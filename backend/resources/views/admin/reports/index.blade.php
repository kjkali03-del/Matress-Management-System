@extends('layouts.admin')
@section('title', 'Sales & Reports | Wonder Godoro Point')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-reports.css') . '?v=20261004-v8' }}">
@endpush

@section('content')
<div class="report-page">
    <header class="report-head">
        <div class="report-title">
            <span class="report-title-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 19V5m0 14h17M7 15l4-4 3 2 6-7M17 6h3v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <div>
                <h1>Sales &amp; Reports</h1>
                <p>Understand sales, product performance, customers, payments and delivery.</p>
            </div>
        </div>
        <div class="report-actions">
            <form class="report-filter" method="GET" action="{{ route('admin.reports.index') }}">
                <label>From<input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}" required></label>
                <label>To<input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}" required></label>
                <button class="report-btn report-btn--primary" type="submit">Apply</button>
            </form>
            <div class="report-export" aria-label="Export report">
                <a class="report-btn" href="{{ route('admin.reports.export.pdf', request()->only('from', 'to')) }}">PDF</a>
                <a class="report-btn" href="{{ route('admin.reports.export.excel', request()->only('from', 'to')) }}">Excel</a>
                <a class="report-btn" href="{{ route('admin.reports.export.docx', request()->only('from', 'to')) }}">DOCX</a>
                <button class="report-btn report-btn--print" type="button" data-report-print>Print</button>
            </div>
        </div>
    </header>

    @if($errors->any())
        <div class="report-validation" role="alert">{{ $errors->first() }}</div>
    @endif

    <nav class="report-tabs" aria-label="Report sections">
        <a href="#overview">Overview</a>
        <a href="#sales-report">Sales Report</a>
        <a href="#products-report">Products Report</a>
        <a href="#customers-report">Customers Report</a>
        <a href="#communications-report">Communications &amp; Operations</a>
        <a href="#expenses-report">Expenses Report</a>
        <a href="#payments-report">Payments Report</a>
        <a href="#delivery-report">Delivery Report</a>
        <a href="#area-report">Area Report</a>
    </nav>

    <section id="overview" class="report-kpis" aria-label="Report overview">
        <article class="report-kpi report-kpi--highlight"><span>Total Sales / Revenue</span><strong>TSh {{ number_format($data['revenue'], 2) }}</strong><small>All orders in the selected period</small></article>
        <article class="report-kpi"><span>Total Orders</span><strong>{{ number_format($data['orders']) }}</strong><small>Orders placed in the selected period</small></article>
        <article class="report-kpi report-kpi--highlight"><span>Total Profit</span><strong>TSh {{ number_format($data['gross_profit'], 2) }}</strong><small>Revenue less estimated product COGS</small></article>
        <article class="report-kpi"><span>Estimated COGS</span><strong>TSh {{ number_format($data['cogs'], 2) }}</strong><small>Linked products with recorded cost</small></article>
        <article class="report-kpi"><span>Average Order Value</span><strong>TSh {{ number_format($data['average_order_value'], 2) }}</strong><small>Revenue divided by orders</small></article>
        <article class="report-kpi"><span>Paid Orders</span><strong>{{ number_format($data['paid_orders']) }}</strong><small>Payment status: paid</small></article>
        <article class="report-kpi"><span>Delivered Orders</span><strong>{{ number_format($data['delivered_orders']) }}</strong><small>Delivery status: delivered</small></article>
        <article class="report-kpi"><span>New Customers</span><strong>{{ number_format($data['new_customers']) }}</strong><small>Customer accounts created in this period</small></article>
    </section>

    <section id="sales-report" class="report-panel">
        <div class="report-panel-head">
            <div><p class="report-eyebrow">Performance over time</p><h2>Sales &amp; Profit Trend</h2><p>Daily sales and estimated profit for {{ $data['from']->format('d M Y') }} – {{ $data['to']->format('d M Y') }}.</p></div>
            <div class="report-legend"><span><i class="report-legend__sales"></i>Sales</span><span><i class="report-legend__profit"></i>Estimated profit</span></div>
        </div>
        @if($data['trend']->isNotEmpty())
            @php $trendMax = max(1, (float) $data['trend']->max('sales')); @endphp
            <div class="report-chart" role="img" aria-label="Daily sales and estimated profit bar chart">
                @foreach($data['trend'] as $day)
                    @php
                        $salesHeight = min(100, max(3, ((float) $day['sales'] / $trendMax) * 100));
                        $profitHeight = min(100, max(3, (max(0, (float) $day['profit']) / $trendMax) * 100));
                    @endphp
                    <div class="report-chart-day" title="{{ $day['date'] }} — sales TSh {{ number_format($day['sales'], 2) }}, estimated profit TSh {{ number_format($day['profit'], 2) }}">
                        <div class="report-chart-bars"><span class="report-chart-bar report-chart-bar--sales" style="height:{{ $salesHeight }}%"></span><span class="report-chart-bar report-chart-bar--profit" style="height:{{ $profitHeight }}%"></span></div>
                        <span class="report-chart-label">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="report-table-wrap report-trend-table">
                <table class="report-table"><thead><tr><th>Date</th><th>Sales</th><th>Estimated Profit</th></tr></thead><tbody>
                    @foreach($data['trend'] as $day)
                        <tr><td>{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M Y') }}</td><td>TSh {{ number_format($day['sales'], 2) }}</td><td>TSh {{ number_format($day['profit'], 2) }}</td></tr>
                    @endforeach
                </tbody></table>
            </div>
        @else
            <p class="report-empty">No sales data is available for this date range.</p>
        @endif
    </section>

    <section id="products-report" class="report-panel">
        <div class="report-panel-head"><div><p class="report-eyebrow">Product performance</p><h2>Products Report</h2><p>Top products by revenue, with cost-based estimated profit when linked product costs exist.</p></div></div>
        <div class="report-table-wrap"><table class="report-table">
            <thead><tr><th>Product</th><th>Size</th><th>Quantity sold</th><th>Revenue</th><th>Est. profit</th></tr></thead><tbody>
                @forelse($data['products'] as $product)
                    <tr><td><strong>{{ $product->product_name }}</strong></td><td>{{ $product->product_size ?: '—' }}</td><td>{{ number_format($product->quantity) }}</td><td>TSh {{ number_format($product->revenue, 2) }}</td><td>{{ $product->estimated_profit === null ? 'Unavailable' : 'TSh ' . number_format($product->estimated_profit, 2) }}</td></tr>
                @empty
                    <tr><td class="report-empty" colspan="5">No product sales data for this period.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <h3 class="report-subheading">Sales by size</h3>
        <div class="report-table-wrap"><table class="report-table">
            <thead><tr><th>Product size</th><th>Quantity sold</th><th>Revenue</th></tr></thead><tbody>
                @forelse($data['sizes'] as $size)
                    <tr><td>{{ $size->product_size }}</td><td>{{ number_format($size->quantity) }}</td><td>TSh {{ number_format($size->revenue, 2) }}</td></tr>
                @empty
                    <tr><td class="report-empty" colspan="3">No size sales data for this period.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section id="customers-report" class="report-panel">
        <div class="report-panel-head"><div><p class="report-eyebrow">Customer value</p><h2>Customers Report</h2><p>Customers ordered in the period, ranked by revenue. {{ number_format($data['new_customers']) }} new customer records were created in the selected dates.</p></div></div>
        <div class="report-table-wrap"><table class="report-table">
            <thead><tr><th>Customer</th><th>Orders</th><th>Revenue</th></tr></thead><tbody>
                @forelse($data['customers'] as $customer)
                    <tr><td><strong>{{ $customer->customer_name }}</strong></td><td>{{ number_format($customer->orders) }}</td><td>TSh {{ number_format($customer->revenue, 2) }}</td></tr>
                @empty
                    <tr><td class="report-empty" colspan="3">No customer orders for this period.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section id="communications-report" class="report-panel">
        <div class="report-panel-head"><div><p class="report-eyebrow">Workspace activity</p><h2>Communications &amp; Operations</h2><p>Existing messaging, catalogue and automation activity for this reporting period.</p></div></div>
        <div class="report-kpis report-kpis--compact">
            <article class="report-kpi"><span>Total Messages</span><strong>{{ number_format($data['messages']) }}</strong><small>Messages created in this period</small></article>
            <article class="report-kpi"><span>Inbound Messages</span><strong>{{ number_format($data['inbound_messages']) }}</strong><small>Customer messages received</small></article>
            <article class="report-kpi"><span>Outbound Messages</span><strong>{{ number_format($data['outbound_messages']) }}</strong><small>Messages sent by the business</small></article>
            <article class="report-kpi"><span>Active Products</span><strong>{{ number_format($data['active_products']) }}</strong><small>Active products in the catalogue</small></article>
            <article class="report-kpi"><span>Automation Runs</span><strong>{{ number_format($data['automation_runs']) }}</strong><small>Runs created in this period</small></article>
        </div>
    </section>

    <section id="payments-report" class="report-panel">
        <div class="report-panel-head"><div><p class="report-eyebrow">Collected and outstanding</p><h2>Payments Report</h2><p>Payment totals by the existing order payment status.</p></div></div>
        <div class="report-table-wrap"><table class="report-table">
            <thead><tr><th>Payment status</th><th>Orders</th><th>Order value</th></tr></thead><tbody>
                @forelse($data['payments'] as $payment)
                    <tr><td><span class="report-status">{{ str($payment->payment_status ?: 'not specified')->replace('_', ' ')->title() }}</span></td><td>{{ number_format($payment->orders) }}</td><td>TSh {{ number_format($payment->revenue, 2) }}</td></tr>
                @empty
                    <tr><td class="report-empty" colspan="3">No payment data for this period.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section id="delivery-report" class="report-panel">
        <div class="report-panel-head"><div><p class="report-eyebrow">Order fulfilment</p><h2>Delivery Report</h2><p>Order volumes by delivery status, including any statuses present in the data.</p></div></div>
        <div class="report-table-wrap"><table class="report-table">
            <thead><tr><th>Delivery status</th><th>Orders</th><th>Order value</th></tr></thead><tbody>
                @forelse($data['delivery'] as $delivery)
                    <tr><td><span class="report-status">{{ str($delivery['status'])->replace('_', ' ')->title() }}</span></td><td>{{ number_format($delivery['orders']) }}</td><td>TSh {{ number_format($delivery['revenue'], 2) }}</td></tr>
                @empty
                    <tr><td class="report-empty" colspan="3">No delivery data for this period.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section id="area-report" class="report-panel">
        <div class="report-panel-head"><div><p class="report-eyebrow">Delivery geography</p><h2>Area Report</h2><p>Order activity and completed deliveries by recorded delivery area.</p></div></div>
        <div class="report-table-wrap"><table class="report-table">
            <thead><tr><th>Delivery area</th><th>Orders</th><th>Revenue</th><th>Delivered orders</th></tr></thead><tbody>
                @forelse($data['areas'] as $area)
                    <tr><td><strong>{{ $area->area }}</strong></td><td>{{ number_format($area->orders) }}</td><td>TSh {{ number_format($area->revenue, 2) }}</td><td>{{ number_format($area->delivered_orders) }}</td></tr>
                @empty
                    <tr><td class="report-empty" colspan="4">No delivery areas recorded for this period.</td></tr>
                @endforelse
            </tbody>
        </table></div>
    </section>

    <section id="expenses-report" class="report-panel report-expenses">
        <div><p class="report-eyebrow">Expense tracking</p><h2>Expenses Report</h2><p>Expenses are unavailable / not configured because this system does not currently have an expenses data source. No expense values are estimated or fabricated.</p></div>
        <span class="report-unavailable">Not configured</span>
    </section>
</div>
@endsection

@push('scripts')
<script>
    document.querySelector('[data-report-print]')?.addEventListener('click', () => window.print());
</script>
@endpush
