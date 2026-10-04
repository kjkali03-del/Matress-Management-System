@extends('layouts.admin')
@section('title', 'Reports | Wonder Godoro Point')
@push('styles')
<style>
.report-page{max-width:1200px;margin:0 auto;padding:28px 30px 48px;animation:wgp-report-in .55s cubic-bezier(.2,.8,.2,1) both}
@keyframes wgp-report-in{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:none}}
.report-head{display:flex;justify-content:space-between;align-items:flex-end;gap:24px;margin-bottom:22px}.report-head h1{margin:0;font-size:30px;letter-spacing:-.03em;color:var(--wgp-text)}.report-head p{margin:7px 0 0;color:var(--wgp-muted)}
.report-actions{display:flex;gap:9px;align-items:flex-end;flex-wrap:wrap}.report-filter{display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap}.report-filter label{display:flex;flex-direction:column;gap:5px;font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--wgp-muted)}.report-filter input{padding:10px 11px;border:1px solid var(--wgp-line);border-radius:10px;background:#fff;color:var(--wgp-text);outline:none}.report-filter input:focus{border-color:var(--wgp-gold);box-shadow:0 0 0 3px rgba(214,166,45,.13)}
.report-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:40px;padding:0 14px;border-radius:10px;border:1px solid var(--wgp-line);background:#fff;color:var(--wgp-text);font-size:12px;font-weight:800;text-decoration:none;cursor:pointer;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease,background .2s ease}.report-btn:hover{transform:translateY(-2px);box-shadow:0 9px 20px rgba(20,34,55,.1);border-color:rgba(214,166,45,.65)}.report-btn.primary{background:linear-gradient(135deg,var(--wgp-gold),var(--wgp-gold-2));border-color:transparent;color:#111827}.report-btn.dark{background:var(--wgp-navy);border-color:var(--wgp-navy);color:#fff}
.report-export{display:flex;gap:7px;flex-wrap:wrap}.report-export-title{width:100%;font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.12em;color:var(--wgp-muted);margin-bottom:1px}.report-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:12px}.report-card{background:#fff;border:1px solid var(--wgp-line);border-radius:15px;padding:17px 18px;box-shadow:var(--wgp-shadow);opacity:0;transform:translateY(10px);animation:wgp-report-card .55s cubic-bezier(.2,.8,.2,1) forwards}.report-card:nth-child(1){animation-delay:.04s}.report-card:nth-child(2){animation-delay:.08s}.report-card:nth-child(3){animation-delay:.12s}.report-card:nth-child(4){animation-delay:.16s}.report-card:nth-child(5){animation-delay:.20s}.report-card:nth-child(6){animation-delay:.24s}.report-card:nth-child(7){animation-delay:.28s}.report-card:nth-child(8){animation-delay:.32s}@keyframes wgp-report-card{to{opacity:1;transform:none}}.report-card span{display:block;font-size:11px;color:var(--wgp-muted);font-weight:700}.report-card strong{display:block;font-size:22px;margin-top:7px;color:var(--wgp-text);letter-spacing:-.02em}.report-card:first-child strong,.report-card:nth-child(3) strong{color:#9b7412}
.report-panel{margin-top:18px;background:#fff;border:1px solid var(--wgp-line);border-radius:15px;padding:20px;box-shadow:var(--wgp-shadow);animation:wgp-report-in .65s .15s both}.report-panel-head{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:15px}.report-panel h2{margin:0;font-size:18px;color:var(--wgp-text)}.report-panel p{margin:4px 0 0;color:var(--wgp-muted);font-size:12px}.report-table-wrap{overflow:auto}.report-table{width:100%;border-collapse:collapse}.report-table th,.report-table td{padding:12px;border-bottom:1px solid var(--wgp-line);text-align:left;font-size:13px}.report-table th{font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:var(--wgp-muted);background:#f8fafc}.report-table tr{transition:background .18s ease,transform .18s ease}.report-table tbody tr:hover{background:#fbfcfe}.report-empty{text-align:center!important;color:var(--wgp-muted);padding:28px!important}
@media(max-width:1050px){.report-head{align-items:flex-start;flex-direction:column}.report-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.report-page{padding:20px 16px 36px}.report-grid{grid-template-columns:1fr}.report-actions,.report-filter{width:100%}.report-filter>*{flex:1}.report-export{width:100%}.report-btn{flex:1}}
@media(prefers-reduced-motion:reduce){.report-page,.report-panel,.report-card{animation:none!important;opacity:1!important;transform:none!important}.report-btn,.report-table tr{transition:none!important}}
</style>
@endpush
@section('content')
<div class="report-page" data-wgp-reveal>
    <div class="report-head">
        <div><h1>Sales & Reports</h1><p>Sales performance, products, customers, communication and operations.</p></div>
        <div class="report-actions">
            <form class="report-filter" method="GET" action="{{ route('admin.reports.index') }}">
                <label>From<input type="date" name="from" value="{{ $data['from']->format('Y-m-d') }}"></label>
                <label>To<input type="date" name="to" value="{{ $data['to']->format('Y-m-d') }}"></label>
                <button class="report-btn primary" type="submit">Apply</button>
            </form>
            <div class="report-export">
                <span class="report-export-title">Download report</span>
                <a class="report-btn dark" href="{{ route('admin.reports.export.pdf', request()->only('from','to')) }}">PDF</a>
                <a class="report-btn" href="{{ route('admin.reports.export.excel', request()->only('from','to')) }}">Excel</a>
                <a class="report-btn" href="{{ route('admin.reports.export.docx', request()->only('from','to')) }}">Word (DOCX)</a>
            </div>
        </div>
    </div>

    <div class="report-grid">
        <div class="report-card"><span>Revenue</span><strong>TSh {{ number_format($data['revenue'],0) }}</strong></div>
        <div class="report-card"><span>Estimated COGS</span><strong>TSh {{ number_format($data['cogs'],0) }}</strong></div>
        <div class="report-card"><span>Gross Profit</span><strong>TSh {{ number_format($data['gross_profit'],0) }}</strong></div>
        <div class="report-card"><span>Orders</span><strong>{{ $data['orders'] }}</strong></div>
        <div class="report-card"><span>Paid Orders</span><strong>{{ $data['paid_orders'] }}</strong></div>
        <div class="report-card"><span>Delivered</span><strong>{{ $data['delivered_orders'] }}</strong></div>
        <div class="report-card"><span>New Customers</span><strong>{{ $data['new_customers'] }}</strong></div>
        <div class="report-card"><span>Inbound Messages</span><strong>{{ $data['inbound_messages'] }}</strong></div>
    </div>

    <div class="report-panel">
        <div class="report-panel-head"><div><h2>Top Products</h2><p>Best-performing products for the selected period.</p></div><span class="report-btn">{{ $topProducts->count() }} products</span></div>
        <div class="report-table-wrap"><table class="report-table"><thead><tr><th>Product</th><th>Quantity</th><th>Revenue</th></tr></thead><tbody>
        @forelse($topProducts as $product)
            <tr><td><strong>{{ $product->product_name }}</strong></td><td>{{ $product->quantity }}</td><td>TSh {{ number_format($product->revenue,0) }}</td></tr>
        @empty
            <tr><td class="report-empty" colspan="3">No sales data for this period.</td></tr>
        @endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
