<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerNotesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_customer_notes(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Maya Customer',
            'notes' => null,
        ]);

        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'status' => 'open',
        ]);

        $response = $this->actingAs($admin)->patch(
            route('admin.inbox.customers.notes.update', $customer),
            [
                'notes' => 'Customer prefers a medium-firm mattress.',
            ]
        );

        $response->assertRedirect(
            route('admin.inbox', [
                'conversation' => $conversation->id,
            ])
        );

        $response->assertSessionHas(
            'notes_status',
            'Customer notes saved successfully.'
        );

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'notes' => 'Customer prefers a medium-firm mattress.',
        ]);
    }

    public function test_admin_can_download_customer_notes_as_text(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Maya Customer',
            'phone' => '+255700000001',
            'notes' => 'Customer wants delivery in Dar es Salaam.',
        ]);

        $response = $this->actingAs($admin)->get(
            route(
                'admin.inbox.customers.notes.download',
                $customer
            )
        );

        $response->assertOk();

        $response->assertHeader(
            'Content-Type',
            'text/plain; charset=UTF-8'
        );

        $response->assertHeader(
            'Content-Disposition',
            'attachment; filename="Maya-Customer-notes.txt"'
        );

        $response->assertSeeText('WONDER GODORO POINT');
        $response->assertSeeText('Maya Customer');
        $response->assertSeeText('+255700000001');
        $response->assertSeeText(
            'Customer wants delivery in Dar es Salaam.'
        );
    }
}