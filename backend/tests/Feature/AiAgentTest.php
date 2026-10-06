<?php

namespace Tests\Feature;

use App\Models\AiAction;
use App\Models\AiConversationState;
use App\Models\AiFollowUp;
use App\Models\AiSetting;
use App\Events\AiConversationEscalated;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\AI\AiAgentOrchestrator;
use App\Services\AI\AiBusinessTools;
use App\Services\AI\AiProviderInterface;
use App\Services\AI\OpenAiChatCompletionsProvider;
use App\Services\ConversationMessageService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiAgentTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_is_not_created_before_explicit_confirmation_and_is_audited_afterward(): void
    {
        $this->enableAgent();
        $provider = new FakeAiProvider([
            [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'tool-search-catalogue',
                    'name' => 'search_products',
                    'arguments' => ['query' => 'WGP Comfort'],
                ]],
            ],
            [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'tool-turn-confidence',
                    'name' => 'record_conversation_state',
                    'arguments' => [
                        'intent' => 'purchase',
                        'stage' => 'ORDER_REVIEW',
                        'summary' => 'Customer is ready to place an order.',
                        'next_action' => 'Confirm customer details.',
                        'language' => 'en',
                        'confidence' => 0.9,
                    ],
                ]],
            ],
            [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'tool-order-confirmation',
                    'name' => 'request_order_confirmation',
                    'arguments' => [
                        'product_id' => 1,
                        'quantity' => 2,
                        'customer_name' => 'Jane Doe',
                        'location' => 'Dar es Salaam',
                        'area' => 'Kariakoo',
                        'delivery_address' => 'Mtaa wa Uhuru 12',
                    ],
                ]],
            ],
        ]);
        $product = $this->createProduct();
        $provider->responses[2]['tool_calls'][0]['arguments']['product_id'] = $product->id;
        $agent = $this->agent($provider);
        $conversation = Conversation::factory()
            ->for(Customer::factory()->create())
            ->create();
        $details = Message::factory()->for($conversation)->create([
            'body' => 'Mimi ni Jane Doe. Nipo Dar es Salaam, Kariakoo, Mtaa wa Uhuru 12.',
        ]);

        $agent->handle($details);

        $this->assertSame(0, Order::query()->count());
        $state = AiConversationState::query()->where('conversation_id', $conversation->id)->firstOrFail();
        $this->assertSame(
            'AWAITING_CONFIRMATION',
            $state->stage,
            json_encode([
                'state' => $state->toArray(),
                'actions' => AiAction::query()->get(['tool', 'status', 'summary', 'metadata'])->toArray(),
                'provider_calls' => $provider->calls,
            ], JSON_UNESCAPED_UNICODE),
        );

        $confirmation = Message::factory()->for($conversation)->create(['body' => 'Nathibitisha']);
        $agent->handle($confirmation);

        $order = Order::query()->sole();
        $this->assertSame($conversation->customer_id, $order->customer_id);
        $this->assertSame(2, $order->quantity);
        $this->assertSame('pending', $order->status);
        $this->assertSame(3, OrderActivity::query()->where('order_id', $order->id)->count());
        $this->assertDatabaseHas('ai_actions', [
            'order_id' => $order->id,
            'tool' => 'create_confirmed_order',
            'status' => 'completed',
        ]);
        $this->assertSame(
            $order->id,
            app(AiBusinessTools::class)
                ->createConfirmedOrder($conversation, $state->fresh(), $confirmation->id)
                ?->id,
        );
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(3, OrderActivity::query()->where('order_id', $order->id)->count());
        $this->assertSame(3, $provider->calls);
    }

    public function test_customer_details_are_not_saved_when_not_explicitly_present_in_messages(): void
    {
        $conversation = Conversation::factory()
            ->for(Customer::factory()->create(['name' => 'WhatsApp Profile']))
            ->create();
        Message::factory()->for($conversation)->create(['body' => 'My name is Janet Doe. I would like to see a mattress.']);

        $result = app(AiBusinessTools::class)->execute(
            'update_customer_details',
            ['name' => 'Invented Customer'],
            $conversation,
            AiConversationState::query()->create([
                'conversation_id' => $conversation->id,
                'status' => 'active',
                'stage' => 'NEW',
                'context' => [],
            ]),
            1,
        );

        $this->assertSame(['name'], json_decode($result, true)['missing_customer_details']);
        $this->assertSame('WhatsApp Profile', $conversation->customer->fresh()->name);

        $result = app(AiBusinessTools::class)->execute(
            'update_customer_details',
            ['name' => 'Jane Doe'],
            $conversation,
            AiConversationState::query()->where('conversation_id', $conversation->id)->firstOrFail(),
            1,
        );

        $this->assertSame(['name'], json_decode($result, true)['missing_customer_details']);
        $this->assertSame('WhatsApp Profile', $conversation->customer->fresh()->name);
    }

    public function test_admin_can_load_ai_control_center_and_conversation_monitor(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conversation = Conversation::factory()->for(Customer::factory()->create())->create();

        $this->actingAs($admin)
            ->get(route('admin.ai.index'))
            ->assertOk()
            ->assertSeeText('AI Agent Control Center');

        $this->actingAs($admin)
            ->get(route('admin.ai.conversations.show', $conversation))
            ->assertOk()
            ->assertSeeText('Conversation Monitor');
    }

    public function test_opt_out_is_persisted_and_scheduled_follow_ups_are_cancelled_when_ai_is_off(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($customer)->create();
        $source = Message::factory()->for($conversation)->create(['body' => 'Tell me about delivery.']);
        $stop = Message::factory()->for($conversation)->create(['body' => 'STOP']);
        $state = AiConversationState::query()->create([
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'stage' => 'DISCOVERY',
            'context' => [],
        ]);
        $followUp = AiFollowUp::query()->create([
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'message_id' => $source->id,
            'status' => 'scheduled',
            'attempt' => 1,
            'scheduled_at' => now()->addHour(),
            'idempotency_key' => 'test-follow-up-' . $conversation->id,
        ]);

        config(['ai.enabled' => false]);
        $this->agent(new FakeAiProvider())->handle($stop);

        $this->assertTrue($state->fresh()->context['opted_out']);
        $this->assertSame('cancelled', $followUp->fresh()->status);
        $this->assertSame(0, Message::query()->where('direction', 'outbound')->count());
    }

    public function test_low_confidence_turn_escalates_instead_of_sending_an_unverified_answer(): void
    {
        $this->enableAgent();
        $provider = new FakeAiProvider([
            [
                'content' => null,
                'tool_calls' => [[
                    'id' => 'tool-state',
                    'name' => 'record_conversation_state',
                    'arguments' => [
                        'intent' => 'unknown',
                        'stage' => 'DISCOVERY',
                        'summary' => 'Customer intent is unclear.',
                        'next_action' => 'Ask an advisor to review.',
                        'language' => 'en',
                        'confidence' => 0.1,
                        'discussed_product_ids' => [],
                    ],
                ]],
            ],
        ]);
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->for($conversation)->create(['body' => 'Something unclear']);

        $this->agent($provider)->handle($message);

        $this->assertDatabaseHas('ai_conversation_states', [
            'conversation_id' => $conversation->id,
            'status' => 'escalated',
            'confidence' => 0.1,
        ]);
        $this->assertDatabaseHas('ai_escalations', [
            'conversation_id' => $conversation->id,
            'status' => 'open',
            'reason' => 'missing_or_low_confidence',
        ]);
        $this->assertSame(1, Message::query()->where('direction', 'outbound')->count());
    }

    public function test_turn_without_current_confidence_is_escalated_instead_of_answered(): void
    {
        $this->enableAgent();
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->for($conversation)->create(['body' => 'Please tell me about your mattresses.']);

        $this->agent(new FakeAiProvider([
            ['content' => 'Our mattress is TSh 10,000.', 'tool_calls' => []],
        ]))->handle($message);

        $this->assertDatabaseHas('ai_conversation_states', [
            'conversation_id' => $conversation->id,
            'status' => 'escalated',
        ]);
        $this->assertDatabaseHas('ai_escalations', [
            'conversation_id' => $conversation->id,
            'reason' => 'missing_or_low_confidence',
            'status' => 'open',
        ]);
        $this->assertDatabaseMissing('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'body' => 'Our mattress is TSh 10,000.',
        ]);
    }

    public function test_failure_diagnostic_sanitizes_exception_details_without_interrupting_fallback(): void
    {
        $this->enableAgent();
        config(['ai.api_key' => 'test-provider-secret']);

        $conversation = Conversation::factory()->create();
        $message = Message::factory()->for($conversation)->create([
            'body' => 'Nataka kununua godoro.',
        ]);
        $exception = new \RuntimeException('Provider rejected credential test-provider-secret');
        $loggedContext = null;

        $provider = \Mockery::mock(AiProviderInterface::class);
        $provider->shouldReceive('isConfigured')->once()->andReturn(true);
        $provider->shouldReceive('complete')->once()->andThrow($exception);
        Log::shouldReceive('warning')
            ->once()
            ->with('WGP AI turn failed.', \Mockery::on(
                static function (array $context) use (&$loggedContext): bool {
                    $loggedContext = $context;

                    return true;
                },
            ));

        (new AiAgentOrchestrator(
            $provider,
            app(AiBusinessTools::class),
            app(ConversationMessageService::class),
        ))->handle($message);

        $this->assertSame(\RuntimeException::class, $loggedContext['exception_class']);
        $this->assertSame('RuntimeException', $loggedContext['exception_type']);
        $this->assertSame('Provider rejected credential [REDACTED]', $loggedContext['exception_message']);
        $this->assertSame($exception->getFile(), $loggedContext['exception_file']);
        $this->assertSame($exception->getLine(), $loggedContext['exception_line']);
        $this->assertNotSame('', $loggedContext['exception_trace']);
        $this->assertStringNotContainsString('test-provider-secret', $loggedContext['exception_message']);
        $this->assertStringNotContainsString('test-provider-secret', $loggedContext['exception_trace']);

        $this->assertDatabaseHas('ai_conversation_states', [
            'conversation_id' => $conversation->id,
            'status' => 'escalated',
        ]);
        $this->assertDatabaseHas('ai_actions', [
            'conversation_id' => $conversation->id,
            'tool' => 'ai_failure_fallback',
            'status' => 'completed',
        ]);
    }

    public function test_admin_takeover_pauses_ai_and_admin_can_return_the_conversation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $conversation = Conversation::factory()->create();
        $state = AiConversationState::query()->create([
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'stage' => 'DISCOVERY',
            'context' => [],
        ]);

        $this->actingAs($admin)
            ->post(route('admin.ai.conversations.take-over', $conversation))
            ->assertRedirect();
        $this->assertSame('human', $state->fresh()->status);

        $this->enableAgent();
        $provider = new FakeAiProvider([
            ['content' => 'Automated reply must not be sent.', 'tool_calls' => []],
        ]);
        $message = Message::factory()->for($conversation)->create(['body' => 'Hello again']);
        $this->agent($provider)->handle($message);
        $this->assertSame(0, $provider->calls);
        $this->assertSame(0, Message::query()->where('direction', 'outbound')->count());

        $this->actingAs($admin)
            ->post(route('admin.ai.conversations.return-to-ai', $conversation))
            ->assertRedirect();
        $this->assertSame('active', $state->fresh()->status);
    }

    public function test_escalation_broadcasts_admin_notification_event(): void
    {
        $this->enableAgent();
        Event::fake([AiConversationEscalated::class]);
        $conversation = Conversation::factory()->create();
        $message = Message::factory()->for($conversation)->create(['body' => 'Please connect me to a human']);

        $this->agent(new FakeAiProvider())->handle($message);

        Event::assertDispatched(AiConversationEscalated::class, fn ($event) => $event->conversationId === $conversation->id);
        $this->assertDatabaseHas('ai_conversation_states', [
            'conversation_id' => $conversation->id,
            'status' => 'escalated',
        ]);
    }

    public function test_catalogue_tool_returns_only_active_products_and_public_database_prices(): void
    {
        $active = $this->createProduct();
        $inactive = $this->createProduct();
        $inactive->update(['is_active' => false]);
        $conversation = Conversation::factory()->create();
        $state = AiConversationState::query()->create([
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'stage' => 'PRODUCT_SEARCH',
            'context' => [],
        ]);

        $result = app(AiBusinessTools::class)->execute(
            'search_products',
            ['query' => 'WGP Comfort'],
            $conversation,
            $state,
            1,
        );
        $products = json_decode($result, true)['products'];

        $this->assertCount(1, $products);
        $this->assertSame($active->id, $products[0]['id']);
        $this->assertEquals(250000, $products[0]['price']);
        $this->assertArrayNotHasKey('cost_price', $products[0]);
    }

    public function test_product_image_tool_sends_the_catalogue_image_after_lookup(): void
    {
        $this->enableAgent();
        Storage::fake('public');
        $product = $this->createProduct();
        $product->update(['image_path' => 'catalogue/wgp-comfort.png']);
        Storage::disk('public')->put($product->image_path, 'verified catalogue image');
        $conversation = Conversation::factory()
            ->for(Customer::factory()->create(['phone' => '255700000001']))
            ->create();
        $state = AiConversationState::query()->create([
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'stage' => 'IMAGE_REQUEST',
            'context' => [],
        ]);

        app(AiBusinessTools::class)->execute(
            'search_products',
            ['query' => $product->name],
            $conversation,
            $state,
            1,
        );
        $result = app(AiBusinessTools::class)->execute(
            'send_product_image',
            ['product_id' => $product->id],
            $conversation,
            $state->fresh(),
            1,
        );

        $this->assertSame('Sent the exact active catalogue image.', json_decode($result, true)['summary']);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'image',
            'media_filename' => 'wgp-comfort.png',
        ]);
        Http::assertSent(fn ($request) => data_get($request->data(), 'image.link') === url('/storage/catalogue/wgp-comfort.png'));
    }

    public function test_order_status_tool_only_returns_the_current_whatsapp_customers_orders(): void
    {
        $conversation = Conversation::factory()
            ->for(Customer::factory()->create())
            ->create();
        $otherCustomerOrder = Order::query()->create([
            'order_number' => 'WGP-OTHER-CUSTOMER',
            'customer_id' => Customer::factory()->create()->id,
            'product_name' => 'Private order',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_amount' => 1000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_status' => 'pending',
            'ordered_at' => now(),
        ]);

        $result = app(AiBusinessTools::class)->execute(
            'check_order_status',
            ['order_number' => $otherCustomerOrder->order_number],
            $conversation,
            AiConversationState::query()->create([
                'conversation_id' => $conversation->id,
                'status' => 'active',
                'stage' => 'POST_SALE',
                'context' => [],
            ]),
            1,
        );

        $this->assertNull(json_decode($result, true)['order']);
    }

    public function test_follow_up_is_opt_in_limited_idempotent_and_respects_quiet_hours(): void
    {
        $this->enableAgent();
        AiSetting::put('auto_follow_up', 'true');
        AiSetting::put('maximum_follow_ups', '1');
        AiSetting::put('follow_up_delay_minutes', '5');
        $conversation = Conversation::factory()
            ->for(Customer::factory()->create(['phone' => '255700000001']))
            ->create();
        $state = AiConversationState::query()->create([
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'stage' => 'FOLLOW_UP',
            'language' => 'en',
            'context' => ['follow_up_opt_in' => true],
        ]);
        $source = Message::factory()->for($conversation)->create(['body' => 'Please remind me about the mattress.']);

        $tools = app(AiBusinessTools::class);
        $tools->execute('schedule_follow_up', ['reason' => 'Customer requested a reminder.'], $conversation, $state, $source->id);
        $tools->execute('schedule_follow_up', ['reason' => 'Customer requested a reminder.'], $conversation, $state, $source->id);
        $this->assertSame(1, AiFollowUp::query()->count());

        $secondConversation = Conversation::factory()->for($conversation->customer)->create();
        $secondState = AiConversationState::query()->create([
            'conversation_id' => $secondConversation->id,
            'status' => 'active',
            'stage' => 'FOLLOW_UP',
            'context' => ['follow_up_opt_in' => true],
        ]);
        $secondMessage = Message::factory()->for($secondConversation)->create(['body' => 'Please remind me next week.']);
        $tools->execute(
            'schedule_follow_up',
            ['reason' => 'Customer requested another reminder.'],
            $secondConversation,
            $secondState,
            $secondMessage->id,
        );
        $this->assertSame(1, AiFollowUp::query()->count());

        Carbon::setTestNow(Carbon::parse('2026-10-04 19:00:00', 'UTC'));
        try {
            $followUp = AiFollowUp::query()->firstOrFail();
            $followUp->update(['scheduled_at' => now()->subMinute()]);

            Artisan::call('ai:process-follow-ups');
            $followUp->refresh();
            $this->assertSame('scheduled', $followUp->status);
            $this->assertSame('2026-10-05 05:00:00', $followUp->scheduled_at->utc()->format('Y-m-d H:i:s'));
            $this->assertSame(0, Message::query()->where('direction', 'outbound')->count());

            Carbon::setTestNow(Carbon::parse('2026-10-05 05:00:00', 'UTC'));
            Artisan::call('ai:process-follow-ups');
            $this->assertSame('sent', $followUp->fresh()->status);
            $this->assertSame(1, Message::query()->where('direction', 'outbound')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_openai_compatible_provider_uses_environment_configuration_and_parses_tool_calls(): void
    {
        config([
            'ai.enabled' => true,
            'ai.api_key' => 'test-provider-secret',
            'ai.base_url' => 'https://provider.example/v1',
            'ai.model' => 'configured-model',
            'ai.timeout' => 60,
        ]);
        Http::fake([
            'https://provider.example/v1/chat/completions' => Http::response([
                'choices' => [[
                    'finish_reason' => 'tool_calls',
                    'message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call-1',
                            'function' => [
                                'name' => 'search_products',
                                'arguments' => '{"query":"comfort","size":"6x6"}',
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $provider = new OpenAiChatCompletionsProvider();
        $result = $provider->complete([['role' => 'user', 'content' => 'Find a mattress']], []);

        $this->assertTrue($provider->isConfigured());
        $this->assertSame('search_products', $result['tool_calls'][0]['name']);
        $this->assertSame(['query' => 'comfort', 'size' => '6x6'], $result['tool_calls'][0]['arguments']);
        Http::assertSent(fn ($request) => $request->url() === 'https://provider.example/v1/chat/completions'
            && $request['model'] === 'configured-model'
            && $request->hasHeader('Authorization', 'Bearer test-provider-secret'));
    }

    public function test_provider_rejects_malformed_tool_arguments_without_returning_a_tool(): void
    {
        config([
            'ai.enabled' => true,
            'ai.api_key' => 'test-provider-secret',
            'ai.base_url' => 'https://provider.example/v1',
            'ai.model' => 'configured-model',
        ]);
        Http::fake([
            'https://provider.example/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => null,
                        'tool_calls' => [[
                            'id' => 'call-invalid',
                            'function' => [
                                'name' => 'request_order_confirmation',
                                'arguments' => '{"quantity":',
                            ],
                        ]],
                    ],
                ]],
            ]),
        ]);

        $this->expectException(\RuntimeException::class);
        (new OpenAiChatCompletionsProvider())->complete([['role' => 'user', 'content' => 'Place an order']], []);
    }

    public function test_product_image_tool_requires_product_to_be_loaded_from_catalogue_first(): void
    {
        $product = $this->createProduct();
        $conversation = Conversation::factory()->create();
        $state = AiConversationState::query()->create([
            'conversation_id' => $conversation->id,
            'status' => 'active',
            'stage' => 'IMAGE_REQUEST',
            'context' => [],
        ]);

        $result = app(AiBusinessTools::class)->execute(
            'send_product_image',
            ['product_id' => $product->id],
            $conversation,
            $state,
            1,
        );

        $this->assertTrue(json_decode($result, true)['catalogue_lookup_required']);
        $this->assertSame(0, Message::query()->where('direction', 'outbound')->count());
    }

    private function enableAgent(): void
    {
        config([
            'ai.enabled' => true,
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.phone_number_id' => 'phone-number-001',
            'services.whatsapp.api_version' => 'v21.0',
            'services.whatsapp.base_url' => 'https://graph.facebook.com',
        ]);
        $messageSequence = 0;
        Http::fake([
            'https://graph.facebook.com/*' => function () use (&$messageSequence) {
                $messageSequence++;

                return Http::response([
                    'messages' => [['id' => 'wamid.ai-agent-test-' . $messageSequence]],
                ], 200);
            },
        ]);
        AiSetting::put('ai_enabled', 'true');
        AiSetting::put('auto_reply_enabled', 'true');
        AiSetting::put('auto_order_creation', 'true');
        AiSetting::put('escalation_threshold', '0.55');
    }

    private function agent(FakeAiProvider $provider): AiAgentOrchestrator
    {
        return new AiAgentOrchestrator(
            $provider,
            app(AiBusinessTools::class),
            app(ConversationMessageService::class),
        );
    }

    private function createProduct(): Product
    {
        $category = ProductCategory::query()->create([
            'name' => 'Test mattresses ' . bin2hex(random_bytes(4)),
            'is_active' => true,
        ]);

        return Product::query()->create([
            'product_category_id' => $category->id,
            'name' => 'WGP Comfort',
            'sku' => 'WGP-COMFORT-' . bin2hex(random_bytes(4)),
            'size' => '6x6',
            'price' => 250000,
            'cost_price' => 160000,
            'stock_quantity' => 10,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
    }
}

final class FakeAiProvider implements AiProviderInterface
{
    public int $calls = 0;

    /** @param array<int, array<string, mixed>> $responses */
    public function __construct(public array $responses = []) {}

    public function isConfigured(): bool
    {
        return true;
    }

    public function complete(array $messages, array $tools): array
    {
        $this->calls++;

        return array_shift($this->responses) ?? [
            'content' => 'I will connect you with a WGP advisor.',
            'tool_calls' => [],
        ];
    }
}
