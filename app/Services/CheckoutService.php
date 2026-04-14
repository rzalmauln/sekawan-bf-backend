<?php

namespace App\Services;

use App\Jobs\CompleteOrderWhatsappJob;
use App\Jobs\SendOrderWhatsappJob;
use App\Jobs\ShipOrderWhatsappJob;
use App\Jobs\VerifyOrderWhatsappJob;
use App\Models\Order;
use App\Repositories\CustomerRepository;
use App\Repositories\ItemRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        protected CustomerRepository $customerRepo,
        protected OrderRepository $orderRepo,
        protected OrderItemRepository $orderItemRepo,
        protected ItemRepository $itemRepo
    ) {}

    /**
     * Create order with direct checkout and payment proof upload
     * User uploads payment proof immediately during checkout
     */
    public function checkout(array $data)
    {
        $proofPath = null;

        try {
            return DB::transaction(function () use ($data, &$proofPath) {
                // Validate payment proof file is provided
                if (!isset($data['payment_proof']) || !($data['payment_proof'] instanceof UploadedFile)) {
                    throw new \Exception("Bukti pembayaran wajib diunggah");
                }

                $customer = $this->customerRepo->create($data['customer']);

                $order = $this->orderRepo->create([
                    'invoice_number' => $this->generateInvoice(),
                    'customer_id' => $customer->id,
                    'status' => Order::STATUS_PENDING,
                    'total_price' => 0,
                    'payment_proof_path' => null,
                ]);

                $total = 0;

                foreach ($data['items'] as $row) {
                    $item = $this->itemRepo->findForUpdate($row['item_id']);

                    if ($item->stock < $row['qty']) {
                        throw new \Exception("Stock {$item->name} tidak cukup");
                    }

                    $this->itemRepo->decrementStock($item, $row['qty']);

                    $subtotal = $item->price * $row['qty'];

                    $this->orderItemRepo->create([
                        'order_id' => $order->id,
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'unit_price' => $item->price,
                        'qty' => $row['qty'],
                        'subtotal' => $subtotal,
                    ]);

                    $total += $subtotal;
                }

                $this->orderRepo->updateTotal($order, $total);

                // Store payment proof
                $proofPath = $data['payment_proof']->store('payment_proofs', 'public');
                $this->orderRepo->updatePaymentProof($order, $proofPath);
                
                Log::info('Order created', [
                    'invoice' => $order->invoice_number
                ]);
                return [
                    'message' => 'Pesanan berhasil dibuat, menunggu verifikasi admin',
                    'invoice' => $order->invoice_number,
                    'status' => $order->status,
                ];
            });
        } catch (\Throwable $e) {
            // Clean up uploaded file if transaction fails
            if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                Storage::disk('public')->delete($proofPath);
            }

            throw new \Exception('Checkout gagal: '.$e->getMessage(), previous: $e);
        }
    }

    /**
     * Admin verifies payment proof and marks order as paid
     */
    public function verifyPayment(int $orderId)
    {
        $result = DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_PENDING) {
                throw new \Exception('Order harus dalam status pending untuk diverifikasi');
            }

            if (empty($order->payment_proof_path)) {
                throw new \Exception('Bukti pembayaran tidak ditemukan');
            }

            $this->orderRepo->verifyPayment($order);

            return [
                'message' => 'Pembayaran berhasil diverifikasi',
                'invoice' => $order->invoice_number,
                'status' => $order->status,
            ];
        });

        VerifyOrderWhatsappJob::dispatch($orderId);

        return $result;
    }

    /**
     * Admin ships order with tracking number
     */
    public function ship(int $orderId, string $trackingNumber)
    {
        $result = DB::transaction(function () use ($orderId, $trackingNumber) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_PAID) {
                throw new \Exception('Order harus sudah dibayar sebelum dapat dikirim');
            }

            if (empty($trackingNumber)) {
                throw new \Exception('Nomor resi wajib diisi');
            }

            $this->orderRepo->ship($order, $trackingNumber);

            return [
                'message' => 'Order berhasil dikirim',
                'invoice' => $order->invoice_number,
                'tracking_number' => $trackingNumber,
                'status' => $order->status,
            ];
        });

        ShipOrderWhatsappJob::dispatch($orderId);

        return $result;
    }

    public function verify(int $orderId)
    {
        $result = DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_PENDING) {
                throw new \Exception('Order tidak bisa diverifikasi');
            }

            if (! $order->payment_requested_at || ! $order->payment_due_at) {
                throw new \Exception('Order belum meminta pembayaran');
            }

            if (empty($order->payment_proof_path)) {
                throw new \Exception('Bukti pembayaran belum diunggah');
            }

            $this->orderRepo->verifyPayment($order);

            return [
                'message' => 'Order berhasil diverifikasi',
                'invoice' => $order->invoice_number,
            ];
        });

        VerifyOrderWhatsappJob::dispatch($orderId);

        return $result;
    }

    /**
     * Admin cancels order and return stock
     */
    public function cancel(int $orderId)
    {
        $result = DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);

            // Can only cancel pending or paid orders
            if (!in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_PAID])) {
                throw new \Exception('Order tidak bisa dibatalkan pada status saat ini');
            }

            // Return stock
            foreach ($order->orderItems as $orderItem) {
                $this->itemRepo->incrementStock(
                    $orderItem->item,
                    $orderItem->qty
                );
            }

            $this->orderRepo->cancel($order);

            // Delete payment proof if exists
            if ($order->payment_proof_path && Storage::disk('public')->exists($order->payment_proof_path)) {
                Storage::disk('public')->delete($order->payment_proof_path);
            }

            return [
                'message' => 'Order berhasil dibatalkan',
                'invoice' => $order->invoice_number,
            ];
        });

        return $result;
    }

    /**
     * User completes order when item is received
     */
    public function complete(string $invoiceNumber)
    {
        $result = DB::transaction(function () use ($invoiceNumber) {
            $order = $this->orderRepo->findByInvoiceNumber($invoiceNumber);

            if (! $order) {
                throw new \Exception('Order tidak ditemukan');
            }

            if ($order->status !== Order::STATUS_SHIPPED) {
                throw new \Exception('Order belum bisa selesai');
            }

            $this->orderRepo->complete($order);

            return [
                'message' => 'Order berhasil selesai',
                'invoice' => $order->invoice_number,
            ];
        });

        $order = $this->orderRepo->findByInvoiceNumber($invoiceNumber);
        CompleteOrderWhatsappJob::dispatch($order->id);

        return $result;
    }

    public function prunePaymentProofs(int $retentionDays): array
    {
        $orders = $this->orderRepo->getOrdersWithPrunablePaymentProofs($retentionDays);
        $deletedFiles = 0;
        $clearedOrders = 0;

        foreach ($orders as $order) {
            $paymentProofPath = $order->payment_proof_path;

            if ($paymentProofPath && Storage::disk('public')->exists($paymentProofPath)) {
                Storage::disk('public')->delete($paymentProofPath);
                $deletedFiles++;
            }

            $this->orderRepo->clearPaymentProof($order);
            $clearedOrders++;
        }

        return [
            'cleared_orders' => $clearedOrders,
            'deleted_files' => $deletedFiles,
        ];
    }

    private function generateInvoice(): string
    {
        return 'INV-'.now()->format('YmdHis').'-'.Str::random(5);
    }
}
