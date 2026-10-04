<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AutomationRun;
use App\Models\Customer;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ZipArchive;

class ReportsController extends Controller
{
    public function index(Request $request): View
    {
        [$data, $topProducts] = $this->reportData($request);

        return view('admin.reports.index', compact('data', 'topProducts'));
    }

    public function pdf(Request $request): Response
    {
        [$data, $topProducts] = $this->reportData($request);
        $title = 'Wonder Godoro Point - Sales Report';
        $lines = $this->reportLines($data, $topProducts);
        $pdf = $this->buildSimplePdf($title, $lines);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="wonder-godoro-report-' . $this->filenameDate($data) . '.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function excel(Request $request): Response
    {
        [$data, $topProducts] = $this->reportData($request);
        $path = tempnam(sys_get_temp_dir(), 'wgp_xlsx_');
        $this->buildXlsx($path, $data, $topProducts);
        $content = file_get_contents($path);
        @unlink($path);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="wonder-godoro-report-' . $this->filenameDate($data) . '.xlsx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function docx(Request $request): Response
    {
        [$data, $topProducts] = $this->reportData($request);
        $path = tempnam(sys_get_temp_dir(), 'wgp_docx_');
        $this->buildDocx($path, $data, $topProducts);
        $content = file_get_contents($path);
        @unlink($path);

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'Content-Disposition' => 'attachment; filename="wonder-godoro-report-' . $this->filenameDate($data) . '.docx"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function reportData(Request $request): array
    {
        $from = $request->date('from')?->startOfDay() ?? now()->startOfMonth();
        $to = $request->date('to')?->endOfDay() ?? now()->endOfDay();

        $orders = Order::query()->whereBetween('ordered_at', [$from, $to]);
        $paidOrders = (clone $orders)->where('payment_status', 'paid');
        $revenue = (float) (clone $paidOrders)->sum('total_amount');
        $estimatedCogs = (float) Order::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->join('products', 'orders.product_id', '=', 'products.id')
            ->selectRaw('COALESCE(SUM(orders.quantity * products.cost_price), 0) as cogs')
            ->value('cogs');
        $messages = Message::whereBetween('created_at', [$from, $to]);

        $data = [
            'from' => $from,
            'to' => $to,
            'orders' => (clone $orders)->count(),
            'paid_orders' => (clone $paidOrders)->count(),
            'revenue' => $revenue,
            'cogs' => $estimatedCogs,
            'gross_profit' => $revenue - $estimatedCogs,
            'messages' => $messages->count(),
            'inbound_messages' => (clone $messages)->where('direction', 'inbound')->count(),
            'outbound_messages' => (clone $messages)->where('direction', 'outbound')->count(),
            'new_customers' => Customer::whereBetween('created_at', [$from, $to])->count(),
            'delivered_orders' => (clone $orders)->where('delivery_status', 'delivered')->count(),
            'active_products' => Product::where('is_active', true)->count(),
            'automation_runs' => AutomationRun::whereBetween('created_at', [$from, $to])->count(),
        ];

        $topProducts = Order::query()
            ->whereBetween('ordered_at', [$from, $to])
            ->selectRaw('product_name, SUM(quantity) as quantity, SUM(total_amount) as revenue')
            ->groupBy('product_name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        return [$data, $topProducts];
    }

    private function filenameDate(array $data): string
    {
        return $data['from']->format('Y-m-d') . '-to-' . $data['to']->format('Y-m-d');
    }

    private function reportLines(array $data, $topProducts): array
    {
        $money = static fn ($value): string => 'TSh ' . number_format((float) $value, 0);
        $lines = [
            'Period: ' . $data['from']->format('d M Y') . ' - ' . $data['to']->format('d M Y'),
            '',
            'SALES OVERVIEW',
            'Revenue: ' . $money($data['revenue']),
            'Estimated COGS: ' . $money($data['cogs']),
            'Gross Profit: ' . $money($data['gross_profit']),
            'Orders: ' . $data['orders'],
            'Paid Orders: ' . $data['paid_orders'],
            'Delivered Orders: ' . $data['delivered_orders'],
            '',
            'CUSTOMERS & COMMUNICATION',
            'New Customers: ' . $data['new_customers'],
            'Total Messages: ' . $data['messages'],
            'Inbound Messages: ' . $data['inbound_messages'],
            'Outbound Messages: ' . $data['outbound_messages'],
            '',
            'OPERATIONS',
            'Active Products: ' . $data['active_products'],
            'Automation Runs: ' . $data['automation_runs'],
            '',
            'TOP PRODUCTS',
            'Product | Quantity | Revenue',
        ];
        foreach ($topProducts as $product) {
            $lines[] = $product->product_name . ' | ' . $product->quantity . ' | ' . $money($product->revenue);
        }
        if ($topProducts->isEmpty()) {
            $lines[] = 'No sales data for this period.';
        }
        return $lines;
    }

    private function buildXlsx(string $path, array $data, $topProducts): void
    {
        if (!class_exists(ZipArchive::class)) {
            abort(500, 'The PHP ZIP extension is required for Excel exports.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create Excel report.');
        }

        $rows = [
            ['WONDER GODORO POINT', 'Sales & Operations Report'],
            ['Report period', $data['from']->format('Y-m-d') . ' to ' . $data['to']->format('Y-m-d')],
            [],
            ['SALES OVERVIEW', 'Value'],
            ['Revenue', $data['revenue']],
            ['Estimated COGS', $data['cogs']],
            ['Gross Profit', $data['gross_profit']],
            ['Orders', $data['orders']],
            ['Paid Orders', $data['paid_orders']],
            ['Delivered Orders', $data['delivered_orders']],
            [],
            ['CUSTOMERS & COMMUNICATION', 'Value'],
            ['New Customers', $data['new_customers']],
            ['Total Messages', $data['messages']],
            ['Inbound Messages', $data['inbound_messages']],
            ['Outbound Messages', $data['outbound_messages']],
            [],
            ['OPERATIONS', 'Value'],
            ['Active Products', $data['active_products']],
            ['Automation Runs', $data['automation_runs']],
            [],
            ['TOP PRODUCTS', 'Quantity', 'Revenue'],
        ];
        foreach ($topProducts as $product) {
            $rows[] = [$product->product_name, (float) $product->quantity, (float) $product->revenue];
        }
        if ($topProducts->isEmpty()) {
            $rows[] = ['No sales data for this period.', '', ''];
        }

        $sheetRows = '';
        foreach ($rows as $rowIndex => $row) {
            $r = $rowIndex + 1;
            $sheetRows .= '<row r="' . $r . '">';
            foreach ($row as $colIndex => $value) {
                if ($value === null || $value === '') continue;
                $cell = $this->xlsxColumn($colIndex + 1) . $r;
                if (is_numeric($value)) {
                    $sheetRows .= '<c r="' . $cell . '"><v>' . $this->xml($value) . '</v></c>';
                } else {
                    $sheetRows .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . $this->xml($value) . '</t></is></c>';
                }
            }
            $sheetRows .= '</row>';
        }

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>';
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>' . $sheetRows . '</sheetData></worksheet>';

        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
    }

    private function buildDocx(string $path, array $data, $topProducts): void
    {
        if (!class_exists(ZipArchive::class)) {
            abort(500, 'The PHP ZIP extension is required for Word exports.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Unable to create Word report.');
        }
        $paragraphs = '';
        foreach ($this->reportLines($data, $topProducts) as $line) {
            $bold = in_array($line, ['SALES OVERVIEW', 'CUSTOMERS & COMMUNICATION', 'OPERATIONS', 'TOP PRODUCTS'], true);
            $run = $bold ? '<w:r><w:rPr><w:b/></w:rPr><w:t>' : '<w:r><w:t>';
            $paragraphs .= '<w:p>' . $run . $this->xml($line) . '</w:t></w:r></w:p>';
        }
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paragraphs . '<w:sectPr/></w:body></w:document>';
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>';
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $document);
        $zip->close();
    }

    private function buildSimplePdf(string $title, array $lines): string
    {
        $pages = array_chunk($lines, 42);
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $pageRefs = [];
        $fontObject = 3;
        $nextObject = 4 + count($pages) * 2;
        $objects[1] = '<< /Type /Pages /Kids [';
        foreach ($pages as $index => $_) {
            $pageObject = 4 + ($index * 2);
            $contentObject = $pageObject + 1;
            $pageRefs[] = $pageObject . ' 0 R';
            $objects[1] .= $pageObject . ' 0 R ';
        }
        $objects[1] .= '] /Count ' . count($pages) . ' >>';
        $objects[2] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        foreach ($pages as $index => $pageLines) {
            $pageObject = 4 + ($index * 2);
            $contentObject = $pageObject + 1;
            $stream = "BT\n/F1 11 Tf\n50 780 Td\n";
            $stream .= '14 TL' . "\n";
            $stream .= '/F1 16 Tf (' . $this->pdfText($title) . ') Tj\n/F1 9 Tf 0 -20 Td\n';
            foreach ($pageLines as $line) {
                $stream .= '(' . $this->pdfText(Str::limit((string) $line, 105, '...')) . ') Tj\n0 -14 Td\n';
            }
            $stream .= "ET\n";
            $objects[$pageObject - 1] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
            $objects[$contentObject - 1] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
        }
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $i => $object) {
            $num = $i + 1;
            $offsets[$num] = strlen($pdf);
            $pdf .= $num . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf('%010d 00000 n \n', $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private function pdfText(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', ' ', ' '], $value);
    }

    private function xlsxColumn(int $number): string
    {
        $result = '';
        while ($number > 0) {
            $number--;
            $result = chr(65 + ($number % 26)) . $result;
            $number = intdiv($number, 26);
        }
        return $result;
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
