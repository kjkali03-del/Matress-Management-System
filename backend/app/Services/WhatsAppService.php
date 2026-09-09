<?php

namespace App\Services;

use App\Exceptions\WhatsAppApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public function isConfigured(): bool
    {
        return filled(config('services.whatsapp.access_token'))
            && filled(config('services.whatsapp.phone_number_id'));
    }

    /** @return array<string, mixed> */
    public function sendText(string $recipient, string $body): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = Http::withToken(config('services.whatsapp.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->post($this->messagesUrl(), [
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $body,
                ],
            ]);

        if ($response->failed()) {
            $this->logFailure($response);

            throw new WhatsAppApiException(
                'WhatsApp message delivery failed.',
                $response->status(),
            );
        }

        return $response->json();
    }

    private function messagesUrl(): string
    {
        return sprintf(
            '%s/%s/%s/messages',
            rtrim(config('services.whatsapp.base_url'), '/'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );
    }

    private function logFailure(Response $response): void
    {
        Log::warning('WhatsApp API request failed.', [
            'status' => $response->status(),
            'endpoint' => 'messages',
        ]);
    }
}