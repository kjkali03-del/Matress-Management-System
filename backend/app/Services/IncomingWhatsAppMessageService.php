<?php

namespace App\Services;

use App\Events\NewWhatsAppMessage;
use App\Jobs\ProcessAiConversation;
use App\Models\Call;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IncomingWhatsAppMessageService
{
    public function __construct(
        private readonly AutomationService $automationService
    ) {}

    /** @return array{processed: int, duplicates: int} */
    public function process(array $payload): array
    {
        $processed = 0;
        $duplicates = 0;

        foreach ($payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $value = $change['value'] ?? [];

                foreach ($value['statuses'] ?? [] as $status) {
                    if ($this->storeMessageStatus($status)) {
                        $processed++;
                    }
                }

                foreach ($value['messages'] ?? [] as $incoming) {
                    if (blank($incoming['id'] ?? null)) {
                        continue;
                    }

                    if (Message::where(
                        'provider_message_id',
                        $incoming['id']
                    )->exists()) {
                        $duplicates++;
                        continue;
                    }

                    try {
                        $message = $this->storeIncomingMessage(
                            $incoming,
                            $value['contacts'] ?? [],
                        );

                        /*
                         * Run automations only after the inbound message
                         * has been successfully committed to the database.
                         *
                         * AutomationService handles its own failures so
                         * an automation problem does not break webhook
                         * processing.
                         */
                        $this->automationService->handleIncomingMessage(
                            $message
                        );

                        if (filter_var(config('ai.enabled'), FILTER_VALIDATE_BOOL)) {
                            ProcessAiConversation::dispatch($message->id)->afterCommit();
                        }

                        /*
                         * Broadcast the new WhatsApp message to the
                         * administrator workspace in real time.
                         */
                        NewWhatsAppMessage::dispatch($message);

                        $processed++;
                    } catch (UniqueConstraintViolationException $exception) {
                        if (
                            ! Message::where(
                                'provider_message_id',
                                $incoming['id']
                            )->exists()
                        ) {
                            throw $exception;
                        }

                        $duplicates++;
                    }
                }

                foreach ($value['calls'] ?? [] as $incomingCall) {
                    if (blank($incomingCall['id'] ?? null)) {
                        continue;
                    }

                    $result = $this->storeCall(
                        $incomingCall,
                        $value['contacts'] ?? [],
                    );

                    if ($result === 'duplicate') {
                        $duplicates++;
                    } else {
                        $processed++;
                    }
                }
            }
        }

        return compact('processed', 'duplicates');
    }

    private function storeMessageStatus(array $status): bool
    {
        $providerMessageId = (string) ($status['id'] ?? '');
        if ($providerMessageId === '') {
            return false;
        }

        $message = Message::where('provider_message_id', $providerMessageId)->first();
        if (! $message) {
            return false;
        }

        $state = strtolower((string) ($status['status'] ?? ''));
        $attributes = [
            'status' => match ($state) {
                'sent' => 'sent',
                'delivered' => 'delivered',
                'read' => 'read',
                'failed' => 'failed',
                default => $message->status,
            },
        ];

        if ($state === 'read') {
            $attributes['read_at'] = now();
        }

        if ($state === 'failed') {
            $attributes['failed_at'] = now();
            $attributes['error_code'] = (string) (data_get($status, 'errors.0.code') ?? '');
            $attributes['error_message'] = (string) (data_get($status, 'errors.0.title') ?? data_get($status, 'errors.0.message') ?? 'WhatsApp message failed.');
        }

        $message->update($attributes);
        return true;
    }

    private function storeIncomingMessage(
        array $incoming,
        array $contacts
    ): Message {
        return DB::transaction(function () use (
            $incoming,
            $contacts
        ): Message {
            $phone = (string) ($incoming['from'] ?? '');

            $contact = collect($contacts)->firstWhere(
                'wa_id',
                $phone
            ) ?? [];

            $customer = Customer::firstOrCreate(
                [
                    'provider' => 'whatsapp',
                    'provider_customer_id' => $phone,
                ],
                [
                    'name' => data_get(
                        $contact,
                        'profile.name',
                        $phone
                    ),
                    'phone' => $phone,
                ],
            );

            $customer->update([
                'last_contact_at' => now(),
            ]);

            $conversation = Conversation::firstOrCreate(
                [
                    'customer_id' => $customer->id,
                    'channel' => 'whatsapp',
                    'status' => 'open',
                ],
            );

            $createdAt = now();
            $type = (string) ($incoming['type'] ?? 'text');
            $body = $this->resolveIncomingBody($incoming, $type);
            $media = $this->resolveIncomingMedia($incoming, $type);
            $location = $this->resolveIncomingLocation($incoming, $type);

            $message = $conversation->messages()->create([
                'direction' => 'inbound',
                'message_type' => $type,
                'body' => $body,
                'provider_message_id' => $incoming['id'],
                'status' => 'delivered',
                'sent_at' => $createdAt,
                ...$media,
                ...$location,
            ]);

            $conversation->update([
                'last_message_at' => $createdAt,
            ]);

            return $message;
        });
    }


    private function resolveIncomingBody(array $incoming, string $type): ?string
    {
        return match ($type) {
            'text' => data_get($incoming, 'text.body'),
            'image' => data_get($incoming, 'image.caption'),
            'video' => data_get($incoming, 'video.caption'),
            'document' => data_get($incoming, 'document.caption'),
            'audio' => 'Audio message',
            'location' => data_get($incoming, 'location.name')
                ?? data_get($incoming, 'location.address')
                ?? 'Shared location',
            'interactive' => data_get($incoming, 'interactive.button_reply.title')
                ?? data_get($incoming, 'interactive.list_reply.title')
                ?? 'Interactive reply',
            'button' => data_get($incoming, 'button.text'),
            default => 'Incoming WhatsApp message',
        };
    }

    /** @return array<string, mixed> */
    private function resolveIncomingMedia(array $incoming, string $type): array
    {
        $media = data_get($incoming, $type, []);
        if (! is_array($media) || ! in_array($type, ['image', 'video', 'audio', 'document'], true)) {
            return [];
        }

        return [
            'media_id' => data_get($media, 'id'),
            'media_mime_type' => data_get($media, 'mime_type'),
            'media_filename' => data_get($media, 'filename'),
            'media_caption' => data_get($media, 'caption'),
        ];
    }

    /** @return array<string, mixed> */
    private function resolveIncomingLocation(array $incoming, string $type): array
    {
        if ($type !== 'location') {
            return [];
        }

        return [
            'latitude' => data_get($incoming, 'location.latitude'),
            'longitude' => data_get($incoming, 'location.longitude'),
            'location_name' => data_get($incoming, 'location.name'),
            'location_address' => data_get($incoming, 'location.address'),
        ];
    }

    private function storeCall(
        array $incomingCall,
        array $contacts
    ): string {
        $providerCallId = (string) $incomingCall['id'];

        $direction = strtoupper(
            (string) ($incomingCall['direction'] ?? 'USER_INITIATED')
        );

        $existingCall = Call::where(
            'provider_call_id',
            $providerCallId
        )
            ->where('direction', $direction)
            ->first();

        $phone = $this->resolveCallPhone(
            $incomingCall,
            $direction
        );

        if (blank($phone)) {
            return $existingCall ? 'duplicate' : 'processed';
        }

        $contact = collect($contacts)->firstWhere(
            'wa_id',
            $phone
        ) ?? [];

        return DB::transaction(
            function () use (
                $incomingCall,
                $existingCall,
                $providerCallId,
                $direction,
                $phone,
                $contact
            ): string {
                $customer = Customer::firstOrCreate(
                    [
                        'provider' => 'whatsapp',
                        'provider_customer_id' => $phone,
                    ],
                    [
                        'name' => data_get(
                            $contact,
                            'profile.name',
                            $phone
                        ),
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

                $event = strtolower(
                    (string) ($incomingCall['event'] ?? 'ringing')
                );

                $attributes = [
                    'conversation_id' => $conversation->id,
                    'customer_id' => $customer->id,
                    'provider_call_id' => $providerCallId,
                    'direction' => $direction,
                    'status' => $this->resolveCallStatus(
                        $incomingCall
                    ),
                ];

                $sessionSdp = data_get(
                    $incomingCall,
                    'session.sdp'
                );

                /*
                 * Only update session_sdp when Meta actually sends
                 * a new SDP. This prevents a terminate webhook from
                 * accidentally clearing the SDP offer.
                 */
                if (filled($sessionSdp)) {
                    $attributes['session_sdp'] = $sessionSdp;
                }

                $this->applyCallTimestamps(
                    $attributes,
                    $incomingCall,
                    $event,
                );

                if ($existingCall) {
                    $existingCall->update($attributes);

                    return 'processed';
                }

                try {
                    Call::create($attributes);
                } catch (UniqueConstraintViolationException $exception) {
                    $duplicate = Call::where(
                        'provider_call_id',
                        $providerCallId
                    )
                        ->where('direction', $direction)
                        ->first();

                    if (! $duplicate) {
                        throw $exception;
                    }

                    $duplicate->update($attributes);
                }

                $conversation->update([
                    'last_message_at' => now(),
                ]);

                return 'processed';
            }
        );
    }

    private function resolveCallPhone(
        array $incomingCall,
        string $direction
    ): string {
        /*
         * USER_INITIATED:
         * customer -> business
         *
         * BUSINESS_INITIATED:
         * business -> customer
         */
        if ($direction === 'BUSINESS_INITIATED') {
            return (string) (
                $incomingCall['to']
                ?? $incomingCall['from']
                ?? ''
            );
        }

        if ($direction === 'USER_INITIATED') {
            return (string) (
                $incomingCall['from']
                ?? $incomingCall['to']
                ?? ''
            );
        }

        return (string) (
            $incomingCall['from']
            ?? $incomingCall['to']
            ?? ''
        );
    }

    private function resolveCallStatus(
        array $incomingCall
    ): string {
        /*
         * The webhook event is more authoritative for call lifecycle
         * than the optional status field.
         */
        $event = strtolower(
            (string) ($incomingCall['event'] ?? '')
        );

        if ($event !== '') {
            $eventStatus = match ($event) {
                'connect' => 'ringing',
                'pre_accept' => 'ringing',
                'accept' => 'answered',
                'accepted' => 'answered',
                'reject' => 'rejected',
                'rejected' => 'rejected',
                'terminate' => 'ended',
                'terminated' => 'ended',
                'missed' => 'missed',
                default => null,
            };

            if ($eventStatus !== null) {
                return $eventStatus;
            }
        }

        $status = strtolower(
            (string) (
                $incomingCall['status']
                ?? 'ringing'
            )
        );

        return match ($status) {
            'connect' => 'ringing',
            'pre_accept' => 'ringing',
            'accept' => 'answered',
            'accepted' => 'answered',
            'reject' => 'rejected',
            'rejected' => 'rejected',
            'terminate' => 'ended',
            'terminated' => 'ended',
            'completed' => 'ended',
            'missed' => 'missed',
            default => mb_substr($status, 0, 30),
        };
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function applyCallTimestamps(
        array &$attributes,
        array $incomingCall,
        string $event
    ): void {
        $timestamp = $this->parseTimestamp(
            data_get($incomingCall, 'timestamp')
        );

        $startedAt = $this->parseTimestamp(
            data_get($incomingCall, 'start_time')
            ?? data_get($incomingCall, 'started_at')
        );

        $answeredAt = $this->parseTimestamp(
            data_get($incomingCall, 'answer_time')
            ?? data_get($incomingCall, 'answered_at')
        );

        $endedAt = $this->parseTimestamp(
            data_get($incomingCall, 'end_time')
            ?? data_get($incomingCall, 'ended_at')
        );

        if ($startedAt) {
            $attributes['started_at'] = $startedAt;
        }

        if ($answeredAt) {
            $attributes['answered_at'] = $answeredAt;
        }

        if ($endedAt) {
            $attributes['ended_at'] = $endedAt;
        }

        if (
            $event === 'connect'
            && ! isset($attributes['started_at'])
        ) {
            $attributes['started_at'] = $timestamp ?? now();
        }

        if (
            in_array($event, ['accept', 'accepted'], true)
            && ! isset($attributes['answered_at'])
        ) {
            $attributes['answered_at'] = $timestamp ?? now();
        }

        if (
            in_array(
                $event,
                [
                    'terminate',
                    'terminated',
                    'reject',
                    'rejected',
                ],
                true
            )
            && ! isset($attributes['ended_at'])
        ) {
            $attributes['ended_at'] = $timestamp ?? now();
        }

        $duration = data_get(
            $incomingCall,
            'duration'
        );

        if (is_numeric($duration)) {
            $attributes['duration_seconds'] = max(
                0,
                (int) $duration
            );
        }
    }

    private function parseTimestamp(
        mixed $value
    ): ?Carbon {
        if (blank($value)) {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestamp(
                (int) $value
            );
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Throwable) {
            return null;
        }
    }
}