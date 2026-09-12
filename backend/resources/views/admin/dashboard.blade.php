@extends('layouts.admin')

@section('title', 'CRM Dashboard | Wonder Godoro Point')

@section('content')

@php
    use App\Models\Customer;

    $totalCustomers = Customer::count();

    $newCustomers = Customer::where('status', 'new')->count();

    $contactedCustomers = Customer::where('status', 'contacted')->count();

    $interestedCustomers = Customer::where('status', 'interested')->count();

    $negotiatingCustomers = Customer::where('status', 'negotiating')->count();

    $wonCustomers = Customer::where('status', 'won')->count();

    $lostCustomers = Customer::where('status', 'lost')->count();

    $pipelineTotal = $newCustomers
        + $contactedCustomers
        + $interestedCustomers
        + $negotiatingCustomers
        + $wonCustomers;

    $recentCustomers = Customer::query()
        ->with('tags')
        ->latest('last_contact_at')
        ->latest('id')
        ->limit(6)
        ->get();
@endphp

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">
            Wonder Godoro Point
        </p>

        <h1>
            Good morning, {{ auth()->user()->name }}.
        </h1>
    </div>

    <span class="dashboard-date">
        {{ now()->format('l, F j, Y') }}
    </span>
</header>

{{-- Welcome --}}
<section
    class="dashboard-welcome"
    aria-labelledby="dashboard-heading"
>
    <div>
        <p class="dashboard-section-kicker">
            CRM Workspace
        </p>

        <h2 id="dashboard-heading">
            Know your customers. Follow every opportunity.
        </h2>

        <p>
            Manage customer conversations, sales opportunities and
            follow-ups from one focused workspace.
        </p>
    </div>

    <a
        class="dashboard-primary-action"
        href="{{ route('admin.inbox') }}"
    >
        Open Customer Inbox
        <span aria-hidden="true">&rarr;</span>
    </a>
</section>

{{-- CRM Statistics --}}
<section
    class="dashboard-module-section"
    aria-labelledby="crm-stats-heading"
>
    <div class="dashboard-section-heading">
        <div>
            <p class="dashboard-section-kicker">
                Customer CRM
            </p>

            <h2 id="crm-stats-heading">
                Business overview
            </h2>
        </div>

        <a
            href="{{ route('admin.customers.index') }}"
            class="dashboard-module-count"
            style="text-decoration: none;"
        >
            View customers &rarr;
        </a>
    </div>

    <div
        style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
            gap:16px;
            margin-top:20px;
        "
    >

        {{-- Total --}}
        <a
            href="{{ route('admin.customers.index') }}"
            style="
                text-decoration:none;
                color:inherit;
                padding:22px;
                border:1px solid rgba(0,0,0,.08);
                border-radius:18px;
                background:#fff;
                box-shadow:0 8px 25px rgba(0,0,0,.04);
            "
        >
            <span style="font-size:13px;opacity:.65;">
                Total Customers
            </span>

            <strong
                style="
                    display:block;
                    margin-top:8px;
                    font-size:32px;
                "
            >
                {{ $totalCustomers }}
            </strong>
        </a>

        {{-- New --}}
        <a
            href="{{ route('admin.customers.index') }}"
            style="
                text-decoration:none;
                color:inherit;
                padding:22px;
                border:1px solid rgba(0,0,0,.08);
                border-radius:18px;
                background:#fff;
                box-shadow:0 8px 25px rgba(0,0,0,.04);
            "
        >
            <span style="font-size:13px;opacity:.65;">
                New Leads
            </span>

            <strong
                style="
                    display:block;
                    margin-top:8px;
                    font-size:32px;
                "
            >
                {{ $newCustomers }}
            </strong>
        </a>

        {{-- Contacted --}}
        <a
            href="{{ route('admin.customers.index') }}"
            style="
                text-decoration:none;
                color:inherit;
                padding:22px;
                border:1px solid rgba(0,0,0,.08);
                border-radius:18px;
                background:#fff;
                box-shadow:0 8px 25px rgba(0,0,0,.04);
            "
        >
            <span style="font-size:13px;opacity:.65;">
                Contacted
            </span>

            <strong
                style="
                    display:block;
                    margin-top:8px;
                    font-size:32px;
                "
            >
                {{ $contactedCustomers }}
            </strong>
        </a>

        {{-- Interested --}}
        <a
            href="{{ route('admin.customers.index') }}"
            style="
                text-decoration:none;
                color:inherit;
                padding:22px;
                border:1px solid rgba(0,0,0,.08);
                border-radius:18px;
                background:#fff;
                box-shadow:0 8px 25px rgba(0,0,0,.04);
            "
        >
            <span style="font-size:13px;opacity:.65;">
                Interested
            </span>

            <strong
                style="
                    display:block;
                    margin-top:8px;
                    font-size:32px;
                "
            >
                {{ $interestedCustomers }}
            </strong>
        </a>

        {{-- Negotiating --}}
        <a
            href="{{ route('admin.customers.index') }}"
            style="
                text-decoration:none;
                color:inherit;
                padding:22px;
                border:1px solid rgba(0,0,0,.08);
                border-radius:18px;
                background:#fff;
                box-shadow:0 8px 25px rgba(0,0,0,.04);
            "
        >
            <span style="font-size:13px;opacity:.65;">
                Negotiating
            </span>

            <strong
                style="
                    display:block;
                    margin-top:8px;
                    font-size:32px;
                "
            >
                {{ $negotiatingCustomers }}
            </strong>
        </a>

        {{-- Won --}}
        <a
            href="{{ route('admin.customers.index') }}"
            style="
                text-decoration:none;
                color:inherit;
                padding:22px;
                border:1px solid rgba(0,0,0,.08);
                border-radius:18px;
                background:#fff;
                box-shadow:0 8px 25px rgba(0,0,0,.04);
            "
        >
            <span style="font-size:13px;opacity:.65;">
                Won
            </span>

            <strong
                style="
                    display:block;
                    margin-top:8px;
                    font-size:32px;
                "
            >
                {{ $wonCustomers }}
            </strong>
        </a>

    </div>
