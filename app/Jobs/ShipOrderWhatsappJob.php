<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShipOrderWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    public $tries = 3;

    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        $order = Order::with('customer', 'orderItems')
            ->findOrFail($this->orderId);

        $message = $this->buildMessage($order);

        $this->sendWa($order->customer->phone, $message);

        $ownerPhone = env('WA_OWNER');
        if ($ownerPhone) {
            $this->sendWa($ownerPhone, $message);
        }
    }

    private function buildMessage(Order $order): string
    {
        $text = "Pesanan Anda sudah dikirim.\n";
        $text .= "INVOICE: {$order->invoice_number}\n";
        $text .= "Status: SHIPPED\n";
        $text .= "Total: Rp " . number_format((float) $order->total_price) . "\n";

        if ($order->tracking_number) {
            $text .= "Nomor resi: {$order->tracking_number}\n";
        }

        if ($order->shipped_at) {
            $text .= "Dikirim pada: " . $order->shipped_at->format('d-m-Y H:i') . "\n";
        }

        $text .= "\nDetail pesanan:\n";

        foreach ($order->orderItems as $item) {
            $text .= "- {$item->item_name} x{$item->qty}\n";
        }

        $text .= "\nSilakan simpan nomor invoice untuk konfirmasi pesanan selesai.";

        return $text;
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
