<?php

namespace App\Services;

use App\Exceptions\WhatsAppApiException;
use Illuminate\Http\Client\Response;
use Illuminate\Http\UploadedFile;
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
        return $this->sendMessage($recipient, [
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $body,
            ],
        ]);
    }

    /** @return array<string, mixed> */
    public function sendImage(
        string $recipient,
        string $mediaUrl,
        ?string $caption = null
    ): array {
        $image = [
            'link' => $mediaUrl,
        ];

        if (filled($caption)) {
            $image['caption'] = $caption;
        }

        return $this->sendMessage($recipient, [
            'type' => 'image',
            'image' => $image,
        ]);
    }

    /**
     * Send an image using a WhatsApp Cloud API media ID.
     *
     * @return array<string, mixed>
     */
    public function sendImageByMediaId(
        string $recipient,
        string $mediaId,
        ?string $caption = null
    ): array {
        $image = [
            'id' => $mediaId,
        ];

        if (filled($caption)) {
            $image['caption'] = $caption;
        }

        return $this->sendMessage($recipient, [
            'type' => 'image',
            'image' => $image,
        ]);
    }

    /** @return array<string, mixed> */
    public function sendVideo(
        string $recipient,
        string $mediaUrl,
        ?string $caption = null
    ): array {
        $video = [
            'link' => $mediaUrl,
        ];

        if (filled($caption)) {
            $video['caption'] = $caption;
        }

        return $this->sendMessage($recipient, [
            'type' => 'video',
            'video' => $video,
        ]);
    }

    /**
     * Send a video using a WhatsApp Cloud API media ID.
     *
     * @return array<string, mixed>
     */
    public function sendVideoByMediaId(
        string $recipient,
        string $mediaId,
        ?string $caption = null
    ): array {
        $video = [
            'id' => $mediaId,
        ];

        if (filled($caption)) {
            $video['caption'] = $caption;
        }

        return $this->sendMessage($recipient, [
            'type' => 'video',
            'video' => $video,
        ]);
    }

    /** @return array<string, mixed> */
    public function sendLocation(
        string $recipient,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null
    ): array {
        $location = [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];

        if (filled($name)) {
            $location['name'] = $name;
        }

        if (filled($address)) {
            $location['address'] = $address;
        }

        return $this->sendMessage($recipient, [
            'type' => 'location',
            'location' => $location,
        ]);
    }

    /**
     * Upload an image or video file directly to WhatsApp Cloud API.
     *
     * @return array<string, mixed>
     */
    public function uploadMedia(UploadedFile $file): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $mimeType = $file->getMimeType();

        if (! filled($mimeType)) {
            throw new WhatsAppApiException(
                'Unable to determine the uploaded media MIME type.',
                422,
            );
        }

        $contents = file_get_contents($file->getRealPath());

        if ($contents === false) {
            throw new WhatsAppApiException(
                'Unable to read the uploaded media file.',
                422,
            );
        }

        $response = Http::withToken(
            config('services.whatsapp.access_token')
        )
            ->acceptJson()
            ->timeout(60)
            ->attach(
                'file',
                $contents,
                $file->getClientOriginalName(),
                [
                    'Content-Type' => $mimeType,
                ]
            )
            ->post($this->mediaUrl(), [
                'messaging_product' => 'whatsapp',
                'type' => $mimeType,
            ]);

        if ($response->failed()) {
            $this->logFailure($response);

            throw new WhatsAppApiException(
                'WhatsApp media upload failed.',
                $response->status(),
            );
        }

        return $response->json();
    }

    /**
     * @param array<string, mixed> $message
     * @return array<string, mixed>
     */
    private function sendMessage(string $recipient, array $message): array
    {
        if (! $this->isConfigured()) {
            return [];
        }

        $response = Http::withToken(config('services.whatsapp.access_token'))
            ->acceptJson()
            ->timeout(15)
            ->post($this->messagesUrl(), array_merge([
                'messaging_product' => 'whatsapp',
                'to' => $recipient,
            ], $message));

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

    private function mediaUrl(): string
    {
        return sprintf(
            '%s/%s/%s/media',
            rtrim(config('services.whatsapp.base_url'), '/'),
            config('services.whatsapp.api_version'),
            config('services.whatsapp.phone_number_id'),
        );
    }

    private function logFailure(Response $response): void
    {
        Log::warning('WhatsApp API request failed.', [
            'status' => $response->status(),
            'url' => $response->effectiveUri(),
            'content_type' => $response->header('Content-Type'),
            'response_body' => $response->body(),
            'fbtrace_id' => $response->header('x-fb-trace-id'),
        ]);
    }
}