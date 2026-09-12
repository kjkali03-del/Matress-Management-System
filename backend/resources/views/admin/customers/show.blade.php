@extends('layouts.admin')

@section('title', $customer->name . ' | Customer CRM | Wonder Godoro Point')

@section('content')

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

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Customer CRM</p>

        <h1>{{ $customer->name }}</h1>
    </div>

    <a
        href="{{ route('admin.customers.index') }}"
        style="
            text-decoration: none;
            font-weight: 600;
        "
    >
        &larr; All Customers
    </a>
</header>

@if (session('success'))
    <div
        role="status"
        style="
            margin-bottom: 24px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #eef8f0;
            border: 1px solid #cde8d2;
        "
    >
        {{ session('success') }}
    </div>
@endif

@if ($errors->any())
    <div
        role="alert"
        style="
            margin-bottom: 24px;
            padding: 14px 16px;
            border-radius: 10px;
            background: #fff3f3;
            border: 1px solid #f0cccc;
        "
    >
        <strong>Please correct the following:</strong>

        <ul style="margin: 8px 0 0 20px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section
    class="dashboard-welcome"
    aria-labelledby="customer-profile-heading"
>
    <div>
        <p class="dashboard-section-kicker">
            Customer profile
        </p>

        <h2 id="customer-profile-heading">
            {{ $customer->name }}
        </h2>

        <p>
            {{ $customer->phone ?: 'No WhatsApp number available' }}
        </p>
    </div>

    <a
        class="dashboard-primary-action"
        href="{{ route('admin.inbox', ['conversation' => optional($customer->conversations->first())->id]) }}"
    >
        Open Inbox
        <span aria-hidden="true">&rarr;</span>
    </a>
</section>

<section
    class="dashboard-module-section"
    aria-labelledby="customer-details-heading"
