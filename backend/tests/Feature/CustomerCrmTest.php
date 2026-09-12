<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerCrmTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_store_crm_fields(): void
    {
        $user = User::factory()->create();

        $customer = Customer::create([
            'name' => 'CRM Test Customer',
            'phone' => '+255700000000',
            'location' => 'Dar es Salaam',
            'status' => 'interested',
            'last_contact_at' => now(),
            'assigned_to' => $user->id,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'CRM Test Customer',
            'location' => 'Dar es Salaam',
            'status' => 'interested',
            'assigned_to' => $user->id,
        ]);

        $this->assertNotNull($customer->last_contact_at);
    }

    public function test_customer_can_be_assigned_to_a_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Sales Staff',
        ]);

        $customer = Customer::factory()->create([
            'assigned_to' => $user->id,
        ]);

        $this->assertTrue($customer->assignedUser->is($user));
    }

    public function test_customer_can_have_multiple_tags(): void
    {
        $customer = Customer::factory()->create();

        $tagOne = Tag::create([
            'name' => 'Interested',
            'color' => '#D4AF37',
        ]);

        $tagTwo = Tag::create([
            'name' => 'Follow Up',
            'color' => '#000000',
        ]);

        $customer->tags()->attach([
            $tagOne->id,
            $tagTwo->id,
        ]);

        $this->assertCount(2, $customer->fresh()->tags);
        $this->assertTrue(
            $customer->fresh()->tags->contains($tagOne)
        );
        $this->assertTrue(
            $customer->fresh()->tags->contains($tagTwo)
        );
    }

    public function test_tag_can_have_multiple_customers(): void
    {
        $tag = Tag::create([
            'name' => 'Hot Lead',
            'color' => '#FF0000',
        ]);

        $customerOne = Customer::factory()->create();
        $customerTwo = Customer::factory()->create();

        $tag->customers()->attach([
            $customerOne->id,
            $customerTwo->id,
        ]);

        $this->assertCount(2, $tag->fresh()->customers);
    }
}