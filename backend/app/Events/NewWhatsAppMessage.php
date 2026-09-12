<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewWhatsAppMessage implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('admin-notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'whatsapp.message.received';
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing([
            'conversation.customer',
        ]);

        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'customer_id' => $this->message->conversation?->customer?->id,
            'customer_name' => $this->message->conversation?->customer?->name
                ?: $this->message->conversation?->customer?->phone
                ?: 'New Customer',
            'body' => $this->message->body ?: 'New WhatsApp message',
            'url' => route('admin.inbox', [
                'conversation' => $this->message->conversation_id,
            ]),
        ];
    }
}