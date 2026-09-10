<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        config([
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => 'phone-number-001',
            'services.whatsapp.base_url' => 'https://graph.facebook.com',
        ]);

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