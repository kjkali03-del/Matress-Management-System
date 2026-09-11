<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_image_message_can_store_media_metadata(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->for($conversation)->create([
            'message_type' => 'image',
            'body' => null,
            'media_id' => 'media-image-001',
            'media_url' => 'https://example.com/image.jpg',
            'media_mime_type' => 'image/jpeg',
            'media_filename' => 'mattress.jpg',
            'media_caption' => 'Wonder Godoro Point mattress',
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'message_type' => 'image',
            'media_id' => 'media-image-001',
            'media_mime_type' => 'image/jpeg',
            'media_filename' => 'mattress.jpg',
            'media_caption' => 'Wonder Godoro Point mattress',
        ]);

        $this->assertSame(
            'https://example.com/image.jpg',
            $message->fresh()->media_url
        );
    }

    public function test_video_message_can_store_media_metadata(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->for($conversation)->create([
            'message_type' => 'video',
            'body' => null,
            'media_id' => 'media-video-001',
            'media_url' => 'https://example.com/product-video.mp4',
            'media_mime_type' => 'video/mp4',
            'media_filename' => 'product-video.mp4',
            'media_caption' => 'Video ya godoro la Wonder Godoro Point',
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'message_type' => 'video',
            'media_id' => 'media-video-001',
            'media_mime_type' => 'video/mp4',
            'media_filename' => 'product-video.mp4',
            'media_caption' => 'Video ya godoro la Wonder Godoro Point',
        ]);

        $this->assertSame(
            'https://example.com/product-video.mp4',
            $message->fresh()->media_url
        );
    }

    public function test_location_message_can_store_coordinates(): void
    {
        $conversation = Conversation::factory()->create();

        $message = Message::factory()->for($conversation)->create([
            'message_type' => 'location',
            'body' => null,
            'latitude' => -6.7924000,
            'longitude' => 39.2083000,
            'location_name' => 'Wonder Godoro Point',
            'location_address' => 'Dar es Salaam, Tanzania',
        ]);

        $freshMessage = $message->fresh();

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'message_type' => 'location',
            'location_name' => 'Wonder Godoro Point',
            'location_address' => 'Dar es Salaam, Tanzania',
        ]);

        $this->assertSame('-6.7924000', $freshMessage->latitude);
        $this->assertSame('39.2083000', $freshMessage->longitude);
    }
}