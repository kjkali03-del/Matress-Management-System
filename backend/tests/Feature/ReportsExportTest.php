<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_download_valid_report_exports_for_the_selected_period(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->createReportOrders();
        $query = [
            'from' => now()->subDays(3)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ];

        $pdf = $this->actingAs($admin)->get(route('admin.reports.export.pdf', $query))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="wonder-godoro-report-'.$query['from'].'-to-'.$query['to'].'.pdf"');
        $this->assertStringStartsWith('%PDF-1.4', $pdf->getContent());

        $excel = $this->actingAs($admin)->get(route('admin.reports.export.excel', $query))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertArchiveContains($excel->getContent(), ['xl/workbook.xml', 'xl/worksheets/sheet1.xml']);

        $docx = $this->actingAs($admin)->get(route('admin.reports.export.docx', $query))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertArchiveContains($docx->getContent(), ['word/document.xml']);
    }

    public function test_report_filters_dates_and_calculates_revenue_and_estimated_profit_from_all_orders(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->createReportOrders();
        $query = [
            'from' => now()->subDays(3)->format('Y-m-d'),
            'to' => now()->format('Y-m-d'),
        ];

        $this->actingAs($admin)
            ->get(route('admin.reports.index', $query))
            ->assertOk()
            ->assertSee('Sales &amp; Reports', false)
            ->assertSee('TSh 140,000.00')
            ->assertSee('TSh 60,000.00')
            ->assertSee('TSh 80,000.00')
            ->assertSee('TSh 70,000.00')
            ->assertSee('Expenses are unavailable / not configured')
            ->assertSee('data-report-print', false)
            ->assertSee('PDF')
            ->assertSee('Excel')
            ->assertSee('DOCX')
            ->assertSee('Print')
            ->assertViewHas('data', function (array $data): bool {
                return $data['revenue'] === 140000.0
                    && $data['cogs'] === 60000.0
                    && $data['gross_profit'] === 80000.0
                    && $data['average_order_value'] === 70000.0
                    && $data['orders'] === 2
                    && $data['paid_orders'] === 1
                    && $data['unpaid_orders'] === 1
                    && $data['delivered_orders'] === 1
                    && $data['new_customers'] === 1
                    && (float) $data['products']->first()->estimated_profit === 40000.0;
            });
    }

    public function test_report_rejects_an_end_date_before_the_start_date(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->from(route('admin.reports.index'))
            ->get(route('admin.reports.index', [
                'from' => '2026-10-04',
                'to' => '2026-10-03',
            ]))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('to');
    }

    private function createReportOrders(): void
    {
        $category = ProductCategory::create([
            'name' => 'Report category',
            'description' => 'Report test products',
            'is_active' => true,
        ]);
        $product = Product::create([
            'product_category_id' => $category->id,
            'name' => 'Report mattress',
            'sku' => 'REPORT-001',
            'size' => '5x6',
            'price' => 50000,
            'cost_price' => 30000,
            'stock_quantity' => 10,
            'reorder_level' => 1,
            'is_active' => true,
        ]);
        $customer = Customer::factory()->create(['name' => 'Report customer']);

        Order::create([
            'order_number' => 'REPORT-ORDER-001',
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_size' => $product->size,
            'quantity' => 2,
            'unit_price' => 50000,
            'total_amount' => 100000,
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'delivery_area' => 'Central',
            'ordered_at' => now()->subDays(2),
        ]);

        Order::create([
            'order_number' => 'REPORT-ORDER-002',
            'customer_id' => $customer->id,
            'product_name' => 'Unlinked mattress',
            'product_size' => '6x6',
            'quantity' => 1,
            'unit_price' => 40000,
            'total_amount' => 40000,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'delivery_status' => 'processing',
            'delivery_area' => 'North',
            'ordered_at' => now()->subDay(),
        ]);

        Order::create([
            'order_number' => 'REPORT-ORDER-OLDER',
            'customer_id' => $customer->id,
            'product_name' => 'Outside date range',
            'quantity' => 1,
            'unit_price' => 90000,
            'total_amount' => 90000,
            'status' => 'completed',
            'payment_status' => 'paid',
            'delivery_status' => 'delivered',
            'ordered_at' => now()->subDays(10),
        ]);
    }

    private function assertArchiveContains(string $content, array $expectedEntries): void
    {
        $path = tempnam(sys_get_temp_dir(), 'wgp_test_report_');
        $this->assertNotFalse($path);

        try {
            $this->assertNotFalse(file_put_contents($path, $content));
            $archive = new ZipArchive;
            $this->assertSame(true, $archive->open($path));
            foreach ($expectedEntries as $entry) {
                $this->assertNotFalse($archive->getFromName($entry), 'Missing archive entry: '.$entry);
            }
            $archive->close();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
}
