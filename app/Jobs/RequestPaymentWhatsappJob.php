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

class RequestPaymentWhatsappJob implements ShouldQueue
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
        $confirmationUrl = rtrim(config('app.frontend_url', config('app.url')), '/') . '/order/confirm/' . $order->invoice_number;

        $informationLines = [
            'Pesanan Anda telah kami verifikasi dan total pembayaran sudah diperbarui dengan ongkir.',
            'Silakan lakukan pembayaran dan upload bukti pembayaran melalui link berikut:',
            '',
            $confirmationUrl,
        ];

        if ($order->payment_due_at) {
            $informationLines[] = '';
            $informationLines[] = 'Batas waktu pembayaran : ' . $order->payment_due_at->format('d-m-Y H:i');
            $informationLines[] = 'Apabila dalam 1x24 jam pembayaran belum dikonfirmasi, pesanan akan otomatis dibatalkan.';
        }

        $informationLines[] = '';
        $informationLines[] = '_Link / nomor invoice ini bersifat pribadi. Mohon jangan dibagikan ke pihak lain._';
        $informationLines[] = '';
        $informationLines[] = 'Terima kasih.';

        return $this->buildOrderWhatsappMessage(
            $order,
            'MENUNGGU PEMBAYARAN',
            'INFORMASI PEMBAYARAN',
            $informationLines
        );
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
