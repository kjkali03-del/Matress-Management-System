<?php

namespace App\Console\Commands;

use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Message;
use App\Services\ConversationMessageService;
use Illuminate\Console\Command;

class ProcessAutomationFollowUps extends Command
{
    protected $signature = 'automation:process-followups {--dry-run : Show matches without sending messages}';

    protected $description = 'Process active no-reply automation follow-ups.';

    public function handle(ConversationMessageService $messages): int
    {
        $automations = Automation::where('is_active', true)
            ->where('trigger', 'no_reply')
            ->get();

        $processed = 0;

        foreach ($automations as $automation) {
            $condition = $automation->conditions[0] ?? [];
            $delay = $this->delay($condition);
            $cutoff = now()->subSeconds($delay);
            $action = $automation->actions[0] ?? [];
            $body = trim((string) ($action['message'] ?? ''));

            if ($body === '') {
                continue;
            }

            Message::query()
                ->with(['conversation.customer'])
                ->where('direction', 'outbound')
                ->where('message_type', 'text')
                ->whereNotNull('sent_at')
                ->where('sent_at', '<=', $cutoff)
                ->orderBy('id')
                ->chunkById(100, function ($outboundMessages) use ($automation, $body, $messages, &$processed): void {
                    foreach ($outboundMessages as $outbound) {
                        $conversation = $outbound->conversation;
                        if (! $conversation || ! $conversation->customer) {
                            continue;
                        }

                        $hasLaterInbound = Message::query()
                            ->where('conversation_id', $conversation->id)
                            ->where('direction', 'inbound')
                            ->where('created_at', '>', $outbound->created_at)
                            ->exists();

                        if ($hasLaterInbound) {
                            continue;
                        }

                        $alreadyRun = AutomationRun::query()
                            ->where('automation_id', $automation->id)
                            ->where('message_id', $outbound->id)
                            ->exists();

                        if ($alreadyRun) {
                            continue;
                        }

                        if ($this->option('dry-run')) {
                            $this->line("Would follow up: {$conversation->customer->phone} using {$automation->name}");
                            $processed++;
                            continue;
                        }

                        $run = AutomationRun::create([
                            'automation_id' => $automation->id,
                            'customer_id' => $conversation->customer_id,
                            'conversation_id' => $conversation->id,
                            'message_id' => $outbound->id,
                            'status' => 'processing',
                            'scheduled_at' => $outbound->sent_at,
                            'context' => ['source_message_id' => $outbound->id],
                        ]);

                        try {
                            $messages->sendText($conversation, $body);
                            $run->update([
                                'status' => 'completed',
                                'executed_at' => now(),
                            ]);
                            $processed++;
                        } catch (\Throwable $exception) {
                            $run->update([
                                'status' => 'failed',
                                'executed_at' => now(),
                                'error_message' => $exception->getMessage(),
                            ]);
                            $this->error("Follow-up failed for run {$run->id}: {$exception->getMessage()}");
                        }
                    }
                });
        }

        $this->info("Processed {$processed} follow-up automation(s).");
        return self::SUCCESS;
    }

    private function delay(array $condition): int
    {
        $value = max(1, (int) ($condition['delay_value'] ?? 24));
        $unit = (string) ($condition['delay_unit'] ?? 'hours');

        return match ($unit) {
            'minutes' => $value * 60,
            'days' => $value * 86400,
            default => $value * 3600,
        };
    }
}
