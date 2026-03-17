<?php

namespace App\Repositories;

use App\Models\Order;

class OrderRepository
{
    public function create(array $data)
    {
        return Order::create($data);
    }

    public function paginateForAdmin(array $filters = [], int $perPage = 15)
    {
        return Order::with(['customer', 'orderItems'])
            ->when($filters['status'] ?? null, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('invoice_number', 'like', '%' . $search . '%')
                        ->orWhereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery->where('name', 'like', '%' . $search . '%')
                                ->orWhere('phone', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage);
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
