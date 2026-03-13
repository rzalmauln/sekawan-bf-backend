<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    public function create(array $data){
        return Order::create($data);
    }

    public function updateTotal(Order $order, float $total)
    {
        $order->update(['total_price' => $total]);
        return $order;
    }

    public function findByIdForUpdate($id)
    {
        return Order::with('orderItems.item')
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findByInvoiceNumber(string $invoiceNumber){
        return Order::with('orderItems.item')
            ->where('invoice_number', $invoiceNumber)
            ->first();
    }

    public function cancel(Order $order)
    {
        $order->update([
            'status' => Order::STATUS_CANCELLED,
            'cancelled_at' => now()
        ]);
        return $order;
    }

    public function verify(Order $order)
    {
        $order->update([
            'status' => Order::STATUS_PAID,
            'paid_at' => now()
        ]);
        return $order;
    }

    public function ship(Order $order, string $trackingNumber)
    {
        $order->update([
            'status' => Order::STATUS_SHIPPED,
            'tracking_number' => $trackingNumber,
            'shipped_at' => now()
        ]);
        return $order;
    }

    public function complete(Order $order)
    {
        $order->update([
            'status' => Order::STATUS_COMPLETED,
            'completed_at' => now()
        ]);
        return $order;
    }

    public function findById($id)
    {
        return Order::with('orderItems.item')->findOrFail($id);
    }
}
