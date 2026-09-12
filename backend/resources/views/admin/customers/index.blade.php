@extends('layouts.admin')

@section('title', 'Customers | Wonder Godoro Point')

@section('content')

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Wonder Godoro Point</p>

        <h1>Customer CRM</h1>
    </div>

    <span class="dashboard-date">
        {{ now()->format('l, F j, Y') }}
    </span>
</header>

<section class="dashboard-welcome">
    <div>
        <p class="dashboard-section-kicker">
            Customer management
        </p>

        <h2>
            Know your customers. Grow every conversation.
        </h2>

        <p>
            Manage customer information, sales status, staff assignments
            and relationship tags from one workspace.
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

<section
    class="dashboard-module-section"
    aria-labelledby="customers-heading"
>
    <div class="dashboard-section-heading">
        <div>
            <p class="dashboard-section-kicker">
                Customer database
            </p>

            <h2 id="customers-heading">
                All Customers
            </h2>
        </div>

        <span class="dashboard-module-count">
            {{ $customers->total() }} customers
        </span>
    </div>

    <div style="margin-bottom: 24px;">
        <form
            method="GET"
            action="{{ route('admin.customers.index') }}"
            style="
                display: flex;
                gap: 12px;
                align-items: center;
                max-width: 720px;
            "
        >
            <input
                type="search"
                name="search"
                value="{{ $search }}"
                placeholder="Search by name, WhatsApp number or location..."
                style="
                    flex: 1;
                    min-height: 48px;
                    padding: 0 16px;
                    border: 1px solid #d9d9d9;
                    border-radius: 10px;
                    font: inherit;
                    background: #fff;
                "
            >

            <button
                type="submit"
                class="dashboard-primary-action"
                style="border: 0; cursor: pointer;"
            >
                Search
            </button>

            @if ($search !== '')
                <a
                    href="{{ route('admin.customers.index') }}"
                    style="
                        text-decoration: none;
                        white-space: nowrap;
                    "
                >
                    Clear
                </a>
            @endif
        </form>
    </div>

    @if (session('success'))
        <div
            role="status"
            style="
                margin-bottom: 20px;
                padding: 14px 16px;
                border-radius: 10px;
                background: #eef8f0;
                border: 1px solid #cde8d2;
            "
        >
            {{ session('success') }}
        </div>
    @endif

    @if ($customers->count() > 0)

        <div
            style="
                overflow-x: auto;
                background: #fff;
                border: 1px solid #e7e7e7;
                border-radius: 14px;
            "
        >
            <table
                style="
                    width: 100%;
                    min-width: 900px;
                    border-collapse: collapse;
                "
            >
                <thead>
                    <tr
                        style="
                            text-align: left;
                            border-bottom: 1px solid #e7e7e7;
                        "
                    >
                        <th style="padding: 16px;">Customer</th>
                        <th style="padding: 16px;">WhatsApp</th>
                        <th style="padding: 16px;">Location</th>
                        <th style="padding: 16px;">Status</th>
                        <th style="padding: 16px;">Assigned To</th>
                        <th style="padding: 16px;">Tags</th>
                        <th style="padding: 16px;">Last Contact</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($customers as $customer)

                        <tr
                            style="
                                border-bottom: 1px solid #f0f0f0;
                            "
                        >
                            <td style="padding: 16px;">
                                <a
                                    href="{{ route('admin.customers.show', $customer) }}"
                                    style="
                                        font-weight: 700;
                                        text-decoration: none;
                                    "
                                >
                                    {{ $customer->name }}
                                </a>

                                <div
                                    style="
                                        margin-top: 4px;
                                        font-size: 13px;
                                        opacity: .65;
                                    "
                                >
                                    {{ $customer->conversations_count }}
                                    conversation{{ $customer->conversations_count === 1 ? '' : 's' }}
                                </div>
                            </td>

                            <td style="padding: 16px;">
                                {{ $customer->phone ?: '—' }}
                            </td>

                            <td style="padding: 16px;">
                                {{ $customer->location ?: '—' }}
                            </td>

                            <td style="padding: 16px;">
                                @php
                                    $statusLabels = [
                                        'new' => 'New',
                                        'contacted' => 'Contacted',
                                        'interested' => 'Interested',
                                        'negotiating' => 'Negotiating',
                                        'won' => 'Won',
                                        'lost' => 'Lost',
                                    ];
                                @endphp

                                <span
                                    style="
                                        display: inline-block;
                                        padding: 6px 10px;
                                        border-radius: 999px;
                                        background: #f4f4f4;
                                        font-size: 13px;
                                        font-weight: 600;
                                    "
                                >
                                    {{ $statusLabels[$customer->status] ?? ucfirst($customer->status) }}
                                </span>
                            </td>

                            <td style="padding: 16px;">
                                {{ $customer->assignedUser?->name ?: 'Unassigned' }}
                            </td>

                            <td style="padding: 16px;">
                                @if ($customer->tags->isNotEmpty())

                                    <div
                                        style="
                                            display: flex;
                                            flex-wrap: wrap;
                                            gap: 6px;
                                        "
                                    >
                                        @foreach ($customer->tags as $tag)
                                            <span
                                                style="
                                                    display: inline-block;
                                                    padding: 5px 9px;
                                                    border-radius: 999px;
                                                    background: {{ $tag->color ?: '#f1f1f1' }};
                                                    font-size: 12px;
                                                    font-weight: 600;
                                                "
                                            >
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>

                                @else
                                    <span style="opacity: .5;">
                                        No tags
                                    </span>
                                @endif
                            </td>

                            <td style="padding: 16px;">
                                @if ($customer->last_contact_at)
                                    {{ $customer->last_contact_at->format('M j, Y H:i') }}
                                @else
                                    —
                                @endif
                            </td>
                        </tr>

                    @endforeach
                </tbody>
            </table>
        </div>

        <div style="margin-top: 24px;">
            {{ $customers->links() }}
        </div>

    @else

        <div
            style="
                padding: 48px 24px;
                text-align: center;
                background: #fff;
                border: 1px solid #e7e7e7;
                border-radius: 14px;
            "
        >
            <h3>
                {{ $search !== '' ? 'No customers found' : 'No customers yet' }}
            </h3>

            <p style="opacity: .7;">
                @if ($search !== '')
                    Try another name, WhatsApp number or location.
                @else
                    Customers created through WhatsApp conversations will
                    appear here.
                @endif
            </p>
        </div>

    @endif

</section>

@endsection