>
    <div class="dashboard-section-heading">
        <div>
            <p class="dashboard-section-kicker">
                CRM information
            </p>

            <h2 id="customer-details-heading">
                Customer Details
            </h2>
        </div>

        <span class="dashboard-module-count">
            {{ $customer->conversations->count() }}
            conversation{{ $customer->conversations->count() === 1 ? '' : 's' }}
        </span>
    </div>

    <form
        method="POST"
        action="{{ route('admin.customers.update', $customer) }}"
    >
        @csrf
        @method('PATCH')

        <div
            style="
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 20px;
            "
        >

            <div>
                <label
                    for="name"
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    Customer Name
                </label>

                <input
                    id="name"
                    type="text"
                    name="name"
                    value="{{ old('name', $customer->name) }}"
                    required
                    style="
                        width: 100%;
                        min-height: 48px;
                        padding: 0 14px;
                        border: 1px solid #d9d9d9;
                        border-radius: 10px;
                        font: inherit;
                        background: #fff;
                    "
                >
            </div>

            <div>
                <label
                    for="phone"
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    WhatsApp Number
                </label>

                <input
                    id="phone"
                    type="text"
                    name="phone"
                    value="{{ old('phone', $customer->phone) }}"
                    style="
                        width: 100%;
                        min-height: 48px;
                        padding: 0 14px;
                        border: 1px solid #d9d9d9;
                        border-radius: 10px;
                        font: inherit;
                        background: #fff;
                    "
                >
            </div>

            <div>
                <label
                    for="location"
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    Location
                </label>

                <input
                    id="location"
                    type="text"
                    name="location"
                    value="{{ old('location', $customer->location) }}"
                    placeholder="e.g. Dar es Salaam"
                    style="
                        width: 100%;
                        min-height: 48px;
                        padding: 0 14px;
                        border: 1px solid #d9d9d9;
                        border-radius: 10px;
                        font: inherit;
                        background: #fff;
                    "
                >
            </div>

            <div>
                <label
                    for="status"
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    Sales Status
                </label>

                <select
                    id="status"
                    name="status"
                    required
                    style="
                        width: 100%;
                        min-height: 48px;
                        padding: 0 14px;
                        border: 1px solid #d9d9d9;
                        border-radius: 10px;
                        font: inherit;
                        background: #fff;
                    "
                >
                    @foreach ($statusLabels as $value => $label)
                        <option
                            value="{{ $value }}"
                            @selected(old('status', $customer->status) === $value)
                        >
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    for="assigned_to"
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    Assigned Staff
                </label>

                <select
                    id="assigned_to"
                    name="assigned_to"
                    style="
                        width: 100%;
                        min-height: 48px;
                        padding: 0 14px;
                        border: 1px solid #d9d9d9;
                        border-radius: 10px;
                        font: inherit;
                        background: #fff;
                    "
                >
                    <option value="">
                        Unassigned
                    </option>

                    @foreach ($users as $user)
                        <option
                            value="{{ $user->id }}"
                            @selected((string) old('assigned_to', $customer->assigned_to) === (string) $user->id)
                        >
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    Last Contact
                </label>

                <div
                    style="
                        min-height: 48px;
                        display: flex;
                        align-items: center;
                        padding: 0 14px;
                        border: 1px solid #e7e7e7;
                        border-radius: 10px;
                        background: #f8f8f8;
                    "
                >
                    {{ $customer->last_contact_at?->format('M j, Y H:i') ?: 'No contact recorded' }}
                </div>
            </div>

            <div style="grid-column: 1 / -1;">
                <label
                    for="notes"
                    style="
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                    "
                >
                    Customer Notes
                </label>

                <textarea
                    id="notes"
                    name="notes"
                    rows="6"
                    placeholder="Add important information about this customer..."
                    style="
                        width: 100%;
                        padding: 14px;
                        border: 1px solid #d9d9d9;
                        border-radius: 10px;
                        font: inherit;
                        resize: vertical;
                        background: #fff;
                    "
                >{{ old('notes', $customer->notes) }}</textarea>
            </div>

            <div style="grid-column: 1 / -1;">
                <label
                    style="
                        display: block;
                        margin-bottom: 10px;
                        font-weight: 600;
                    "
                >
                    Customer Tags
                </label>

                <div
                    style="
                        display: flex;
                        flex-wrap: wrap;
                        gap: 10px;
                    "
                >
                    @foreach ($tags as $tag)
                        <label
                            style="
                                display: inline-flex;
                                align-items: center;
                                gap: 7px;
                                padding: 8px 12px;
                                border: 1px solid #e1e1e1;
                                border-radius: 999px;
                                background: #fff;
                                cursor: pointer;
                            "
                        >
                            <input
                                type="checkbox"
                                name="tags[]"
                                value="{{ $tag->id }}"
                                @checked(
                                    in_array(
                                        $tag->id,
                                        old(
                                            'tags',
                                            $customer->tags->pluck('id')->all()
                                        )
                                    )
                                )
                            >

                            <span>
                                {{ $tag->name }}
                            </span>
                        </label>
                    @endforeach
                </div>

                @if ($tags->isEmpty())
                    <p style="opacity: .65;">
                        No tags have been created yet.
                    </p>
                @endif
            </div>

        </div>

        <div style="margin-top: 28px;">
            <button
                type="submit"
                class="dashboard-primary-action"
                style="
                    border: 0;
                    cursor: pointer;
                "
            >
                Save Customer
                <span aria-hidden="true">&rarr;</span>
            </button>
        </div>

    </form>

</section>

<section
    class="dashboard-module-section"
    aria-labelledby="conversation-history-heading"
>
    <div class="dashboard-section-heading">
        <div>
            <p class="dashboard-section-kicker">
                Customer activity
            </p>

            <h2 id="conversation-history-heading">
                Conversation History
            </h2>
        </div>
    </div>

    @if ($customer->conversations->isNotEmpty())

        <div
            style="
                display: grid;
                gap: 12px;
            "
        >
            @foreach ($customer->conversations as $conversation)

                <a
                    href="{{ route('admin.inbox', ['conversation' => $conversation->id]) }}"
                    style="
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        gap: 20px;
                        padding: 18px;
                        border: 1px solid #e7e7e7;
                        border-radius: 12px;
                        background: #fff;
                        text-decoration: none;
                    "
                >
                    <div>
                        <strong>
                            Conversation #{{ $conversation->id }}
                        </strong>

                        <div
                            style="
                                margin-top: 5px;
                                font-size: 13px;
                                opacity: .65;
                            "
                        >
                            {{ $conversation->last_message_at?->format('M j, Y H:i') ?: 'No activity' }}
                        </div>
                    </div>

                    <span aria-hidden="true">
                        &rarr;
                    </span>
                </a>

            @endforeach
        </div>

    @else

        <div
            style="
                padding: 40px 24px;
                text-align: center;
                border: 1px solid #e7e7e7;
                border-radius: 14px;
                background: #fff;
            "
        >
            <h3>No conversations yet</h3>

            <p style="opacity: .65;">
                Conversation history will appear here when this customer
                interacts with Wonder Godoro Point.
            </p>
        </div>

    @endif

</section>

@endsection