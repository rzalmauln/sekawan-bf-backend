<?php

namespace App\Exports;

use App\Models\Order;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SalesExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    public function __construct(
        protected int $year,
        protected ?int $month = null
    ) {}

    public function collection(): Collection
    {
        return Order::with(['customer', 'orderItems'])
            ->where('status', Order::STATUS_COMPLETED)
            ->whereYear('completed_at', $this->year)
            ->when($this->month, function ($query, $month) {
                $query->whereMonth('completed_at', $month);
            })
            ->orderBy('completed_at')
            ->get();
    }

    public function headings(): array
    {
        return [
            'Invoice Number',
            'Status',
            'Customer Name',
            'Customer Phone',
            'Items',
            'Tracking Number',
            'Total Price',
            'Paid At',
            'Shipped At',
            'Completed At',
            'Created At',
        ];
    }

    public function map($order): array
    {
        return [
            $order->invoice_number,
            strtoupper($order->status),
            $order->customer?->name,
            $order->customer?->phone,
            $order->orderItems->map(function ($item) {
                return "{$item->item_name} x{$item->qty}";
            })->implode(', '),
            $order->tracking_number,
            (float) $order->total_price,
            optional($order->paid_at)?->format('Y-m-d H:i:s'),
            optional($order->shipped_at)?->format('Y-m-d H:i:s'),
            optional($order->completed_at)?->format('Y-m-d H:i:s'),
            optional($order->created_at)?->format('Y-m-d H:i:s'),
        ];
    }
}
