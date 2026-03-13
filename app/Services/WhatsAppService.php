<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    public function sendText($phone, $message)
    {
        $chatId = $this->normalizeChatId($phone);

        $payload = [
            'session' => config('services.waha.session'),
            'chatId'  => $chatId,
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

    private function normalizeChatId(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (Str::startsWith($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        }

        return $phone . '@c.us';
    }
}
