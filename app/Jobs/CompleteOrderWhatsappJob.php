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

class CompleteOrderWhatsappJob implements ShouldQueue
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
        $order = Order::with('customer', 'orderItems.item')
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
            'Pesanan Anda telah dinyatakan selesai.',
            'Terima kasih telah mempercayakan pembelian Anda kepada kami.',
        ];

        if ($order->completed_at) {
            $informationLines[] = '';
            $informationLines[] = 'Waktu selesai : ' . $order->completed_at->format('d-m-Y H:i');
        }

        $informationLines[] = '';
        $informationLines[] = 'Semoga burung yang Anda pilih sesuai harapan dan membawa kepuasan.';
        $informationLines[] = 'Kami tunggu pesanan Anda berikutnya.';
        $informationLines[] = '';
        $informationLines[] = '*INFORMASI SERTIFIKAT:*';
        $informationLines[] = 'Password sertifikat untuk setiap item tertera pada rincian pesanan di atas.';
        $informationLines[] = '_Mohon simpan dan jangan bagikan password sertifikat kepada pihak lain._';
        $informationLines[] = '';
        $informationLines[] = 'Terima kasih.';

        return $this->buildOrderWhatsappMessage(
            $order,
            'PESANAN SELESAI',
            'INFORMASI PESANAN',
            $informationLines,
            function ($orderItem) {
                $certificatePassword = $orderItem->item?->certificate_password;

                if (!$certificatePassword) {
                    return [];
                }

                return [
                    '   Password Sertifikat : *' . $certificatePassword . '*',
                ];
            }
        );
    }

    private function sendWa(string $phone, string $message): void
    {
        $wa = new WhatsAppService();
        $wa->sendText($phone, $message);
    }
}
