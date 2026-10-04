@extends('layouts.admin')
@section('title','Sales Pipeline | Wonder Godoro Point')
@section('content')
<div class="pipeline-page">
    <div class="pipeline-head">
        <div><h1>Sales Pipeline</h1><p>Move customers from first contact to completed sale.</p></div>
        <a class="dashboard-primary-action" href="{{ route('admin.customers.index') }}">View Customers</a>
    </div>
    <div class="pipeline-toolbar">
        <form method="GET" action="{{ route('admin.pipeline.index') }}" style="display:flex;gap:.65rem;flex:1">
            <input name="search" value="{{ $search }}" placeholder="Search customer name, phone or location..." aria-label="Search customers">
            <button type="submit">Search</button>
            @if($search !== '')<a href="{{ route('admin.pipeline.index') }}">Clear</a>@endif
        </form>
    </div>
    @if(session('success'))<div class="wgp-toast" style="margin-bottom:1rem">{{ session('success') }}</div>@endif
    <div class="pipeline-board">
        @foreach($stages as $stage => $stageLabel)
            @php $stageCustomers=$pipeline[$stage]['customers']; @endphp
            <section class="pipeline-column" data-wgp-reveal>
                <header class="pipeline-column-head"><div><strong>{{ $stageLabel }}</strong><small>{{ $stageCustomers->count() }} {{ $stageCustomers->count()===1?'customer':'customers' }}</small></div><span class="pipeline-count">{{ $stageCustomers->count() }}</span></header>
                <div class="pipeline-cards">
                    @forelse($stageCustomers as $customer)
                        <article class="pipeline-card">
                            <div style="display:flex;justify-content:space-between;gap:.6rem">
                                <div style="min-width:0"><a class="customer-name" href="{{ route('admin.customers.show',$customer) }}">{{ $customer->name ?: 'Unnamed Customer' }}</a><div class="customer-meta">{{ $customer->phone ?: 'No phone' }}</div></div>
                                <span class="wgp-avatar">{{ str($customer->name ?: 'C')->substr(0,1)->upper() }}</span>
                            </div>
                            @if($customer->location)<div class="customer-meta" style="margin-top:.55rem">📍 {{ $customer->location }}</div>@endif
                            @if($customer->tags->isNotEmpty())<div style="display:flex;gap:.3rem;flex-wrap:wrap;margin-top:.55rem">@foreach($customer->tags as $tag)<span class="wgp-status" style="background:{{ $tag->color ? $tag->color.'20' : '#eef1f5' }};color:{{ $tag->color ?: '#536176' }}">{{ $tag->name }}</span>@endforeach</div>@endif
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.6rem;margin-top:.7rem;padding-top:.65rem;border-top:1px solid #eef1f4"><div><small style="color:#8995a5;font-size:.58rem">CONVERSATIONS</small><div style="font-weight:800;font-size:.72rem">{{ $customer->conversations_count }}</div></div><div><small style="color:#8995a5;font-size:.58rem">ASSIGNED</small><div style="font-weight:800;font-size:.72rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $customer->assignedUser?->name ?? 'Unassigned' }}</div></div></div>
                            @if($customer->last_contact_at)<div class="customer-meta" style="margin-top:.55rem">Last contact {{ $customer->last_contact_at->diffForHumans() }}</div>@endif
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.45rem;margin-top:.7rem"><a href="{{ route('admin.customers.show',$customer) }}" class="pipeline-toolbar-link" style="text-align:center;border:1px solid #e1e6ed;border-radius:8px;padding:.5rem;font-size:.64rem;font-weight:800;color:#536176">Customer</a>@if($customer->latestConversation)<a href="{{ route('admin.inbox',['conversation'=>$customer->latestConversation->id]) }}" style="text-align:center;background:#0b1728;color:#fff;border-radius:8px;padding:.5rem;font-size:.64rem;font-weight:800">Inbox</a>@endif</div>
                            <form method="POST" action="{{ route('admin.pipeline.customers.status.update',$customer) }}">@csrf @method('PATCH')<select name="status" onchange="this.form.submit()" aria-label="Move customer"><option value="">Move to…</option>@foreach($stages as $optionStage=>$optionLabel)<option value="{{ $optionStage }}" @selected($customer->status===$optionStage)>{{ $optionLabel }}</option>@endforeach</select></form>
                        </article>
                    @empty
                        <div style="padding:3rem 1rem;text-align:center;color:#8a96a7;font-size:.68rem">No customers in this stage.</div>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
@endsection
