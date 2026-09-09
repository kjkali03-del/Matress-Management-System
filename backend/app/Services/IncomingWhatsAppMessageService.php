<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

class IncomingWhatsAppMessageService
{
    /** @return array{processed: int, duplicates: int} */
    public function process(array $payload): array
    {
        $processed = 0;
        $duplicates = 0;

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                foreach ($change['value']['messages'] ?? [] as $incoming) {
                    if (($incoming['type'] ?? null) !== 'text' || blank($incoming['id'] ?? null)) {
                        continue;
                    }

                    if (Message::where('provider_message_id', $incoming['id'])->exists()) {
                        $duplicates++;
                        continue;
                    }

                    try {
                        $this->storeTextMessage($incoming, $change['value']['contacts'] ?? []);
                        $processed++;
                    } catch (UniqueConstraintViolationException $exception) {
                        if (! Message::where('provider_message_id', $incoming['id'])->exists()) {
                            throw $exception;
                        }

                        $duplicates++;
                    }
                }
            }
        }

        return compact('processed', 'duplicates');
    }

    private function storeTextMessage(array $incoming, array $contacts): void
    {
        DB::transaction(function () use ($incoming, $contacts): void {
            $phone = (string) ($incoming['from'] ?? '');
            $contact = collect($contacts)->firstWhere('wa_id', $phone) ?? [];

            $customer = Customer::firstOrCreate(
                [
                    'provider' => 'whatsapp',
                    'provider_customer_id' => $phone,
                ],
                [
                    'name' => data_get($contact, 'profile.name', $phone),
                    'phone' => $phone,
                ],
            );

            $conversation = Conversation::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'channel' => 'whatsapp',
                    'status' => 'open',
                ],
            );

            $createdAt = now();
            $conversation->messages()->create([
                'direction' => 'inbound',
                'message_type' => 'text',
                'body' => data_get($incoming, 'text.body'),
                'provider_message_id' => $incoming['id'],
                'status' => 'delivered',
                'sent_at' => $createdAt,
            ]);

            $conversation->update(['last_message_at' => $createdAt]);
        });
    }
}