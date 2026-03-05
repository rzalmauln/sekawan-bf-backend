<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderWhatsappJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    public $tries = 3; // retry maksimal 3x


    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = \App\Models\Order::with('customer', 'orderItems')
            ->findOrFail($this->orderId);

        $message = $this->buildMessage($order);

        $this->sendWa($order->customer->phone, $message);

        $ownerPhone = env('WA_OWNER');
        if ($ownerPhone) {
            $this->sendWa($ownerPhone, $message);
        }
    }

    private function buildMessage($order)
    {
        $text = "INVOICE: {$order->invoice_number}\n";
        $text .= "Status: " . strtoupper($order->status) . "\n";
        $text .= "Total: Rp " . number_format($order->total_price) . "\n\n";

        foreach ($order->orderItems as $item) {
            $text .= "- {$item->item_name} x{$item->qty}\n";
        }

        return $text;
    }

    private function sendWa($phone, $message)
    {
        // nanti integrasi ke provider WA
        $wa = new \App\Services\WhatsAppService();
        $wa->sendText($phone, $message);
    }
}