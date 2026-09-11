<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    public function __construct(
        private readonly ConversationMessageService $conversationMessageService
    ) {}

    /**
     * Run active automations for an incoming message.
     */
    public function handleIncomingMessage(Message $message): void
    {
        if (
            $message->direction !== 'inbound'
            || $message->message_type !== 'text'
            || blank($message->body)
        ) {
            return;
        }

        $message->loadMissing('conversation');

        $automations = Automation::query()
            ->where('is_active', true)
            ->where('trigger', 'message_received')
            ->latest('id')
            ->get();

        foreach ($automations as $automation) {
            if (! $this->conditionsMatch($automation, (string) $message->body)) {
                continue;
            }

            try {
                $executed = $this->executeActions(
                    $automation,
                    $message
                );

                if ($executed) {
                    /*
                     * For now, stop after the first matching automation.
                     * This prevents multiple automations from sending
                     * several replies to the same customer.
                     */
                    break;
                }
            } catch (\Throwable $exception) {
                Log::error('Automation execution failed.', [
                    'automation_id' => $automation->id,
                    'message_id' => $message->id,
                    'exception' => $exception,
                ]);
            }
        }
    }

    /**
     * Check whether the automation conditions match the message.
     */
    private function conditionsMatch(
        Automation $automation,
        string $messageBody
    ): bool {
        $conditions = $automation->conditions ?? [];

        if ($conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            $type = $condition['type'] ?? null;

            if ($type !== 'keyword') {
                return false;
            }

            $keyword = trim((string) ($condition['value'] ?? ''));

            if ($keyword === '') {
                return false;
            }

            if (! $this->containsKeyword($messageBody, $keyword)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Case-insensitive keyword matching.
     */
    private function containsKeyword(
        string $messageBody,
        string $keyword
    ): bool {
        return str_contains(
            mb_strtolower($messageBody),
            mb_strtolower($keyword)
        );
    }

    /**
     * Execute the configured automation actions.
     */
    private function executeActions(
        Automation $automation,
        Message $message
    ): bool {
        $actions = $automation->actions ?? [];

        if ($actions === []) {
            return false;
        }

        $conversation = $message->conversation;

        if (! $conversation) {
            return false;
        }

        $executed = false;

        foreach ($actions as $action) {
            $type = $action['type'] ?? null;

            if ($type === 'send_text') {
                $body = trim((string) ($action['message'] ?? ''));

                if ($body === '') {
                    continue;
                }

                $this->conversationMessageService->sendText(
                    $conversation,
                    $body
                );

                $executed = true;
            }
        }

        return $executed;
    }
}