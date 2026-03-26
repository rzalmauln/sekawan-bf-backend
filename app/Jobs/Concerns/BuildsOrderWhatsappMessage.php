<?php

namespace App\Jobs\Concerns;

use App\Models\Order;

trait BuildsOrderWhatsappMessage
{
    protected function buildOrderWhatsappMessage(
        Order $order,
        string $statusLabel,
        string $informationTitle,
        array $informationLines,
        ?callable $itemDetailBuilder = null
    ): string {
        $customerName = $order->customer?->name ?: 'Pelanggan Yth.';
        $date = $order->created_at
            ? $order->created_at->locale('id')->translatedFormat('d F Y')
            : now()->locale('id')->translatedFormat('d F Y');

        $text = "*INVOICE #{$order->invoice_number}*\n";
        $text .= "--------------------------------------\n";
        $text .= "Tanggal : {$date}\n";
        $text .= "Nama    : {$customerName}\n";
        $text .= "Status  : *{$statusLabel}*\n";
        $text .= "--------------------------------------\n\n";
        $text .= "*RINCIAN PESANAN:*\n\n";

        foreach ($order->orderItems as $index => $item) {
            $number = $index + 1;
            $text .= "{$number}. *{$item->item_name}*\n";
            $text .= "   Qty   : {$item->qty}\n";
            $text .= "   Harga : " . $this->formatRupiah((float) $item->unit_price) . "\n";
            $text .= "   Total : " . $this->formatRupiah((float) $item->subtotal) . "\n\n";

            if ($itemDetailBuilder) {
                $extraLines = $itemDetailBuilder($item) ?? [];

                foreach ($extraLines as $line) {
                    $text .= $line . "\n";
                }

                if (!empty($extraLines)) {
                    $text .= "\n";
                }
            }
        }

        $text .= "--------------------------------------\n";
        $text .= "SUBTOTAL    : " . $this->formatRupiah((float) $order->total_price) . "\n";
        $text .= "ONGKIR      : " . $this->formatRupiah(0) . "\n";
        $text .= "--------------------------------------\n";
        $text .= "*TOTAL TAGIHAN : " . $this->formatRupiah((float) $order->total_price) . "*\n";
        $text .= "--------------------------------------\n\n";
        $text .= "*{$informationTitle}:*\n";

        foreach ($informationLines as $line) {
            $text .= $line . "\n";
        }

        return trim($text);
    }

    protected function formatRupiah(float $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }
}
