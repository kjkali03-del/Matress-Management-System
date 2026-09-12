@extends('layouts.admin')

@section('title', 'Sales Pipeline')

@section('content')
<div class="space-y-6">

    {{-- Header --}}
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">
                Sales Pipeline
            </h1>

            <p class="mt-1 text-sm text-gray-500">
                Manage customers from first contact to completed sale.
            </p>
        </div>

        <a
            href="{{ route('admin.customers.index') }}"
            class="inline-flex items-center justify-center rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
        >
            View Customers
        </a>
    </div>

    {{-- Search --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
        <form
            method="GET"
            action="{{ route('admin.pipeline.index') }}"
            class="flex flex-col gap-3 sm:flex-row"
        >
            <div class="flex-1">
                <label
                    for="pipeline-search"
                    class="sr-only"
                >
                    Search customers
                </label>

                <input
                    id="pipeline-search"
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Search customer name, phone or location..."
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200"
                >
            </div>

            <button
                type="submit"
                class="rounded-lg bg-gray-900 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800"
            >
                Search
            </button>

            @if($search !== '')
                <a
                    href="{{ route('admin.pipeline.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50"
                >
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Success message --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Pipeline --}}
    <div class="overflow-x-auto pb-4">
        <div class="grid min-w-[1500px] grid-cols-6 gap-4">

            @foreach($stages as $stage => $stageLabel)

                @php
                    $stageData = $pipeline[$stage];
                    $stageCustomers = $stageData['customers'];
                @endphp

                <div class="flex min-h-[520px] flex-col rounded-xl border border-gray-200 bg-gray-50">

                    {{-- Column Header --}}
                    <div class="border-b border-gray-200 bg-white px-4 py-4 rounded-t-xl">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-gray-900">
                                    {{ $stageLabel }}
                                </h2>

                                <p class="mt-0.5 text-xs text-gray-500">
                                    {{ $stageCustomers->count() }}
                                    {{ $stageCustomers->count() === 1 ? 'customer' : 'customers' }}
                                </p>
                            </div>

                            <span class="inline-flex h-8 min-w-8 items-center justify-center rounded-full bg-gray-100 px-2 text-xs font-bold text-gray-700">
                                {{ $stageCustomers->count() }}
                            </span>
                        </div>
                    </div>

                    {{-- Customer Cards --}}
                    <div class="flex-1 space-y-3 p-3">

                        @forelse($stageCustomers as $customer)

                            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">

                                {{-- Customer --}}
                                <div class="flex items-start justify-between gap-3">

                                    <div class="min-w-0">
                                        <a
                                            href="{{ route('admin.customers.show', $customer) }}"
                                            class="block truncate text-sm font-bold text-gray-900 hover:text-blue-600"
                                        >
                                            {{ $customer->name ?: 'Unnamed Customer' }}
                                        </a>

                                        @if($customer->phone)
                                            <p class="mt-1 text-xs text-gray-500">
                                                {{ $customer->phone }}
                                            </p>
                                        @endif
                                    </div>

                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-sm font-bold text-gray-600">
                                        {{ strtoupper(substr($customer->name ?: 'C', 0, 1)) }}
                                    </div>

                                </div>

                                {{-- Location --}}
                                @if($customer->location)
                                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-500">
                                        <span>📍</span>
                                        <span class="truncate">
                                            {{ $customer->location }}
                                        </span>
                                    </div>
                                @endif

                                {{-- Tags --}}
                                @if($customer->tags->count())
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @foreach($customer->tags as $tag)
                                            <span
                                                class="rounded-full px-2 py-1 text-[10px] font-semibold"
                                                style="
                                                    background-color: {{ $tag->color ? $tag->color . '20' : '#f3f4f6' }};
                                                    color: {{ $tag->color ?: '#374151' }};
                                                "
                                            >
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- Customer Meta --}}
                                <div class="mt-4 grid grid-cols-2 gap-2 border-t border-gray-100 pt-3">

                                    <div>
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400">
                                            Conversations
                                        </p>

                                        <p class="mt-0.5 text-xs font-semibold text-gray-700">
                                            {{ $customer->conversations_count ?? 0 }}
                                        </p>
                                    </div>

                                    <div>
                                        <p class="text-[10px] uppercase tracking-wide text-gray-400">
                                            Assigned
                                        </p>

                                        <p class="mt-0.5 truncate text-xs font-semibold text-gray-700">
                                            {{ $customer->assignedUser?->name ?? 'Unassigned' }}
                                        </p>
                                    </div>

                                </div>

                                {{-- Last Contact --}}
                                @if($customer->last_contact_at)
                                    <div class="mt-3 text-[10px] text-gray-400">
                                        Last contact:
                                        {{ $customer->last_contact_at->diffForHumans() }}
                                    </div>
                                @endif

                                {{-- Actions --}}
                                <div class="mt-4 flex gap-2">

                                    <a
                                        href="{{ route('admin.customers.show', $customer) }}"
                                        class="flex-1 rounded-lg border border-gray-300 px-2 py-2 text-center text-xs font-semibold text-gray-700 transition hover:bg-gray-50"
                                    >
                                        Customer
                                    </a>

                                    @php
                                        $conversation = $customer->conversations
                                            ->sortByDesc('last_message_at')
                                            ->first();
                                    @endphp

                                    @if($conversation)
                                        <a
                                            href="{{ route('admin.inbox', ['conversation' => $conversation->id]) }}"
                                            class="flex-1 rounded-lg bg-gray-900 px-2 py-2 text-center text-xs font-semibold text-white transition hover:bg-gray-800"
                                        >
                                            Inbox
                                        </a>
                                    @endif

                                </div>

                                {{-- Move Stage --}}
                                <form
                                    method="POST"
                                    action="{{ route('admin.pipeline.customers.status.update', $customer) }}"
                                    class="mt-3"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <label
                                        for="stage-{{ $customer->id }}"
                                        class="sr-only"
                                    >
                                        Move customer
                                    </label>

                                    <select
                                        id="stage-{{ $customer->id }}"
                                        name="status"
                                        onchange="this.form.submit()"
                                        class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs font-medium text-gray-700 outline-none transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200"
                                    >
                                        @foreach($stages as $optionStage => $optionLabel)
                                            <option
                                                value="{{ $optionStage }}"
                                                @selected($customer->status === $optionStage)
                                            >
                                                Move to {{ $optionLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>

                            </div>

                        @empty

                            <div class="flex min-h-[300px] flex-col items-center justify-center px-4 text-center">
                                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white text-xl shadow-sm">
                                    📋
                                </div>

                                <p class="mt-3 text-sm font-semibold text-gray-700">
                                    No customers
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    Customers in this stage will appear here.
                                </p>
                            </div>

                        @endforelse

                    </div>

                </div>

            @endforeach

        </div>
    </div>

</div>
@endsection