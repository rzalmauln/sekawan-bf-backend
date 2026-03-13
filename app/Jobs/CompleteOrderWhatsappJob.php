<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CompleteOrderWhatsappJob implements ShouldQueue
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
        $text = "Pesanan Anda telah selesai.\n";
        $text .= "INVOICE: {$order->invoice_number}\n";
        $text .= "Status: COMPLETED\n";
        $text .= "Total: Rp " . number_format((float) $order->total_price) . "\n";

        if ($order->completed_at) {
            $text .= "Selesai pada: " . $order->completed_at->format('d-m-Y H:i') . "\n";
        }

        $text .= "\nDetail pesanan:\n";

        foreach ($order->orderItems as $item) {
            $text .= "- {$item->item_name} x{$item->qty}\n";
        }

        $text .= "\nTerima kasih telah berbelanja bersama kami.";

        return $text;
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