</section>

{{-- Sales Pipeline --}}
<section
    class="dashboard-module-section"
    aria-labelledby="pipeline-heading"
    style="margin-top:32px;"
>
    <div class="dashboard-section-heading">

        <div>
            <p class="dashboard-section-kicker">
                Sales Pipeline
            </p>

            <h2 id="pipeline-heading">
                Customer opportunities
            </h2>
        </div>

        <span class="dashboard-module-count">
            {{ $pipelineTotal }} active
        </span>

    </div>

    <div
        style="
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
            gap:12px;
            margin-top:20px;
        "
    >

        @php
            $pipeline = [
                [
                    'label' => 'New',
                    'value' => $newCustomers,
                ],
                [
                    'label' => 'Contacted',
                    'value' => $contactedCustomers,
                ],
                [
                    'label' => 'Interested',
                    'value' => $interestedCustomers,
                ],
                [
                    'label' => 'Negotiating',
                    'value' => $negotiatingCustomers,
                ],
                [
                    'label' => 'Won',
                    'value' => $wonCustomers,
                ],
            ];
        @endphp

        @foreach ($pipeline as $stage)
            <div
                style="
                    min-height:120px;
                    padding:18px;
                    border-radius:16px;
                    background:rgba(255,255,255,.7);
                    border:1px solid rgba(0,0,0,.07);
                "
            >
                <span
                    style="
                        display:block;
                        font-size:13px;
                        opacity:.65;
                    "
                >
                    {{ $stage['label'] }}
                </span>

                <strong
                    style="
                        display:block;
                        margin-top:10px;
                        font-size:30px;
                    "
                >
                    {{ $stage['value'] }}
                </strong>

                <div
                    style="
                        height:5px;
                        margin-top:14px;
                        border-radius:10px;
                        background:rgba(0,0,0,.08);
                        overflow:hidden;
                    "
                >
                    <div
                        style="
                            height:100%;
                            width:{{ $pipelineTotal > 0 ? min(100, ($stage['value'] / $pipelineTotal) * 100) : 0 }}%;
                            background:#c8a44d;
                            border-radius:10px;
                        "
                    ></div>
                </div>
            </div>
        @endforeach

    </div>
</section>

{{-- Recent Customers --}}
<section
    class="dashboard-module-section"
    aria-labelledby="recent-customers-heading"
    style="margin-top:32px;"
