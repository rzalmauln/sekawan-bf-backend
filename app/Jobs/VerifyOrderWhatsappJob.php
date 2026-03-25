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

class VerifyOrderWhatsappJob implements ShouldQueue
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
            'Pembayaran Anda telah berhasil kami konfirmasi.',
            'Pesanan Anda sedang kami proses untuk tahap berikutnya.',
        ];

        if ($order->paid_at) {
            $informationLines[] = '';
            $informationLines[] = 'Waktu konfirmasi : ' . $order->paid_at->format('d-m-Y H:i');
        }

        $informationLines[] = '';
        $informationLines[] = 'Mohon tunggu notifikasi selanjutnya saat pesanan sudah dikirim.';
        $informationLines[] = '';
        $informationLines[] = 'Terima kasih.';

        return $this->buildOrderWhatsappMessage(
            $order,
            'PEMBAYARAN TERVERIFIKASI',
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
