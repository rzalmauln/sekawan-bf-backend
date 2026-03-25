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

class SendOrderWhatsappJob implements ShouldQueue
{
    use BuildsOrderWhatsappMessage, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    public $tries = 3; // retry maksimal 3x


    public function __construct($orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::with('customer', 'orderItems')
            ->findOrFail($this->orderId);

        $message = $this->buildMessage($order);

        $this->sendWa($order->customer->phone, $message, $order);

        $ownerPhone = env('WA_OWNER');
        if ($ownerPhone) {
            $this->sendWa($ownerPhone, $message, $order);
        }
    }

    private function buildMessage(Order $order): string
    {
        return $this->buildOrderWhatsappMessage(
            $order,
            'MENUNGGU VERIFIKASI',
            'INFORMASI PESANAN',
            [
                'Pesanan Anda sudah kami terima.',
                'Pembayaran Anda sedang menunggu verifikasi admin.',
                '',
                'Jika Anda sudah melakukan pembayaran, mohon tunggu proses pengecekan dari admin.',
                'Notifikasi berikutnya akan kami kirim setelah pembayaran berhasil dikonfirmasi.',
                '',
                '_Link / nomor invoice ini bersifat pribadi. Mohon jangan dibagikan ke pihak lain._',
                '',
                'Terima kasih.',
            ]
        );
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
