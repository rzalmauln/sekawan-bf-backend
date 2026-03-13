<?php

namespace App\Services;

use App\Jobs\CancelOrderWhatsappJob;
use App\Jobs\CompleteOrderWhatsappJob;
use App\Jobs\SendOrderWhatsappJob;
use App\Jobs\ShipOrderWhatsappJob;
use App\Jobs\VerifyOrderWhatsappJob;
use App\Models\Order;
use App\Repositories\CustomerRepository;
use App\Repositories\ItemRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
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

    public function checkout(array $data, $paymentProof)
    {
        $order = null;

        try {

            // 1️⃣ Jalankan transaksi TANPA file
            $order = DB::transaction(function () use ($data) {

                $customer = $this->customerRepo->create($data['customer']);

                $order = $this->orderRepo->create([
                    'invoice_number' => $this->generateInvoice(),
                    'customer_id' => $customer->id,
                    'status' => Order::STATUS_PENDING,
                    'total_price' => 0,
                    'payment_proof_path' => null
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

                return $order;
            });

            // 2️⃣ Setelah commit → upload file
            if ($paymentProof) {

                $proofPath = $paymentProof->store(
                    'payment_proofs',
                    'public'
                );

                $order->update([
                    'payment_proof_path' => $proofPath
                ]);
            }

            SendOrderWhatsappJob::dispatch($order->id);
            Log::info('Order created', [
                'invoice' => $order->invoice_number
            ]);
            return [
                'message' => 'Order berhasil dibuat',
                'invoice' => $order->invoice_number
            ];
        } catch (\Throwable $e) {

            // Jika file sudah terlanjur upload tapi update gagal
            if (isset($proofPath) && Storage::disk('public')->exists($proofPath)) {
                Storage::disk('public')->delete($proofPath);
            }
            throw new \Exception('Checkout gagal');
        }
    }
    
    public function verify(int $orderId)
    {
        $result = DB::transaction(function () use ($orderId) {

            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_PENDING) {
                throw new \Exception('Order tidak bisa diverifikasi');
            }

            $this->orderRepo->verify($order);

            return [
                'message' => 'Order berhasil diverifikasi',
                'invoice' => $order->invoice_number
            ];
        });

        VerifyOrderWhatsappJob::dispatch($orderId);

        return $result;
    }

    public function ship(int $orderId, string $trackingNumber)
    {
        $result = DB::transaction(function () use ($orderId, $trackingNumber) {

            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_PAID) {
                throw new \Exception('Order belum bisa dikirim');
            }

            if (empty($trackingNumber)) {
                throw new \Exception('Nomor resi wajib diisi');
            }

            $this->orderRepo->ship($order, $trackingNumber);

            return [
                'message' => 'Order berhasil dikirim',
                'invoice' => $order->invoice_number,
                'tracking_number' => $trackingNumber
            ];
        });

        ShipOrderWhatsappJob::dispatch($orderId);

        return $result;
    }

    public function cancel(int $id)
    {
        $result = DB::transaction(function () use ($id) {

            $order = $this->orderRepo->findByIdForUpdate($id);
            if ($order->status !== Order::STATUS_PENDING) {
                throw new \Exception('Order tidak bisa dibatalkan');
            }
            foreach ($order->orderItems as $orderItem) {
                $this->itemRepo->incrementStock(
                    $orderItem->item,
                    $orderItem->qty
                );
            }
            $this->orderRepo->cancel($order);
            return [
                'message' => 'Order berhasil dibatalkan',
                'invoice' => $order->invoice_number
            ];
            
        });

        CancelOrderWhatsappJob::dispatch($id);

        return $result;
    }

    public function complete(string $invoiceNumber)
    {
        $result = DB::transaction(function () use ($invoiceNumber) {

            $order = $this->orderRepo->findByInvoiceNumber($invoiceNumber);
            if ($order->status !== Order::STATUS_SHIPPED) {
                throw new \Exception('Order belum bisa selesai');
            }
            $this->orderRepo->complete($order);
            return [
                'message' => 'Order berhasil selesai',
                'invoice' => $order->invoice_number
            ];
        });

        $order = $this->orderRepo->findByInvoiceNumber($invoiceNumber);
        CompleteOrderWhatsappJob::dispatch($order->id);

        return $result;
    }

    private function generateInvoice(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . Str::random(5);
    }
}
