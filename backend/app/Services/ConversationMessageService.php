<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
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
            $providerResponse = $this->whatsapp->sendText((string) $customer->phone, $body);
            $providerMessageId = data_get($providerResponse, 'messages.0.id');

            $message = $conversation->messages()->create([
                'direction' => 'outbound',
                'message_type' => 'text',
                'body' => $body,
                'provider_message_id' => $providerMessageId,
                'status' => $providerMessageId || ! $this->whatsapp->isConfigured() ? 'sent' : 'pending',
                'sent_at' => $sentAt,
            ]);

            $conversation->update(['last_message_at' => $sentAt]);

            return $message;
        });
    }
}