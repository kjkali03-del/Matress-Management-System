<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use App\Services\ConversationMessageService;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const APP_SECRET = 'test-meta-app-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.whatsapp.app_secret' => self::APP_SECRET,
            'services.whatsapp.verify_token' => 'local-verify-token',
        ]);
    }

    public function test_webhook_verification_returns_the_meta_challenge(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=local-verify-token&hub.challenge=challenge-123')
            ->assertOk()
            ->assertSeeText('challenge-123');
    }

    public function test_webhook_verification_rejects_an_invalid_token(): void
    {
        $this->get('/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=challenge-123')
            ->assertForbidden();
    }

    public function test_webhook_routes_accept_https_requests_with_valid_meta_signature(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => []]],
        ];

        $signature = $this->metaSignature($payload);

        $this->get('https://tunnel.example/webhooks/whatsapp?hub.mode=subscribe&hub.verify_token=local-verify-token&hub.challenge=challenge-https')
            ->assertOk()
            ->assertSeeText('challenge-https');

        $this->postJson(
            'https://tunnel.example/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )
            ->assertOk()
            ->assertJson(['received' => true]);
    }

    public function test_invalid_webhook_payload_is_rejected(): void
    {
        $payload = [
            'object' => 'not-whatsapp',
        ];

        $this->postJson(
            '/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $this->metaSignature($payload)]
        )->assertUnprocessable();
    }

    public function test_webhook_rejects_missing_or_invalid_signature(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [['changes' => []]],
        ];

        $this->postJson('/webhooks/whatsapp', $payload)
            ->assertUnauthorized();

        $this->postJson('/webhooks/whatsapp', $payload, [
            'X-Hub-Signature-256' => 'sha256=incorrect-signature',
        ])->assertUnauthorized();
    }

    public function test_valid_incoming_text_message_creates_customer_conversation_and_message(): void
    {
        $payload = $this->incomingPayload('wamid.incoming-001');

        $this->postJson(
            '/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $this->metaSignature($payload)]
        )
            ->assertOk()
            ->assertJson(['received' => true]);

        $this->assertDatabaseHas('customers', [
            'name' => 'Maya Customer',
            'phone' => '15551234567',
            'provider' => 'whatsapp',
            'provider_customer_id' => '15551234567',
        ]);

        $this->assertDatabaseHas('conversations', [
            'channel' => 'whatsapp',
            'status' => 'open',
        ]);

        $this->assertDatabaseHas('messages', [
            'provider_message_id' => 'wamid.incoming-001',
            'direction' => 'inbound',
            'body' => 'I need help with a mattress.',
            'status' => 'delivered',
        ]);
    }

    public function test_duplicate_webhook_message_is_ignored(): void
    {
        $payload = $this->incomingPayload('wamid.duplicate-001');
        $signature = $this->metaSignature($payload);

        $this->postJson(
            '/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        $this->postJson(
            '/webhooks/whatsapp',
            $payload,
            ['X-Hub-Signature-256' => $signature]
        )->assertOk();

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseCount('conversations', 1);
        $this->assertDatabaseCount('messages', 1);
    }

    public function test_configured_outbound_text_uses_mocked_whatsapp_api_and_stores_provider_id(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'messages' => [['id' => 'wamid.outbound-001']],
            ], 200),
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '15551234567',
            ]))
            ->create();

        $this->actingAs($admin)
            ->post(route('admin.inbox.messages.store', $conversation), [
                'body' => 'Your mattress consultation is confirmed.',
            ])
            ->assertRedirect();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/messages'
                && $request['to'] === '15551234567'
                && $request['text']['body'] === 'Your mattress consultation is confirmed.'
                && $request->hasHeader('Authorization', 'Bearer test-token');
        });

        $this->assertDatabaseHas('messages', [
            'provider_message_id' => 'wamid.outbound-001',
            'status' => 'sent',
            'direction' => 'outbound',
        ]);
    }

    public function test_configured_media_upload_sends_multipart_file_to_whatsapp_and_returns_media_id(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response([
                'id' => 'media-upload-001',
            ], 200),
        ]);

        $service = app(WhatsAppService::class);

        $file = UploadedFile::fake()->create(
            'mattress.jpg',
            100,
            'image/jpeg'
        );

        $response = $service->uploadMedia($file);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/media'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer test-token')
                && str_contains(
                    implode(';', $request->header('Content-Type')),
                    'multipart/form-data'
                )
                && str_contains($request->body(), 'mattress.jpg');
        });

        $this->assertSame(
            'media-upload-001',
            data_get($response, 'id')
        );
    }

    public function test_configured_outbound_image_by_media_id_sends_media_id_to_whatsapp(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.outbound-image-media-001',
                    ],
                ],
            ], 200),
        ]);

        $service = app(WhatsAppService::class);

        $response = $service->sendImageByMediaId(
            '255700000001',
            'media-image-001',
            'Wonder Godoro Point'
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/messages'
                && $request->method() === 'POST'
                && data_get($data, 'messaging_product') === 'whatsapp'
                && data_get($data, 'to') === '255700000001'
                && data_get($data, 'type') === 'image'
                && data_get($data, 'image.id') === 'media-image-001'
                && data_get($data, 'image.caption') === 'Wonder Godoro Point';
        });

        $this->assertSame(
            'wamid.outbound-image-media-001',
            data_get($response, 'messages.0.id')
        );
    }

    public function test_configured_outbound_video_by_media_id_sends_media_id_to_whatsapp(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.outbound-video-media-001',
                    ],
                ],
            ], 200),
        ]);

        $service = app(WhatsAppService::class);

        $response = $service->sendVideoByMediaId(
            '255700000001',
            'media-video-001',
            'Video ya godoro'
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/messages'
                && $request->method() === 'POST'
                && data_get($data, 'messaging_product') === 'whatsapp'
                && data_get($data, 'to') === '255700000001'
                && data_get($data, 'type') === 'video'
                && data_get($data, 'video.id') === 'media-video-001'
                && data_get($data, 'video.caption') === 'Video ya godoro';
        });

        $this->assertSame(
            'wamid.outbound-video-media-001',
            data_get($response, 'messages.0.id')
        );
    }

    public function test_configured_image_file_is_uploaded_sent_and_saved_to_conversation(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response([
                'id' => 'media-image-upload-001',
            ], 200),

            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.outbound-image-file-001',
                    ],
                ],
            ], 200),
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $file = UploadedFile::fake()->create(
            'mattress.jpg',
            100,
            'image/jpeg'
        );

        $message = app(ConversationMessageService::class)->sendImageFile(
            $conversation,
            $file,
            'Wonder Godoro Point'
        );

        $this->assertSame('image', $message->message_type);
        $this->assertSame('media-image-upload-001', $message->media_id);
        $this->assertSame('image/jpeg', $message->media_mime_type);
        $this->assertSame('mattress.jpg', $message->media_filename);
        $this->assertSame('Wonder Godoro Point', $message->media_caption);
        $this->assertSame('wamid.outbound-image-file-001', $message->provider_message_id);
        $this->assertSame('sent', $message->status);
        $this->assertNull($message->media_url);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/media';
        });

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/messages'
                && data_get($data, 'to') === '255700000001'
                && data_get($data, 'type') === 'image'
                && data_get($data, 'image.id') === 'media-image-upload-001'
                && data_get($data, 'image.caption') === 'Wonder Godoro Point';
        });

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'image',
            'provider_message_id' => 'wamid.outbound-image-file-001',
            'media_id' => 'media-image-upload-001',
            'media_mime_type' => 'image/jpeg',
            'media_filename' => 'mattress.jpg',
            'media_caption' => 'Wonder Godoro Point',
            'media_url' => null,
            'status' => 'sent',
        ]);
    }

    public function test_configured_video_file_is_uploaded_sent_and_saved_to_conversation(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response([
                'id' => 'media-video-upload-001',
            ], 200),

            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.outbound-video-file-001',
                    ],
                ],
            ], 200),
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $file = UploadedFile::fake()->create(
            'mattress-demo.mp4',
            500,
            'video/mp4'
        );

        $message = app(ConversationMessageService::class)->sendVideoFile(
            $conversation,
            $file,
            'Video ya godoro'
        );

        $this->assertSame('video', $message->message_type);
        $this->assertSame('media-video-upload-001', $message->media_id);
        $this->assertSame('video/mp4', $message->media_mime_type);
        $this->assertSame('mattress-demo.mp4', $message->media_filename);
        $this->assertSame('Video ya godoro', $message->media_caption);
        $this->assertSame('wamid.outbound-video-file-001', $message->provider_message_id);
        $this->assertSame('sent', $message->status);
        $this->assertNull($message->media_url);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/media';
        });

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/messages'
                && data_get($data, 'to') === '255700000001'
                && data_get($data, 'type') === 'video'
                && data_get($data, 'video.id') === 'media-video-upload-001'
                && data_get($data, 'video.caption') === 'Video ya godoro';
        });

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'video',
            'provider_message_id' => 'wamid.outbound-video-file-001',
            'media_id' => 'media-video-upload-001',
            'media_mime_type' => 'video/mp4',
            'media_filename' => 'mattress-demo.mp4',
            'media_caption' => 'Video ya godoro',
            'media_url' => null,
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_send_image_through_inbox_route(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response([
                'id' => 'media-controller-image-001',
            ], 200),

            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.controller-image-001',
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $file = UploadedFile::fake()->create(
            'wonder-godoro.jpg',
            100,
            'image/jpeg'
        );

        $this->actingAs($admin)
            ->post(
                route('admin.inbox.messages.image', $conversation),
                [
                    'image' => $file,
                    'caption' => 'Wonder Godoro Point',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'image',
            'media_id' => 'media-controller-image-001',
            'media_filename' => 'wonder-godoro.jpg',
            'media_caption' => 'Wonder Godoro Point',
            'provider_message_id' => 'wamid.controller-image-001',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_send_video_through_inbox_route(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/media' => Http::response([
                'id' => 'media-controller-video-001',
            ], 200),

            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.controller-video-001',
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $file = UploadedFile::fake()->create(
            'wonder-godoro-demo.mp4',
            500,
            'video/mp4'
        );

        $this->actingAs($admin)
            ->post(
                route('admin.inbox.messages.video', $conversation),
                [
                    'video' => $file,
                    'caption' => 'Video ya godoro',
                ]
            )
            ->assertRedirect();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'video',
            'media_id' => 'media-controller-video-001',
            'media_filename' => 'wonder-godoro-demo.mp4',
            'media_caption' => 'Video ya godoro',
            'provider_message_id' => 'wamid.controller-video-001',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_send_location_through_inbox_route(): void
    {
        $this->configureWhatsApp();

        Http::fake([
            'https://graph.facebook.com/*/messages' => Http::response([
                'messages' => [
                    [
                        'id' => 'wamid.location-001',
                    ],
                ],
            ], 200),
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $response = $this->actingAs($admin)
            ->postJson(
                route('admin.inbox.messages.location', $conversation),
                [
                    'latitude' => -6.7924,
                    'longitude' => 39.2083,
                    'name' => 'Wonder Godoro Point',
                    'address' => 'Dar es Salaam, Tanzania',
                ]
            )
            ->assertOk()
            ->assertJson([
                'message' => 'Location sent successfully.',
                'data' => [
                    'direction' => 'outbound',
                    'message_type' => 'location',
                    'location_name' => 'Wonder Godoro Point',
                    'location_address' => 'Dar es Salaam, Tanzania',
                    'status' => 'sent',
                ],
            ]);

        $response->assertJsonPath(
            'data.latitude',
            '-6.7924000'
        );

        $response->assertJsonPath(
            'data.longitude',
            '39.2083000'
        );

        Http::assertSent(function ($request) {
            $data = $request->data();

            return $request->url() === 'https://graph.facebook.com/v21.0/phone-number-001/messages'
                && data_get($data, 'to') === '255700000001'
                && data_get($data, 'type') === 'location'
                && data_get($data, 'location.latitude') === -6.7924
                && data_get($data, 'location.longitude') === 39.2083
                && data_get($data, 'location.name') === 'Wonder Godoro Point'
                && data_get($data, 'location.address') === 'Dar es Salaam, Tanzania';
        });

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'location',
            'provider_message_id' => 'wamid.location-001',
            'location_name' => 'Wonder Godoro Point',
            'location_address' => 'Dar es Salaam, Tanzania',
            'status' => 'sent',
        ]);
    }

    public function test_admin_can_delete_message_from_inbox(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $message = $conversation->messages()->create([
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => 'Message to delete',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'body' => 'Message to delete',
        ]);

        $this->actingAs($admin)
            ->delete(
                route('admin.inbox.messages.destroy', $message)
            )
            ->assertRedirect();

        $this->assertDatabaseMissing('messages', [
            'id' => $message->id,
        ]);
    }

    public function test_messages_endpoint_returns_media_and_location_fields(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'phone' => '255700000001',
            ]))
            ->create();

        $image = $conversation->messages()->create([
            'direction' => 'outbound',
            'message_type' => 'image',
            'body' => 'Image caption',
            'status' => 'sent',
            'media_id' => 'media-image-001',
            'media_mime_type' => 'image/jpeg',
            'media_filename' => 'mattress.jpg',
            'media_caption' => 'Image caption',
        ]);

        $location = $conversation->messages()->create([
            'direction' => 'outbound',
            'message_type' => 'location',
            'body' => null,
            'status' => 'sent',
            'latitude' => -6.7924,
            'longitude' => 39.2083,
            'location_name' => 'Wonder Godoro Point',
            'location_address' => 'Dar es Salaam, Tanzania',
        ]);

        $this->actingAs($admin)
            ->getJson(
                route('admin.inbox.messages', $conversation)
                . '?after_id=0'
            )
            ->assertOk()
            ->assertJsonPath(
                'messages.0.id',
                $image->id
            )
            ->assertJsonPath(
                'messages.0.message_type',
                'image'
            )
            ->assertJsonPath(
                'messages.0.media_id',
                'media-image-001'
            )
            ->assertJsonPath(
                'messages.0.media_filename',
                'mattress.jpg'
            )
            ->assertJsonPath(
                'messages.1.id',
                $location->id
            )
            ->assertJsonPath(
                'messages.1.message_type',
                'location'
            )
            ->assertJsonPath(
                'messages.1.location_name',
                'Wonder Godoro Point'
            )
            ->assertJsonPath(
                'messages.1.location_address',
                'Dar es Salaam, Tanzania'
            );
    }

    private function configureWhatsApp(): void
    {
        config([
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => 'phone-number-001',
            'services.whatsapp.api_version' => 'v21.0',
            'services.whatsapp.base_url' => 'https://graph.facebook.com',
        ]);
    }

    /** @return array<string, mixed> */
    private function incomingPayload(string $providerMessageId): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'value' => [
                        'contacts' => [[
                            'wa_id' => '15551234567',
                            'profile' => ['name' => 'Maya Customer'],
                        ]],
                        'messages' => [[
                            'from' => '15551234567',
                            'id' => $providerMessageId,
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => [
                                'body' => 'I need help with a mattress.',
                            ],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function metaSignature(array $payload): string
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);

        return 'sha256=' . hash_hmac(
            'sha256',
            $body,
            self::APP_SECRET,
        );
    }
}