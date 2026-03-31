<?php

namespace App\Services;

use App\Jobs\CancelOrderWhatsappJob;
use App\Jobs\CompleteOrderWhatsappJob;
use App\Jobs\RequestPaymentWhatsappJob;
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

    public function checkout(array $data)
    {
        try {
            $order = DB::transaction(function () use ($data) {
                $customer = $this->customerRepo->create($data['customer']);

                $order = $this->orderRepo->create([
                    'invoice_number' => $this->generateInvoice(),
                    'customer_id' => $customer->id,
                    'status' => Order::STATUS_BOOKING,
                    'total_price' => 0,
                    'shipping_cost' => null,
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

                return $order;
            });

            SendOrderWhatsappJob::dispatch($order->id);

            Log::info('Order created', [
                'invoice' => $order->invoice_number,
            ]);

            return [
                'message' => 'Booking berhasil dibuat',
                'invoice' => $order->invoice_number,
            ];
        } catch (\Throwable $e) {
            throw new \Exception('Checkout gagal', previous: $e);
        }
    }

    public function requestPayment(int $orderId, float $shippingCost)
    {
        $result = DB::transaction(function () use ($orderId, $shippingCost) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_BOOKING) {
                throw new \Exception('Order tidak berada pada status booking');
            }

            if ($shippingCost < 0) {
                throw new \Exception('Ongkir tidak boleh kurang dari 0');
            }

            $subtotal = (float) $order->orderItems->sum('subtotal');
            $total = $subtotal + $shippingCost;

            $this->orderRepo->requestPayment($order, $shippingCost, $total);

            $freshOrder = $order->fresh();

            return [
                'message' => 'Permintaan pembayaran berhasil dikirim',
                'invoice' => $freshOrder->invoice_number,
                'shipping_cost' => (float) $freshOrder->shipping_cost,
                'total_price' => (float) $freshOrder->total_price,
                'payment_due_at' => $freshOrder->payment_due_at,
            ];
        });

        RequestPaymentWhatsappJob::dispatch($orderId);

        return $result;
    }

    public function submitPaymentProof(string $invoiceNumber, UploadedFile $paymentProof)
    {
        $proofPath = null;

        try {
            return DB::transaction(function () use ($invoiceNumber, $paymentProof, &$proofPath) {
                $order = $this->orderRepo->findByInvoiceNumber($invoiceNumber);

                if (!$order) {
                    throw new \Exception('Order tidak ditemukan');
                }

                if ($order->status !== Order::STATUS_BOOKING) {
                    throw new \Exception('Order tidak berada pada status booking');
                }

                if (!$order->payment_requested_at || !$order->payment_due_at) {
                    throw new \Exception('Order belum siap menerima pembayaran');
                }

                if ($order->payment_due_at->isPast()) {
                    throw new \Exception('Batas waktu pembayaran telah berakhir');
                }

                $proofPath = $paymentProof->store('payment_proofs', 'public');

                if ($order->payment_proof_path && Storage::disk('public')->exists($order->payment_proof_path)) {
                    Storage::disk('public')->delete($order->payment_proof_path);
                }

                $this->orderRepo->updatePaymentProof($order, $proofPath);

                return [
                    'message' => 'Bukti pembayaran berhasil dikirim',
                    'invoice' => $order->invoice_number,
                ];
            });
        } catch (\Throwable $e) {
            if ($proofPath && Storage::disk('public')->exists($proofPath)) {
                Storage::disk('public')->delete($proofPath);
            }

            throw new \Exception($e->getMessage(), previous: $e);
        }
    }

    public function verify(int $orderId)
    {
        $result = DB::transaction(function () use ($orderId) {
            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== Order::STATUS_BOOKING) {
                throw new \Exception('Order tidak bisa diverifikasi');
            }

            if (!$order->payment_requested_at || !$order->payment_due_at) {
                throw new \Exception('Order belum meminta pembayaran');
            }

            if (empty($order->payment_proof_path)) {
                throw new \Exception('Bukti pembayaran belum diunggah');
            }

            $this->orderRepo->verify($order);

            return [
                'message' => 'Order berhasil diverifikasi',
                'invoice' => $order->invoice_number,
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
                'tracking_number' => $trackingNumber,
            ];
        });

        ShipOrderWhatsappJob::dispatch($orderId);

        return $result;
    }

    public function cancel(int $id)
    {
        $result = DB::transaction(function () use ($id) {
            $order = $this->orderRepo->findByIdForUpdate($id);

            if ($order->status !== Order::STATUS_BOOKING) {
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
                'invoice' => $order->invoice_number,
            ];
        });

        CancelOrderWhatsappJob::dispatch($id);

        return $result;
    }

    public function cancelExpiredBookings(): int
    {
        $expiredOrders = $this->orderRepo->getExpiredBookings();
        $cancelledCount = 0;

        foreach ($expiredOrders as $expiredOrder) {
            $wasCancelled = DB::transaction(function () use ($expiredOrder) {
                $order = $this->orderRepo->findByIdForUpdate($expiredOrder->id);

                if (
                    $order->status !== Order::STATUS_BOOKING ||
                    !$order->payment_due_at ||
                    $order->payment_due_at->isFuture()
                ) {
                    return false;
                }

                foreach ($order->orderItems as $orderItem) {
                    $this->itemRepo->incrementStock(
                        $orderItem->item,
                        $orderItem->qty
                    );
                }

                $this->orderRepo->cancel($order);

                return true;
            });

            if ($wasCancelled) {
                CancelOrderWhatsappJob::dispatch($expiredOrder->id);
                $cancelledCount++;
            }
        }

        return $cancelledCount;
    }

    public function complete(string $invoiceNumber)
    {
        $result = DB::transaction(function () use ($invoiceNumber) {
            $order = $this->orderRepo->findByInvoiceNumber($invoiceNumber);

            if (!$order) {
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

    private function generateInvoice(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . Str::random(5);
    }
}
