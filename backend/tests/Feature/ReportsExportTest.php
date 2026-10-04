<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_report_exports(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $query = ['from' => now()->startOfMonth()->format('Y-m-d'), 'to' => now()->format('Y-m-d')];

        $this->actingAs($admin)->get(route('admin.reports.export.pdf', $query))
            ->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->actingAs($admin)->get(route('admin.reports.export.excel', $query))
            ->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->actingAs($admin)->get(route('admin.reports.export.docx', $query))
            ->assertOk()->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
    }
}
