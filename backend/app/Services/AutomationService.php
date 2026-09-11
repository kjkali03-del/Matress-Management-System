<?php

namespace App\Services;

use App\Models\Automation;
use App\Models\Message;
use Illuminate\Support\Facades\Log;

class AutomationService
{
    /**
     * Compulsory response for customers mentioning a region
     * outside Dar es Salaam.
     */
    private const OUTSIDE_DAR_RESPONSE =
        'Bosi, huduma ya usafirishaji inapatikana mkoani Dar es Salaam tu. Endapo utahitaji kusafirishwa, tafadhali tupatie mawasiliano ya msafirishaji wako.';

    /**
     * Tanzanian regions and common spelling variations.
     *
     * Dar es Salaam is intentionally excluded because it is the
     * supported delivery region.
     *
     * @var array<string, string>
     */
    private const OUTSIDE_DAR_REGIONS = [
        'arusha' => 'Arusha',
        'arusha mjini' => 'Arusha',
        'dodoma' => 'Dodoma',
        'geita' => 'Geita',
        'iringa' => 'Iringa',
        'kagera' => 'Kagera',
        'katavi' => 'Katavi',
        'kigoma' => 'Kigoma',
        'kilimanjaro' => 'Kilimanjaro',
        'mbeya' => 'Mbeya',
        'manyara' => 'Manyara',
        'mara' => 'Mara',
        'morogoro' => 'Morogoro',
        'mwanza' => 'Mwanza',
        'mtwara' => 'Mtwara',
        'njombe' => 'Njombe',
        'pwani' => 'Pwani',
        'rukwa' => 'Rukwa',
        'ruvuma' => 'Ruvuma',
        'shinyanga' => 'Shinyanga',
        'simiyu' => 'Simiyu',
        'singida' => 'Singida',
        'songwe' => 'Songwe',
        'tabora' => 'Tabora',
        'tanga' => 'Tanga',
        'zanzibar' => 'Zanzibar',
        'unguja' => 'Zanzibar',
        'pemba' => 'Zanzibar',
    ];

    public function __construct(
        private readonly ConversationMessageService $conversationMessageService
    ) {
    }

    /**
     * Run automations for an incoming customer message.
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

        try {
            /*
             * LOCATION RULE HAS HIGHEST PRIORITY.
             *
             * If the customer mentions a region outside Dar es Salaam,
             * send the compulsory delivery response and stop.
             */
            if ($this->mentionsOutsideDarRegion((string) $message->body)) {
                $this->sendCompulsoryLocationResponse($message);

                return;
            }

