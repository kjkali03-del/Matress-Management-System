<?php

namespace App\Services;

use App\Exceptions\WhatsAppApiException;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ConversationMessageService
{
    public function __construct(private readonly WhatsAppService $whatsapp) {}

    public function sendText(Conversation $conversation, string $body): Message
    {
        return DB::transaction(function () use ($conversation, $body): Message {
            $sentAt = Carbon::now();
            $customer = $conversation->loadMissing('customer')->customer;

            $providerResponse = $this->whatsapp->sendText(
                (string) $customer->phone,
                $body
            );

            $providerMessageId = data_get(
                $providerResponse,
                'messages.0.id'
            );

            $message = $conversation->messages()->create([
                'direction' => 'outbound',
                'message_type' => 'text',
                'body' => $body,
                'provider_message_id' => $providerMessageId,
                'status' => $providerMessageId || ! $this->whatsapp->isConfigured()
                    ? 'sent'
                    : 'pending',
                'sent_at' => $sentAt,
            ]);

            $conversation->update([
                'last_message_at' => $sentAt,
            ]);

            return $message;
        });
    }

    public function sendImage(
        Conversation $conversation,
        string $mediaUrl,
        ?string $caption = null,
        ?string $mediaId = null,
        ?string $mimeType = null,
        ?string $filename = null
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $mediaUrl,
            $caption,
            $mediaId,
            $mimeType,
            $filename
        ): Message {
            $sentAt = Carbon::now();
            $customer = $conversation->loadMissing('customer')->customer;

            $providerResponse = $this->whatsapp->sendImage(
                (string) $customer->phone,
                $mediaUrl,
                $caption
            );

            return $this->storeMediaMessage(
                conversation: $conversation,
                messageType: 'image',
                providerResponse: $providerResponse,
                sentAt: $sentAt,
                mediaUrl: $mediaUrl,
                mediaId: $mediaId,
                mimeType: $mimeType,
                filename: $filename,
                caption: $caption,
            );
        });
    }

    /**
     * Upload and send an image file using a WhatsApp Cloud API media ID.
     */
    public function sendImageFile(
        Conversation $conversation,
        UploadedFile $file,
        ?string $caption = null
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $file,
            $caption
        ): Message {
            $sentAt = Carbon::now();
            $customer = $conversation->loadMissing('customer')->customer;

            $uploadResponse = $this->whatsapp->uploadMedia($file);

            $mediaId = data_get($uploadResponse, 'id');

            if (! filled($mediaId)) {
                throw new WhatsAppApiException(
                    'WhatsApp did not return a media ID for the uploaded image.',
                    502,
                );
            }

            $providerResponse = $this->whatsapp->sendImageByMediaId(
                (string) $customer->phone,
                (string) $mediaId,
                $caption
            );

            return $this->storeMediaMessage(
                conversation: $conversation,
                messageType: 'image',
                providerResponse: $providerResponse,
                sentAt: $sentAt,
                mediaUrl: '',
                mediaId: (string) $mediaId,
                mimeType: $file->getMimeType(),
                filename: $file->getClientOriginalName(),
                caption: $caption,
            );
        });
    }

    public function sendVideo(
        Conversation $conversation,
        string $mediaUrl,
        ?string $caption = null,
        ?string $mediaId = null,
        ?string $mimeType = null,
        ?string $filename = null
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $mediaUrl,
            $caption,
            $mediaId,
            $mimeType,
            $filename
        ): Message {
            $sentAt = Carbon::now();
            $customer = $conversation->loadMissing('customer')->customer;

            $providerResponse = $this->whatsapp->sendVideo(
                (string) $customer->phone,
                $mediaUrl,
                $caption
            );

            return $this->storeMediaMessage(
                conversation: $conversation,
                messageType: 'video',
                providerResponse: $providerResponse,
                sentAt: $sentAt,
                mediaUrl: $mediaUrl,
                mediaId: $mediaId,
                mimeType: $mimeType,
                filename: $filename,
                caption: $caption,
            );
        });
    }

    /**
     * Upload and send a video file using a WhatsApp Cloud API media ID.
     */
    public function sendVideoFile(
        Conversation $conversation,
        UploadedFile $file,
        ?string $caption = null
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $file,
            $caption
        ): Message {
            $sentAt = Carbon::now();
            $customer = $conversation->loadMissing('customer')->customer;

            $uploadResponse = $this->whatsapp->uploadMedia($file);

            $mediaId = data_get($uploadResponse, 'id');

            if (! filled($mediaId)) {
                throw new WhatsAppApiException(
                    'WhatsApp did not return a media ID for the uploaded video.',
                    502,
                );
            }

            $providerResponse = $this->whatsapp->sendVideoByMediaId(
                (string) $customer->phone,
                (string) $mediaId,
                $caption
            );

            return $this->storeMediaMessage(
                conversation: $conversation,
                messageType: 'video',
                providerResponse: $providerResponse,
                sentAt: $sentAt,
                mediaUrl: '',
                mediaId: (string) $mediaId,
                mimeType: $file->getMimeType(),
                filename: $file->getClientOriginalName(),
                caption: $caption,
            );
        });
    }

    public function sendLocation(
        Conversation $conversation,
        float $latitude,
        float $longitude,
        ?string $name = null,
        ?string $address = null
    ): Message {
        return DB::transaction(function () use (
            $conversation,
            $latitude,
            $longitude,
            $name,
            $address
        ): Message {
            $sentAt = Carbon::now();
            $customer = $conversation->loadMissing('customer')->customer;

            $providerResponse = $this->whatsapp->sendLocation(
                (string) $customer->phone,
                $latitude,
                $longitude,
                $name,
                $address
            );

            $providerMessageId = data_get(
                $providerResponse,
                'messages.0.id'
            );

            $message = $conversation->messages()->create([
                'direction' => 'outbound',
                'message_type' => 'location',
                'body' => null,
                'provider_message_id' => $providerMessageId,
                'status' => $providerMessageId || ! $this->whatsapp->isConfigured()
                    ? 'sent'
                    : 'pending',
                'sent_at' => $sentAt,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_name' => $name,
                'location_address' => $address,
            ]);

            $conversation->update([
                'last_message_at' => $sentAt,
            ]);

            return $message;
        });
    }

    /**
     * @param array<string, mixed> $providerResponse
     */
    private function storeMediaMessage(
        Conversation $conversation,
        string $messageType,
        array $providerResponse,
        Carbon $sentAt,
        string $mediaUrl,
        ?string $mediaId,
        ?string $mimeType,
        ?string $filename,
        ?string $caption
    ): Message {
        $providerMessageId = data_get(
            $providerResponse,
            'messages.0.id'
        );

        $message = $conversation->messages()->create([
            'direction' => 'outbound',
            'message_type' => $messageType,
            'body' => $caption,
            'provider_message_id' => $providerMessageId,
            'status' => $providerMessageId || ! $this->whatsapp->isConfigured()
                ? 'sent'
                : 'pending',
            'sent_at' => $sentAt,
            'media_id' => $mediaId,
            'media_url' => $mediaUrl !== '' ? $mediaUrl : null,
            'media_mime_type' => $mimeType,
            'media_filename' => $filename,
            'media_caption' => $caption,
        ]);

        $conversation->update([
            'last_message_at' => $sentAt,
        ]);

        return $message;
    }
}