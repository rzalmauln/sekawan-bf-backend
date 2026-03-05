<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function sendText($phone, $message)
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        $payload = [
            'session' => config('services.waha.session'),
            'chatId'  => $phone . '@c.us',
            'text'    => $message,
        ];

        Log::info('WA Payload', $payload);

        $response = Http::timeout(20)
            ->connectTimeout(5)
            ->withHeaders([
                'X-Api-Key' => config('services.waha.key'),
                'Content-Type' => 'application/json'
            ])
            ->send('POST', config('services.waha.url') . '/api/sendText', [
                'body' => json_encode($payload)
            ]);

        Log::info('WA Response', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);

        return $response->json();
    }
}
