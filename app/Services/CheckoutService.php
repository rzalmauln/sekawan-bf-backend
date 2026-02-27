<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Repositories\ItemRepository;
use App\Repositories\OrderItemRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\DB;
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
        return DB::transaction(function () use ($data, $paymentProof) {

            // Upload file
            $proofPath = $paymentProof->store('payment_proofs', 'public');

            // 1. Create customer
            $customer = $this->customerRepo->create($data['customer']);

            // 2. Create order
            $order = $this->orderRepo->create([
                'invoice_number' => $this->generateInvoice(),
                'customer_id' => $customer->id,
                'status' => 'pending',
                'payment_proof_path' => $proofPath,
                'total_price' => 0
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

            return [
                'message' => 'Order berhasil dibuat',
                'invoice' => $order->invoice_number
            ];
        });
    }

    public function verify(int $orderId)
    {
        return DB::transaction(function () use ($orderId) {

            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== 'pending') {
                throw new \Exception('Order tidak bisa diverifikasi');
            }

            $this->orderRepo->verify($order);

            return [
                'message' => 'Order berhasil diverifikasi',
                'invoice' => $order->invoice_number
            ];
        });
    }

    public function ship(int $orderId, string $trackingNumber)
    {
        return DB::transaction(function () use ($orderId, $trackingNumber) {

            $order = $this->orderRepo->findByIdForUpdate($orderId);

            if ($order->status !== 'verified') {
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
    }

    public function cancel(int $id)
    {
        DB::transaction(function () use ($id) {

            $order = $this->orderRepo->findByIdForUpdate($id);
            if ($order->status !== 'pending') {
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
    }

    private function generateInvoice(): string
    {
        return 'INV-' . now()->format('YmdHis') . '-' . Str::random(5);
    }
}