>
    <div class="dashboard-section-heading">

        <div>
            <p class="dashboard-section-kicker">
                Customer Activity
            </p>

            <h2 id="recent-customers-heading">
                Recent customers
            </h2>
        </div>

        <a
            href="{{ route('admin.customers.index') }}"
            class="dashboard-module-count"
            style="text-decoration:none;"
        >
            View all &rarr;
        </a>

    </div>

    <div
        style="
            margin-top:20px;
            overflow-x:auto;
            border:1px solid rgba(0,0,0,.08);
            border-radius:18px;
            background:#fff;
        "
    >

        @forelse ($recentCustomers as $customer)

            <a
                href="{{ route('admin.customers.show', $customer) }}"
                style="
                    display:flex;
                    align-items:center;
                    justify-content:space-between;
                    gap:20px;
                    padding:18px 20px;
                    text-decoration:none;
                    color:inherit;
                    border-bottom:1px solid rgba(0,0,0,.06);
                "
            >

                <div style="min-width:180px;">
                    <strong>
                        {{ $customer->name ?: 'Unnamed Customer' }}
                    </strong>

                    <span
                        style="
                            display:block;
                            margin-top:4px;
                            font-size:13px;
                            opacity:.6;
                        "
                    >
                        {{ $customer->phone ?: 'No phone number' }}
                    </span>
                </div>

                <div style="min-width:120px;">
                    <span
                        style="
                            display:inline-block;
                            padding:6px 10px;
                            border-radius:999px;
                            background:rgba(200,164,77,.12);
                            font-size:12px;
                            text-transform:capitalize;
                        "
                    >
                        {{ $customer->status }}
                    </span>
                </div>

                <div style="min-width:120px;text-align:right;">
                    <span
                        style="
                            font-size:13px;
                            opacity:.65;
                        "
                    >
                        {{ $customer->location ?: 'Location not set' }}
                    </span>
                </div>

                <span
                    style="
                        font-size:20px;
                        opacity:.55;
                    "
                    aria-hidden="true"
                >
                    &rarr;
                </span>

            </a>

        @empty

            <div style="padding:40px;text-align:center;">
                <strong>
                    No customers yet
                </strong>

                <p style="opacity:.65;margin-top:8px;">
                    Customers will appear here when conversations arrive.
                </p>
            </div>

        @endforelse

    </div>
</section>

{{-- Business Modules --}}
<section
    class="dashboard-module-section"
    aria-labelledby="module-heading"
    style="margin-top:32px;"
>

    <div class="dashboard-section-heading">

        <div>

            <p class="dashboard-section-kicker">
                Business modules
            </p>

            <h2 id="module-heading">
                Your operations, in one place
            </h2>

        </div>

        <span class="dashboard-module-count">
            04 active
        </span>

    </div>

    <div class="dashboard-module-grid">

        <a
            class="dashboard-module-card dashboard-module-card--active"
            href="{{ route('admin.inbox') }}"
        >
            <span class="module-card-icon" aria-hidden="true">
                &#9993;
            </span>

            <span class="module-card-content">
                <span class="module-card-label">
                    Active module
                </span>

                <strong>Customer Inbox</strong>

                <span>
                    Manage customer conversations and replies.
                </span>
            </span>

            <span
                class="module-card-arrow"
                aria-hidden="true"
            >
                &rarr;
            </span>
        </a>

        <a
            class="dashboard-module-card dashboard-module-card--active"
            href="{{ route('admin.customers.index') }}"
        >
            <span class="module-card-icon" aria-hidden="true">
                &#9823;
            </span>

            <span class="module-card-content">
                <span class="module-card-label">
                    Active module
                </span>

                <strong>Customers</strong>

                <span>
                    Manage customer profiles, status, tags and assignments.
                </span>
            </span>

            <span
                class="module-card-arrow"
                aria-hidden="true"
            >
                &rarr;
            </span>
        </a>

        <a
            class="dashboard-module-card dashboard-module-card--active"
            href="{{ route('admin.tags.index') }}"
        >
            <span class="module-card-icon" aria-hidden="true">
                &#9733;
            </span>

            <span class="module-card-content">
                <span class="module-card-label">
                    Active module
                </span>

                <strong>Customer Tags</strong>

                <span>
                    Organize customers using powerful CRM tags.
                </span>
            </span>

            <span
                class="module-card-arrow"
                aria-hidden="true"
            >
                &rarr;
            </span>
        </a>

        <a
            class="dashboard-module-card dashboard-module-card--active"
            href="{{ route('admin.products.index') }}"
        >
            <span class="module-card-icon" aria-hidden="true">
                &#9675;
            </span>

            <span class="module-card-content">
                <span class="module-card-label">
                    Active module
                </span>

                <strong>Products</strong>

                <span>
                    Manage mattress types, sizes, prices and availability.
                </span>
            </span>

            <span
                class="module-card-arrow"
                aria-hidden="true"
            >
                &rarr;
            </span>
        </a>

    </div>

</section>

@endsection