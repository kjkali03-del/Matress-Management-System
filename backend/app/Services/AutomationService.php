<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AutomationService
{
    public function __construct(
        private readonly ConversationMessageService $conversationMessages,
    ) {}

    /**
     * Evaluate active automations for an incoming customer message.
     *
     * @return array{
     *     matched: int,
     *     executed: int
     * }
     */
    public function process(
        Conversation $conversation,
        Message $message
    ): array {
        if (
            $message->direction !== 'inbound'
            || $message->message_type !== 'text'
            || blank($message->body)
        ) {
            return [
                'matched' => 0,
                'executed' => 0,
            ];
        }

        $automations = Automation::query()
            ->where('is_active', true)
            ->where('trigger', 'message_received')
            ->get();

        $matched = 0;
        $executed = 0;

        foreach ($automations as $automation) {
            if (! $this->matchesConditions($automation, $message)) {
                continue;
            }

            $matched++;

            if ($this->executeActions($automation, $conversation)) {
                $executed++;
            }
        }

        return [
            'matched' => $matched,
            'executed' => $executed,
        ];
    }

    private function matchesConditions(
        Automation $automation,
        Message $message
    ): bool {
        $conditions = $automation->conditions;

        if (! is_array($conditions) || $conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            $type = (string) ($condition['type'] ?? '');

            if ($type !== 'keyword') {
                continue;
            }

            $keyword = trim((string) ($condition['value'] ?? ''));

            if ($keyword === '') {
                return false;
            }

            if (! $this->containsKeyword(
                (string) $message->body,
                $keyword
            )) {
                return false;
            }
        }

        return true;
    }

    private function containsKeyword(
        string $message,
        string $keyword
    ): bool {
        return Str::contains(
            Str::lower($message),
            Str::lower($keyword)
        );
    }

    private function executeActions(
        Automation $automation,
        Conversation $conversation
    ): bool {
        $actions = $automation->actions;

        if (! is_array($actions) || $actions === []) {
            return false;
        }

        $executed = false;

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            $type = (string) ($action['type'] ?? '');

            if ($type === 'send_text') {
                $body = trim((string) ($action['message'] ?? ''));

                if ($body === '') {
                    continue;
                }

                $this->conversationMessages->sendText(
                    $conversation,
                    $body,
                );

                $executed = true;
            }
        }

        return $executed;
    }
}