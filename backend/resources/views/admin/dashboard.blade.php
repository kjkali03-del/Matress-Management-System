@extends('layouts.admin')

@section('title', 'Admin Dashboard | Wonder Godoro Point')

@section('content')

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Wonder Godoro Point</p>

        <h1>
            Good morning, {{ auth()->user()->name }}.
        </h1>
    </div>

    <span class="dashboard-date">
        {{ now()->format('l, F j, Y') }}
    </span>
</header>

<section
    class="dashboard-welcome"
    aria-labelledby="dashboard-heading"
>
    <div>

        <p class="dashboard-section-kicker">
            Your workspace
        </p>

        <h2 id="dashboard-heading">
            Everything starts with a thoughtful conversation.
        </h2>

        <p>
            Stay close to every customer interaction from one calm,
            focused workspace.
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
    aria-labelledby="module-heading"
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
            02 active
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