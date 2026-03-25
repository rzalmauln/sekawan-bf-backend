<?php

namespace App\Jobs;

use App\Jobs\Concerns\BuildsOrderWhatsappMessage;
use App\Models\Order;
use App\Services\WhatsAppService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelOrderWhatsappJob implements ShouldQueue
{
    use BuildsOrderWhatsappMessage, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
        $informationLines = [
            'Pesanan Anda telah dibatalkan.',
        ];

        if ($order->cancelled_at) {
            $informationLines[] = 'Waktu batal : ' . $order->cancelled_at->format('d-m-Y H:i');
        }

        $informationLines[] = '';
        $informationLines[] = 'Jika pembatalan ini bukan dari Anda atau Anda memerlukan bantuan lebih lanjut, silakan hubungi admin kami.';
        $informationLines[] = 'Kami siap membantu Anda untuk proses pemesanan kembali jika diperlukan.';
        $informationLines[] = '';
        $informationLines[] = 'Terima kasih.';

        return $this->buildOrderWhatsappMessage(
            $order,
            'PESANAN DIBATALKAN',
            'INFORMASI PESANAN',
            $informationLines
        );
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
