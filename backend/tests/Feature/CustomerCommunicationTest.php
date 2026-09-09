<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TestCase;

class CustomerCommunicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_conversation_and_message_relationships_work(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($customer)->create();
        $message = Message::factory()->for($conversation)->create([
            'provider_message_id' => 'wamid.test-001',
        ]);

        $this->assertTrue($customer->conversations->contains($conversation));
        $this->assertTrue($conversation->customer->is($customer));
        $this->assertTrue($conversation->messages->contains($message));
        $this->assertTrue($message->conversation->is($conversation));
    }

    public function test_provider_message_id_is_unique_for_webhook_idempotency(): void
    {
        $conversation = Conversation::factory()->create();

        Message::factory()->for($conversation)->create([
            'provider_message_id' => 'wamid.duplicate',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Message::factory()->for($conversation)->create([
            'provider_message_id' => 'wamid.duplicate',
        ]);
    }

    public function test_provider_customer_identifier_is_unique_per_provider(): void
    {
        Customer::factory()->create([
            'provider' => 'whatsapp',
            'provider_customer_id' => '15551234567',
        ]);

        $this->expectException(UniqueConstraintViolationException::class);

        Customer::factory()->create([
            'provider' => 'whatsapp',
            'provider_customer_id' => '15551234567',
        ]);
    }

    public function test_conversations_require_a_customer_and_messages_require_a_conversation(): void
    {
        $this->expectException(QueryException::class);

        Conversation::factory()->create([
            'customer_id' => 999999,
        ]);
    }

    public function test_deleting_a_customer_cascades_to_conversations_and_messages(): void
    {
        $customer = Customer::factory()->create();
        $conversation = Conversation::factory()->for($customer)->create();
        $message = Message::factory()->for($conversation)->create();

        $customer->delete();

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('messages', ['id' => $message->id]);
    }
}