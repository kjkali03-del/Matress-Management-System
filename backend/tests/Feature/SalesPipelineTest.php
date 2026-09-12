<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_sales_pipeline(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Customer::factory()->create([
            'name' => 'New Customer',
            'status' => 'new',
        ]);

        Customer::factory()->create([
            'name' => 'Interested Customer',
            'status' => 'interested',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.pipeline.index'));

        $response->assertOk();

        $response->assertViewIs('admin.pipeline.index');

        $response->assertViewHas('pipeline');
        $response->assertViewHas('search');
        $response->assertViewHas('stages');
    }

    public function test_admin_can_search_customers_in_pipeline(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Customer::factory()->create([
            'name' => 'Miriam Customer',
            'phone' => '+255700000001',
            'status' => 'new',
        ]);

        Customer::factory()->create([
            'name' => 'Other Customer',
            'phone' => '+255700000002',
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.pipeline.index', [
                'search' => 'Miriam',
            ]));

        $response->assertOk();

        $pipeline = $response->viewData('pipeline');

        $this->assertCount(
            1,
            $pipeline['new']['customers']
        );

        $this->assertSame(
            'Miriam Customer',
            $pipeline['new']['customers']->first()->name
        );
    }

    public function test_admin_can_move_customer_to_another_pipeline_stage(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Pipeline Customer',
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)
            ->patch(
                route(
                    'admin.pipeline.customers.status.update',
                    $customer
                ),
                [
                    'status' => 'interested',
                ]
            );

        $response->assertRedirect(
            route('admin.pipeline.index')
        );

        $response->assertSessionHas(
            'success',
            'Customer moved to Interested.'
        );

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'interested',
        ]);
    }

    public function test_pipeline_rejects_invalid_customer_status(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $customer = Customer::factory()->create([
            'status' => 'new',
        ]);

        $response = $this->actingAs($admin)
            ->patch(
                route(
                    'admin.pipeline.customers.status.update',
                    $customer
                ),
                [
                    'status' => 'invalid-stage',
                ]
            );

        $response->assertSessionHasErrors('status');

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'new',
        ]);
    }

    public function test_non_admin_cannot_access_sales_pipeline(): void
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.pipeline.index'));

        $response->assertForbidden();
    }
}