<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiConversationEscalated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public readonly int $conversationId) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin-notifications')];
    }

    public function broadcastAs(): string
    {
        return 'ai.conversation.escalated';
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        $conversation = Conversation::query()->with('customer')->findOrFail($this->conversationId);

        return [
            'conversation_id' => $conversation->id,
            'customer_name' => $conversation->customer->name
                ?: $conversation->customer->phone
                ?: 'Customer',
            'url' => route('admin.ai.conversations.show', $conversation),
        ];
    }
}