            $this->runAutomations($message);
        } catch (\Throwable $exception) {
            Log::error('Automation processing failed.', [
                'message_id' => $message->id,
                'exception' => $exception,
            ]);
        }
    }

    /**
     * Execute the first matching automation.
     */
    private function runAutomations(Message $message): void
    {
        $automations = Automation::query()
            ->where('is_active', true)
            ->whereIn('trigger', [
                'new_customer',
                'message_received',
                'keyword',
            ])
            ->latest('id')
            ->get();

        foreach ($automations as $automation) {
            if (! $this->triggerMatches($automation, $message)) {
                continue;
            }

            try {
                $executed = $this->executeAutomation(
                    $automation,
                    $message
                );

                if ($executed) {
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
     * Determine whether an automation trigger matches the message.
     */
    private function triggerMatches(
        Automation $automation,
        Message $message
    ): bool {
        return match ($automation->trigger) {
            'new_customer' => $this->isNewCustomer($message),

            'message_received' => $this->conditionsMatch(
                $automation,
                (string) $message->body
            ),

            'keyword' => $this->findMatchingKeyword(
                $automation,
                (string) $message->body
            ) !== null,

            default => false,
        };
    }

    /**
     * Check whether this is the customer's first message.
     */
    private function isNewCustomer(Message $message): bool
    {
        $conversation = $message->conversation;

        if (! $conversation) {
            return false;
        }

        $customerId = $conversation->customer_id;

        return ! Message::query()
            ->where('id', '!=', $message->id)
            ->whereHas('conversation', function ($query) use ($customerId): void {
                $query->where('customer_id', $customerId);
            })
            ->exists();
    }

    /**
     * Match ordinary message_received conditions.
     */
    private function conditionsMatch(
        Automation $automation,
        string $messageBody
    ): bool {
        $conditions = $automation->conditions ?? [];

        if (! is_array($conditions) || $conditions === []) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            if (($condition['type'] ?? null) !== 'keyword') {
                continue;
            }

            $keyword = trim(
                (string) ($condition['value'] ?? '')
            );

            if ($keyword === '') {
                continue;
            }

            if ($this->containsKeyword($messageBody, $keyword)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the first matching keyword condition.
     *
     * Each keyword can have its own response.
     *
     * @return array<string, mixed>|null
     */
    private function findMatchingKeyword(
        Automation $automation,
        string $messageBody
    ): ?array {
        $conditions = $automation->conditions ?? [];

        if (! is_array($conditions)) {
            return null;
        }

        foreach ($conditions as $condition) {
            if (! is_array($condition)) {
                continue;
            }

            if (($condition['type'] ?? null) !== 'keyword') {
                continue;
            }

            $keyword = trim(
                (string) ($condition['value'] ?? '')
            );

            if (
                $keyword !== ''
                && $this->containsKeyword($messageBody, $keyword)
            ) {
                return $condition;
            }
        }

        return null;
    }

    /**
     * Execute an automation.
     */
    private function executeAutomation(
        Automation $automation,
        Message $message
    ): bool {
        $conversation = $message->conversation;

        if (! $conversation) {
            return false;
        }

        /*
         * For keyword automations, use the response attached to
         * the matching keyword.
         */
        if ($automation->trigger === 'keyword') {
            $matchingKeyword = $this->findMatchingKeyword(
                $automation,
                (string) $message->body
            );

            if ($matchingKeyword) {
                $response = trim(
                    (string) (
                        $matchingKeyword['response']
                        ?? ''
                    )
                );

                if ($response !== '') {
                    $this->conversationMessageService->sendText(
                        $conversation,
                        $response
                    );

                    return true;
                }
            }
        }

        /*
         * New customer and message_received use configured actions.
         */
        $actions = $automation->actions ?? [];

        if (! is_array($actions) || $actions === []) {
            return false;
        }

        $executed = false;

        foreach ($actions as $action) {
            if (! is_array($action)) {
                continue;
            }

            if (($action['type'] ?? null) !== 'send_text') {
                continue;
            }

            $body = trim(
                (string) ($action['message'] ?? '')
            );

            if ($body === '') {
                continue;
            }

            $this->conversationMessageService->sendText(
                $conversation,
                $body
            );

            $executed = true;
        }

        return $executed;
    }

    /**
     * Detect a Tanzanian region outside Dar es Salaam.
     */
    private function mentionsOutsideDarRegion(
        string $messageBody
    ): bool {
        $normalized = mb_strtolower(
            trim($messageBody),
            'UTF-8'
        );

        foreach (self::OUTSIDE_DAR_REGIONS as $region) {
            $pattern = '/(?<![\p{L}\p{N}])'
                . preg_quote($region, '/')
                . '(?![\p{L}\p{N}])/iu';

            if (preg_match($pattern, $normalized) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Send the compulsory delivery/location response.
     */
    private function sendCompulsoryLocationResponse(
        Message $message
    ): void {
        $conversation = $message->conversation;

        if (! $conversation) {
            return;
        }

        $this->conversationMessageService->sendText(
            $conversation,
            self::OUTSIDE_DAR_RESPONSE
        );
    }

    /**
     * Case-insensitive keyword matching.
     */
    private function containsKeyword(
        string $messageBody,
        string $keyword
    ): bool {
        return str_contains(
            mb_strtolower($messageBody, 'UTF-8'),
            mb_strtolower($keyword, 'UTF-8')
        );
    }
}