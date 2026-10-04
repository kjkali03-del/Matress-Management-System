<?php

namespace App\Console\Commands;

use App\Models\AiAction;
use App\Models\AiConversationState;
use App\Models\AiFollowUp;
use App\Models\AiSetting;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Services\ConversationMessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProcessAiFollowUps extends Command
{
    protected $signature = 'ai:process-follow-ups {--dry-run : List due follow-ups without sending}';

    protected $description = 'Send due, opted-in WGP AI-agent follow-ups.';

    public function handle(ConversationMessageService $messages): int
    {
        if (
            ! filter_var(config('ai.enabled'), FILTER_VALIDATE_BOOL)
            || AiSetting::get('ai_enabled', 'false') !== 'true'
            || AiSetting::get('auto_follow_up', 'false') !== 'true'
        ) {
            $this->info('AI follow-ups are disabled.');

            return self::SUCCESS;
        }

        $processed = 0;
        AiFollowUp::query()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->with(['conversation.customer', 'message'])
            ->orderBy('scheduled_at')
            ->limit(100)
            ->get()
            ->each(function (AiFollowUp $followUp) use ($messages, &$processed): void {
                if ($this->option('dry-run')) {
                    $this->line('Due follow-up ' . $followUp->id . ' for customer ' . $followUp->customer_id);
                    $processed++;

                    return;
                }

                try {
                    $result = $this->sendOne($followUp, $messages);
                    if ($result) {
                        $processed++;
                    }
                } catch (\Throwable $exception) {
                    $followUp->refresh()->update([
                        'status' => 'failed',
                        'metadata' => array_merge($followUp->metadata ?? [], [
                            'error_type' => class_basename($exception),
                        ]),
                    ]);
                    $this->error('Follow-up ' . $followUp->id . ' failed: ' . class_basename($exception));
                }
            });

        $this->info('Processed ' . $processed . ' AI follow-up(s).');

        return self::SUCCESS;
    }

    private function sendOne(AiFollowUp $followUp, ConversationMessageService $messages): bool
    {
        $claimed = DB::transaction(function () use ($followUp): bool {
            $locked = AiFollowUp::query()->whereKey($followUp->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== 'scheduled') {
                return false;
            }

            $state = AiConversationState::query()
                ->where('conversation_id', $locked->conversation_id)
                ->lockForUpdate()
                ->first();
            if (
                ! $state
                || $state->status !== 'active'
                || ($state->context['opted_out'] ?? false) === true
            ) {
                $locked->update(['status' => 'cancelled']);

                return false;
            }

            $sourceMessage = $locked->message;
            $newerInbound = $sourceMessage && Message::query()
                ->where('conversation_id', $locked->conversation_id)
                ->where('direction', 'inbound')
                ->where('id', '>', $sourceMessage->id)
                ->exists();
            $hasNewOrder = $sourceMessage && Order::query()
                ->where('customer_id', $locked->customer_id)
                ->where('created_at', '>', $sourceMessage->created_at)
                ->exists();

            $outsideWhatsAppWindow = ! $sourceMessage
                || ! $sourceMessage->created_at
                || $sourceMessage->created_at->lt(now()->subHours(24));

            if ($newerInbound || $hasNewOrder || $outsideWhatsAppWindow) {
                $locked->update([
                    'status' => 'cancelled',
                    'metadata' => array_merge($locked->metadata ?? [], [
                        'cancellation_reason' => $outsideWhatsAppWindow
                            ? 'whatsapp_24_hour_window_expired'
                            : ($newerInbound ? 'customer_replied' : 'new_order_created'),
                    ]),
                ]);

                return false;
            }

            $quietUntil = $this->quietHoursEnd();
            if ($quietUntil) {
                $locked->update(['scheduled_at' => $quietUntil]);

                return false;
            }

            $locked->update(['status' => 'processing']);

            return true;
        });

        if (! $claimed) {
            return false;
        }

        $followUp->refresh()->load(['conversation.customer', 'message']);
        $state = AiConversationState::query()
            ->where('conversation_id', $followUp->conversation_id)
            ->firstOrFail();
        $product = Product::query()
            ->active()
            ->find(data_get($followUp->metadata, 'product_id'));

        $body = $this->messageBody($state->language, $product?->name);
        try {
            $outbound = $messages->sendText($followUp->conversation, $body);
        } catch (\Throwable $exception) {
            $followUp->update(['status' => 'failed']);
            AiAction::query()->create([
                'conversation_id' => $followUp->conversation_id,
                'customer_id' => $followUp->customer_id,
                'actor' => 'ai',
                'tool' => 'send_ai_follow_up',
                'status' => 'failed',
                'summary' => 'AI follow-up delivery failed.',
                'metadata' => ['follow_up_id' => $followUp->id, 'error_type' => class_basename($exception)],
            ]);

            throw $exception;
        }

        DB::transaction(function () use ($followUp, $outbound): void {
            $locked = AiFollowUp::query()->whereKey($followUp->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'processing') {
                throw new RuntimeException('Follow-up state changed while the message was being sent.');
            }

            $locked->update(['status' => 'sent', 'sent_at' => now()]);
            AiConversationState::query()
                ->where('conversation_id', $locked->conversation_id)
                ->update(['last_ai_message_at' => now()]);
            AiAction::query()->create([
                'conversation_id' => $locked->conversation_id,
                'customer_id' => $locked->customer_id,
                'actor' => 'ai',
                'tool' => 'send_ai_follow_up',
                'status' => 'completed',
                'summary' => 'Sent a scheduled opt-in follow-up.',
                'metadata' => [
                    'follow_up_id' => $locked->id,
                    'outbound_message_id' => $outbound->id,
                ],
            ]);
        });

        return true;
    }

    private function quietHoursEnd(): ?Carbon
    {
        $timezone = (string) config('ai.timezone', 'Africa/Dar_es_Salaam');
        $now = Carbon::now($timezone);
        $start = AiSetting::get('quiet_hours_start', '21:00');
        $end = AiSetting::get('quiet_hours_end', '08:00');
        $time = $now->format('H:i');

        $quiet = $start < $end
            ? $time >= $start && $time < $end
            : $time >= $start || $time < $end;
        if (! $quiet) {
            return null;
        }

        $quietEndsToday = $time < $end;
        $nextAllowed = Carbon::createFromFormat(
            'Y-m-d H:i',
            ($quietEndsToday ? $now->toDateString() : $now->copy()->addDay()->toDateString()) . ' ' . $end,
            $timezone,
        );

        return $nextAllowed?->setTimezone(config('app.timezone', 'UTC'));
    }

    private function messageBody(?string $language, ?string $productName): string
    {
        $name = $productName ? ' kuhusu ' . $productName : '';

        return $language === 'en'
            ? 'Hello! I’m following up' . $name . '. Would you still like help from WGP? Reply STOP if you do not want follow-up messages.'
            : 'Habari 😊 Nilikuwa nakufuatilia' . $name . '. Bado ungependa nikusaidie? Jibu STOP kama hutaki ujumbe wa ufuatiliaji.';
    }
}
