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

class ShipOrderWhatsappJob implements ShouldQueue
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
        $confirmationUrl = rtrim(config('app.url'), '/') . '/order/confirm/' . $order->invoice_number;
        $informationLines = [
            'Pesanan Anda sudah kami kirim.',
            '',
            'Nomor Resi : *' . ($order->tracking_number ?: '-') . '*',
        ];

        if ($order->shipped_at) {
            $informationLines[] = 'Waktu kirim : ' . $order->shipped_at->format('d-m-Y H:i');
        }

        $informationLines[] = '';
        $informationLines[] = 'Silakan pantau pengiriman Anda menggunakan nomor resi di atas.';
        $informationLines[] = 'Jika pesanan sudah diterima dengan baik, Anda dapat melakukan konfirmasi melalui link berikut:';
        $informationLines[] = '';
        $informationLines[] = $confirmationUrl;
        $informationLines[] = '';
        $informationLines[] = '_Link / nomor invoice ini bersifat pribadi. Mohon jangan dibagikan ke pihak lain._';
        $informationLines[] = '';
        $informationLines[] = 'Terima kasih.';

        return $this->buildOrderWhatsappMessage(
            $order,
            'PESANAN DIKIRIM',
            'INFORMASI PENGIRIMAN',
            $informationLines
        );
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
