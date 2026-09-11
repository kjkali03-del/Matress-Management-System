<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_admin_can_access_dashboard_with_inbox_link(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]))
            ->get('/admin')
            ->assertOk()
            ->assertSee('Customer Inbox')
            ->assertSee(route('admin.inbox'));
    }

    public function test_unauthenticated_users_are_redirected_from_the_dashboard(): void
    {
        $this->get('/admin')->assertRedirectToRoute('login');
    }

    public function test_authenticated_non_admin_users_receive_forbidden_from_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_authenticated_admin_can_access_the_inbox(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/inbox')
            ->assertOk()
            ->assertSee('Inbox');
    }

    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get('/admin/inbox')->assertRedirectToRoute('login');
    }

    public function test_authenticated_non_admin_users_cannot_access_the_inbox(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => false]))
            ->get('/admin/inbox')
            ->assertForbidden();
    }

    public function test_conversation_messages_display_chronologically(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $conversation = Conversation::factory()
            ->for(Customer::factory()->create([
                'name' => 'Maya Customer',
                'phone' => '+15551234567',
            ]))
            ->create();

        Message::factory()->for($conversation)->create([
            'body' => 'I need help choosing a mattress.',
            'direction' => 'inbound',
            'created_at' => now()->subMinutes(2),
        ]);

        Message::factory()->for($conversation)->create([
            'body' => 'We would be happy to help.',
            'direction' => 'outbound',
            'created_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($admin)
            ->get('/admin/inbox');

        $response->assertOk()
            ->assertSee('Maya Customer')
            ->assertSee('I need help choosing a mattress.')
            ->assertSee('We would be happy to help.');

        $content = $response->getContent();

        $messageStream = substr(
            $content,
            strpos($content, 'message-day-label')
        );

        $this->assertTrue(
            strpos($messageStream, 'I need help choosing a mattress.')
                < strpos($messageStream, 'We would be happy to help.')
        );
    }

    public function test_opening_a_conversation_marks_unread_inbound_messages_as_read(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $conversation = Conversation::factory()->create();

        $inboundMessage = Message::factory()
            ->for($conversation)
            ->create([
                'direction' => 'inbound',
                'body' => 'Hello, I would like to know the price of a mattress.',
                'read_at' => null,
            ]);

        $outboundMessage = Message::factory()
            ->for($conversation)
            ->create([
                'direction' => 'outbound',
                'body' => 'Hello! We would be happy to help you.',
                'read_at' => null,
            ]);

        $this->assertNull($inboundMessage->fresh()->read_at);
        $this->assertNull($outboundMessage->fresh()->read_at);

        $this->actingAs($admin)
            ->get(route('admin.inbox', [
                'conversation' => $conversation->id,
            ]))
            ->assertOk();

        $this->assertNotNull($inboundMessage->fresh()->read_at);
        $this->assertNull($outboundMessage->fresh()->read_at);
    }

    public function test_customer_notes_can_be_saved(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Maya Customer',
            'phone' => '+255700000001',
            'notes' => 'Customer prefers a medium-firm mattress and requested delivery in Dar es Salaam.',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Maya Customer',
            'notes' => 'Customer prefers a medium-firm mattress and requested delivery in Dar es Salaam.',
        ]);

        $this->assertSame(
            'Customer prefers a medium-firm mattress and requested delivery in Dar es Salaam.',
            $customer->fresh()->notes
        );
    }

    public function test_valid_outbound_message_is_stored(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $conversation = Conversation::factory()->create();

        $conversation->load('customer');

        $whatsapp = Mockery::mock(WhatsAppService::class);

        $whatsapp->shouldReceive('sendText')
            ->once()
            ->with(
                $conversation->customer->phone,
                'Your mattress consultation is confirmed.'
            )
            ->andReturn([]);

        $whatsapp->shouldReceive('isConfigured')
            ->once()
            ->andReturn(false);

        $this->app->instance(
            WhatsAppService::class,
            $whatsapp
        );

        $this->actingAs($admin)
            ->post(
                route(
                    'admin.inbox.messages.store',
                    $conversation
                ),
                [
                    'body' => 'Your mattress consultation is confirmed.',
                ]
            )
            ->assertRedirect(
                route(
                    'admin.inbox',
                    [
                        'conversation' => $conversation->id,
                    ]
                )
            );

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'message_type' => 'text',
            'body' => 'Your mattress consultation is confirmed.',
            'status' => 'sent',
        ]);

        $this->assertNotNull(
            $conversation->fresh()->last_message_at
        );
    }

    public function test_empty_outbound_messages_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $conversation = Conversation::factory()->create();

        $this->actingAs($admin)
            ->from(
                route(
                    'admin.inbox',
                    [
                        'conversation' => $conversation->id,
                    ]
                )
            )
            ->post(
                route(
                    'admin.inbox.messages.store',
                    $conversation
                ),
                [
                    'body' => '',
                ]
            )
            ->assertRedirect(
                route(
                    'admin.inbox',
                    [
                        'conversation' => $conversation->id,
                    ]
                )
            )
            ->assertSessionHasErrors('body');

        $this->assertDatabaseCount('messages', 0);
    }
}