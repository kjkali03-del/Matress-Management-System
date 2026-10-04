<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use RuntimeException;
use ZipArchive;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.reports.index', [
            'data' => $this->reportData($request),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $data = $this->reportData($request);
        $pdf = $this->buildSimplePdf('Wonder Godoro Point - Sales & Reports', $this->reportLines($data));

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="wonder-godoro-report-'.$this->filenameDate($data).'.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function excel(Request $request): Response
    {
        $data = $this->reportData($request);

        return $this->archiveDownload(
            $data,
            'xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            fn (string $path) => $this->buildXlsx($path, $data),
        );
    }

    public function docx(Request $request): Response
    {
        $data = $this->reportData($request);

        return $this->archiveDownload(
            $data,
            'docx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            fn (string $path) => $this->buildDocx($path, $data),
        );
    }

    private function reportData(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : now()->startOfMonth();
        $to = isset($validated['to'])
            ? Carbon::parse($validated['to'])->endOfDay()
            : now()->endOfDay();

        $orders = $this->ordersInPeriod($from, $to);
        $revenue = (float) (clone $orders)->sum('total_amount');
        $cogs = (float) (clone $orders)
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM(orders.quantity * products.cost_price), 0) AS estimated_cogs')
            ->value('estimated_cogs');
        $orderCount = (clone $orders)->count();
        $paidOrders = (clone $orders)->where('payment_status', 'paid')->count();
        $deliveredOrders = (clone $orders)->where('delivery_status', 'delivered')->count();
        $newCustomers = Customer::query()
            ->whereBetween('created_at', [$from, $to])
            ->count();
        $messages = Message::query()->whereBetween('created_at', [$from, $to]);

        $products = (clone $orders)
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->selectRaw('orders.product_id')
            ->selectRaw('COALESCE(products.name, orders.product_name) AS product_name')
            ->selectRaw('orders.product_size')
            ->selectRaw('SUM(orders.quantity) AS quantity')
            ->selectRaw('SUM(orders.total_amount) AS revenue')
            ->selectRaw('COALESCE(SUM(orders.quantity * products.cost_price), 0) AS estimated_cogs')
            ->selectRaw('CASE WHEN COUNT(products.id) > 0 THEN SUM(orders.total_amount) - SUM(orders.quantity * products.cost_price) END AS estimated_profit')
            ->groupBy('orders.product_id', 'orders.product_name', 'orders.product_size', 'products.name')
            ->orderByDesc('revenue')
            ->get();

        $customers = (clone $orders)
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->selectRaw('orders.customer_id, customers.name AS customer_name')
            ->selectRaw('COUNT(orders.id) AS orders')
            ->selectRaw('SUM(orders.total_amount) AS revenue')
            ->groupBy('orders.customer_id', 'customers.name')
            ->orderByDesc('revenue')
            ->limit(20)
            ->get();

        $payments = (clone $orders)
            ->selectRaw('payment_status, COUNT(*) AS orders, SUM(total_amount) AS revenue')
            ->groupBy('payment_status')
            ->orderBy('payment_status')
            ->get();

        $deliveryStatuses = (clone $orders)
            ->selectRaw('delivery_status, COUNT(*) AS orders, SUM(total_amount) AS revenue')
            ->groupBy('delivery_status')
            ->orderBy('delivery_status')
            ->get();

        $areas = (clone $orders)
            ->selectRaw("COALESCE(NULLIF(TRIM(delivery_area), ''), 'Not specified') AS area")
            ->selectRaw('COUNT(*) AS orders, SUM(total_amount) AS revenue')
            ->selectRaw("SUM(CASE WHEN delivery_status = 'delivered' THEN 1 ELSE 0 END) AS delivered_orders")
            ->groupByRaw("COALESCE(NULLIF(TRIM(delivery_area), ''), 'Not specified')")
            ->orderByDesc('revenue')
            ->get();

        $sizes = (clone $orders)
            ->selectRaw("COALESCE(NULLIF(TRIM(product_size), ''), 'Not specified') AS product_size")
            ->selectRaw('SUM(quantity) AS quantity, SUM(total_amount) AS revenue')
            ->groupByRaw("COALESCE(NULLIF(TRIM(product_size), ''), 'Not specified')")
            ->orderByDesc('quantity')
            ->get();

        $trend = (clone $orders)
            ->leftJoin('products', 'orders.product_id', '=', 'products.id')
            ->selectRaw('DATE(COALESCE(orders.ordered_at, orders.created_at)) AS report_date')
            ->selectRaw('SUM(orders.total_amount) AS sales')
            ->selectRaw('SUM(CASE WHEN products.id IS NOT NULL THEN orders.quantity * products.cost_price ELSE 0 END) AS cogs')
            ->groupByRaw('DATE(COALESCE(orders.ordered_at, orders.created_at))')
            ->orderBy('report_date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->report_date,
                'sales' => (float) $row->sales,
                'profit' => (float) $row->sales - (float) $row->cogs,
            ]);

        $deliveryTotals = $deliveryStatuses->keyBy(fn ($row) => str_replace([' ', '-'], '_', strtolower((string) $row->delivery_status)));
        $deliveryReport = collect(['pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled'])
            ->map(function (string $status) use ($deliveryTotals) {
                $row = $deliveryTotals->get($status);

                return [
                    'status' => $status,
                    'orders' => (int) ($row->orders ?? 0),
                    'revenue' => (float) ($row->revenue ?? 0),
                ];
            });
        foreach ($deliveryStatuses as $row) {
            $status = str_replace([' ', '-'], '_', strtolower((string) $row->delivery_status));
            if (! in_array($status, ['pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled'], true)) {
                $deliveryReport->push([
                    'status' => $status ?: 'not specified',
                    'orders' => (int) $row->orders,
                    'revenue' => (float) $row->revenue,
                ]);
            }
        }

        return [
            'from' => $from,
            'to' => $to,
            'revenue' => $revenue,
            'orders' => $orderCount,
            'cogs' => $cogs,
            'gross_profit' => $revenue - $cogs,
            'average_order_value' => $orderCount > 0 ? $revenue / $orderCount : 0,
            'paid_orders' => $paidOrders,
            'unpaid_orders' => (clone $orders)->whereIn('payment_status', ['unpaid', 'partial', 'pending'])->count(),
            'delivered_orders' => $deliveredOrders,
            'new_customers' => $newCustomers,
            'messages' => (clone $messages)->count(),
            'inbound_messages' => (clone $messages)->where('direction', 'inbound')->count(),
            'outbound_messages' => (clone $messages)->where('direction', 'outbound')->count(),
            'active_products' => Product::query()->where('is_active', true)->count(),
            'automation_runs' => AutomationRun::query()->whereBetween('created_at', [$from, $to])->count(),
            'products' => $products,
            'customers' => $customers,
            'payments' => $payments,
            'delivery' => $deliveryReport,
            'areas' => $areas,
            'sizes' => $sizes,
            'trend' => $trend,
            'expenses_available' => false,
        ];
    }

    private function ordersInPeriod(Carbon $from, Carbon $to): Builder
    {
        return Order::query()->whereRaw(
            'COALESCE(orders.ordered_at, orders.created_at) BETWEEN ? AND ?',
            [$from, $to],
        );
    }

    private function filenameDate(array $data): string
    {
        return $data['from']->format('Y-m-d').'-to-'.$data['to']->format('Y-m-d');
    }

    private function reportLines(array $data): array
    {
        $money = static fn ($value): string => 'TSh '.number_format((float) $value, 2);
        $lines = [
            'Period: '.$data['from']->format('d M Y').' - '.$data['to']->format('d M Y'),
            '',
            'SALES OVERVIEW',
            'Total Sales / Revenue: '.$money($data['revenue']),
            'Total Orders: '.$data['orders'],
            'Estimated COGS: '.$money($data['cogs']),
            'Gross Profit: '.$money($data['gross_profit']),
            'Average Order Value: '.$money($data['average_order_value']),
            'Paid Orders: '.$data['paid_orders'],
            'Unpaid / Pending Orders: '.$data['unpaid_orders'],
            'Delivered Orders: '.$data['delivered_orders'],
            'New Customers: '.$data['new_customers'],
            'Total Messages: ' . $data['messages'],
            'Inbound Messages: ' . $data['inbound_messages'],
            'Outbound Messages: ' . $data['outbound_messages'],
            'Active Products: ' . $data['active_products'],
            'Automation Runs: ' . $data['automation_runs'],
            '',
            'SALES & PROFIT TREND',
            'Date | Sales | Estimated Profit',
        ];
        foreach ($data['trend'] as $day) {
            $lines[] = $day['date'].' | '.$money($day['sales']).' | '.$money($day['profit']);
        }

        $lines = array_merge($lines, ['', 'PRODUCTS REPORT', 'Product | Quantity | Revenue | Estimated Profit']);
        foreach ($data['products'] as $product) {
            $lines[] = $product->product_name.' | '.$product->quantity.' | '.$money($product->revenue)
                .' | '.($product->estimated_profit === null ? 'Unavailable' : $money($product->estimated_profit));
        }
        if ($data['products']->isEmpty()) {
            $lines[] = 'No product sales recorded for this period.';
        }

        $lines = array_merge($lines, ['', 'CUSTOMERS REPORT', 'Customer | Orders | Revenue']);
        foreach ($data['customers'] as $customer) {
            $lines[] = $customer->customer_name.' | '.$customer->orders.' | '.$money($customer->revenue);
        }
        if ($data['customers']->isEmpty()) {
            $lines[] = 'No customer orders recorded for this period.';
        }

        $lines = array_merge($lines, ['', 'PAYMENTS REPORT', 'Status | Orders | Revenue']);
        foreach ($data['payments'] as $payment) {
            $lines[] = ($payment->payment_status ?: 'not specified').' | '.$payment->orders.' | '.$money($payment->revenue);
        }

        $lines = array_merge($lines, ['', 'DELIVERY REPORT', 'Status | Orders | Revenue']);
        foreach ($data['delivery'] as $delivery) {
            $lines[] = $delivery['status'].' | '.$delivery['orders'].' | '.$money($delivery['revenue']);
        }

        $lines = array_merge($lines, ['', 'AREA REPORT', 'Area | Orders | Revenue | Delivered']);
        foreach ($data['areas'] as $area) {
            $lines[] = $area->area.' | '.$area->orders.' | '.$money($area->revenue).' | '.$area->delivered_orders;
        }

        $lines = array_merge($lines, ['', 'SIZE REPORT', 'Size | Quantity | Revenue']);
        foreach ($data['sizes'] as $size) {
            $lines[] = $size->product_size.' | '.$size->quantity.' | '.$money($size->revenue);
        }

        return array_merge($lines, ['', 'EXPENSES REPORT', 'Expenses are unavailable: no expenses source is configured.']);
    }

    private function archiveDownload(array $data, string $extension, string $contentType, callable $build): Response
    {
        if (! class_exists(ZipArchive::class)) {
            abort(500, 'The PHP ZIP extension is required for this report export.');
        }

        $path = tempnam(sys_get_temp_dir(), 'wgp_report_');
        if ($path === false) {
            throw new RuntimeException('Unable to allocate a temporary file for the report export.');
        }

        try {
            $build($path);
            $content = file_get_contents($path);
            if ($content === false) {
                throw new RuntimeException('Unable to read the generated report export.');
            }
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }

        return response($content, 200, [
            'Content-Type' => $contentType,
            'Content-Disposition' => 'attachment; filename="wonder-godoro-report-'.$this->filenameDate($data).'.'.$extension.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function buildXlsx(string $path, array $data): void
    {
        $zip = $this->openArchive($path, 'Excel');
        $rows = [
            ['WONDER GODORO POINT', 'Sales & Reports'],
            ['Report period', $data['from']->format('Y-m-d').' to '.$data['to']->format('Y-m-d')],
            [],
            ['SALES OVERVIEW', 'Value'],
            ['Total Sales / Revenue', $data['revenue']],
            ['Total Orders', $data['orders']],
            ['Estimated COGS', $data['cogs']],
            ['Gross Profit', $data['gross_profit']],
            ['Average Order Value', $data['average_order_value']],
            ['Paid Orders', $data['paid_orders']],
            ['Unpaid / Pending Orders', $data['unpaid_orders']],
            ['Delivered Orders', $data['delivered_orders']],
            ['New Customers', $data['new_customers']],
            ['Total Messages', $data['messages']],
            ['Inbound Messages', $data['inbound_messages']],
            ['Outbound Messages', $data['outbound_messages']],
            ['Active Products', $data['active_products']],
            ['Automation Runs', $data['automation_runs']],
            [],
            ['SALES & PROFIT TREND', 'Sales', 'Estimated Profit'],
        ];
        foreach ($data['trend'] as $day) {
            $rows[] = [$day['date'], $day['sales'], $day['profit']];
        }
        $rows = array_merge($rows, [[], ['PRODUCTS REPORT', 'Quantity', 'Revenue', 'Estimated Profit']]);
        foreach ($data['products'] as $product) {
            $rows[] = [$product->product_name, (int) $product->quantity, (float) $product->revenue, $product->estimated_profit];
        }
        $rows = array_merge($rows, [[], ['CUSTOMERS REPORT', 'Orders', 'Revenue']]);
        foreach ($data['customers'] as $customer) {
            $rows[] = [$customer->customer_name, (int) $customer->orders, (float) $customer->revenue];
        }
        $rows = array_merge($rows, [[], ['PAYMENTS REPORT', 'Orders', 'Revenue']]);
        foreach ($data['payments'] as $payment) {
            $rows[] = [$payment->payment_status ?: 'not specified', (int) $payment->orders, (float) $payment->revenue];
        }
        $rows = array_merge($rows, [[], ['DELIVERY REPORT', 'Orders', 'Revenue']]);
        foreach ($data['delivery'] as $delivery) {
            $rows[] = [$delivery['status'], $delivery['orders'], $delivery['revenue']];
        }
        $rows = array_merge($rows, [[], ['AREA REPORT', 'Orders', 'Revenue', 'Delivered']]);
        foreach ($data['areas'] as $area) {
            $rows[] = [$area->area, (int) $area->orders, (float) $area->revenue, (int) $area->delivered_orders];
        }
        $rows = array_merge($rows, [[], ['SIZE REPORT', 'Quantity', 'Revenue']]);
        foreach ($data['sizes'] as $size) {
            $rows[] = [$size->product_size, (int) $size->quantity, (float) $size->revenue];
        }
        $rows[] = [];
        $rows[] = ['EXPENSES REPORT', 'Unavailable: no expenses source is configured.'];

        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 1;
            $sheetRows .= '<row r="'.$rowNumber.'">';
            foreach ($row as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                $cell = $this->xlsxColumn($columnIndex + 1).$rowNumber;
                if (is_numeric($value)) {
                    $sheetRows .= '<c r="'.$cell.'"><v>'.$this->xml($value).'</v></c>';
                } else {
                    $sheetRows .= '<c r="'.$cell.'" t="inlineStr"><is><t>'.$this->xml($value).'</t></is></c>';
                }
            }
            $sheetRows .= '</row>';
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
            'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets></workbook>',
            'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
            'xl/worksheets/sheet1.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>'.$sheetRows.'</sheetData></worksheet>',
        ];
        $this->addArchiveFiles($zip, $files, 'Excel');
    }

    private function buildDocx(string $path, array $data): void
    {
        $zip = $this->openArchive($path, 'Word');
        $paragraphs = '';
        foreach ($this->reportLines($data) as $line) {
            $bold = in_array($line, [
                'SALES OVERVIEW',
                'SALES & PROFIT TREND',
                'PRODUCTS REPORT',
                'CUSTOMERS REPORT',
                'PAYMENTS REPORT',
                'DELIVERY REPORT',
                'AREA REPORT',
                'SIZE REPORT',
                'EXPENSES REPORT',
            ], true);
            $runProperties = $bold ? '<w:rPr><w:b/><w:color w:val="A98218"/></w:rPr>' : '';
            $paragraphs .= '<w:p><w:r>'.$runProperties.'<w:t xml:space="preserve">'.$this->xml($line).'</w:t></w:r></w:p>';
        }

        $files = [
            '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
            '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
            'word/document.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$paragraphs.'<w:sectPr/></w:body></w:document>',
        ];
        $this->addArchiveFiles($zip, $files, 'Word');
    }

    private function openArchive(string $path, string $format): ZipArchive
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create '.$format.' report.');
        }

        return $zip;
    }

    private function addArchiveFiles(ZipArchive $zip, array $files, string $format): void
    {
        foreach ($files as $name => $contents) {
            if (! $zip->addFromString($name, $contents)) {
                $zip->close();
                throw new RuntimeException('Unable to add a file to the '.$format.' report.');
            }
        }
        if (! $zip->close()) {
            throw new RuntimeException('Unable to finish the '.$format.' report.');
        }
    }

    private function buildSimplePdf(string $title, array $lines): string
    {
        $pages = array_chunk($lines, 42);
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];
        $objects[1] = '<< /Type /Pages /Kids [';
        foreach ($pages as $index => $_) {
            $pageObject = 5 + ($index * 2);
            $contentObject = $pageObject + 1;
            $objects[1] .= $pageObject.' 0 R ';
            $stream = "q\n0.035 0.043 0.055 rg\n0 760 595 82 re f\nQ\n";
            $stream .= "q\n0.84 0.65 0.18 rg\n0 758 595 2 re f\nQ\n";
            $stream .= "BT\n/F2 17 Tf\n1 1 1 rg\n48 812 Td\n(".$this->pdfText($title).") Tj\nET\n";
            $stream .= "BT\n/F1 9 Tf\n0.89 0.89 0.89 rg\n48 790 Td\n(".$this->pdfText('Report period: '.$this->reportPeriodFromLines($lines)).") Tj\nET\n";
            $y = 738;
            foreach (array_slice($pages[$index], $index === 0 ? 1 : 0) as $line) {
                $heading = in_array($line, [
                    'SALES OVERVIEW',
                    'SALES & PROFIT TREND',
                    'PRODUCTS REPORT',
                    'CUSTOMERS REPORT',
                    'PAYMENTS REPORT',
                    'DELIVERY REPORT',
                    'AREA REPORT',
                    'SIZE REPORT',
                    'EXPENSES REPORT',
                ], true);
                $font = $heading ? 'F2' : 'F1';
                $size = $heading ? 10 : 9;
                $color = $heading ? '0.56 0.40 0.05' : '0.12 0.14 0.17';
                $stream .= 'BT /'.$font.' '.$size.' Tf '.$color.' rg 48 '.$y.' Td ('
                    .$this->pdfText(mb_substr((string) $line, 0, 110)).") Tj ET\n";
                $y -= $heading ? 17 : 14;
            }
            $objects[$pageObject - 1] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents '.$contentObject.' 0 R >>';
            $objects[$contentObject - 1] = '<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream';
        }
        $objects[1] .= '] /Count '.count($pages).' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $index => $object) {
            $number = $index + 1;
            $offsets[$number] = strlen($pdf);
            $pdf .= $number." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        for ($index = 1; $index <= count($objects); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF";

        return $pdf;
    }

    private function reportPeriodFromLines(array $lines): string
    {
        return preg_replace('/^Period:\s*/', '', (string) ($lines[0] ?? '')) ?? '';
    }

    private function pdfText(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = $converted === false ? '' : $converted;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    }

    private function xlsxColumn(int $number): string
    {
        $result = '';
        while ($number > 0) {
            $number--;
            $result = chr(65 + ($number % 26)).$result;
            $number = intdiv($number, 26);
        }

        return $result;
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
