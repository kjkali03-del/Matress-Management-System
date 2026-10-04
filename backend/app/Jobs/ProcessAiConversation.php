<?php

namespace App\Jobs;

use App\Models\Message;
use App\Services\AI\AiAgentOrchestrator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;

class ProcessAiConversation implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use \Illuminate\Bus\Queueable;
    use SerializesModels;

    public int $tries = 5;

    public int $timeout = 300;

    public int $uniqueFor = 360;

    /** @return array<int, object> */
    public function middleware(): array
    {
        $conversationId = Message::query()->whereKey($this->messageId)->value('conversation_id');
        if (! $conversationId) {
            return [];
        }

        return [
            (new WithoutOverlapping('wgp-ai-conversation-' . $conversationId))
                ->releaseAfter(10)
                ->expireAfter(360),
        ];
    }

    public function __construct(public readonly int $messageId) {}

    public function uniqueId(): string
    {
        return 'wgp-ai-inbound-' . $this->messageId;
    }

    public function handle(AiAgentOrchestrator $agent): void
    {
        $message = Message::query()->with('conversation.customer')->find($this->messageId);
        if ($message) {
            $agent->handle($message);
        }
    }
}
