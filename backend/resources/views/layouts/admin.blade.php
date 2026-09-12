<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Admin | Wonder Godoro Point')</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">

    @vite('resources/js/app.js')

    @stack('styles')
</head>

<body class="dashboard-page">

<div class="dashboard-shell">

    <aside class="dashboard-sidebar">

        <a class="dashboard-logo" href="{{ route('admin') }}">
            <img
                src="{{ asset('img/logo.png') }}"
                alt="Wonder Godoro Point Mattress Shop"
            >
            <span>Admin workspace</span>
        </a>

        <nav class="dashboard-nav" aria-label="Admin navigation">

            <p class="dashboard-nav-label">
                Workspace
            </p>

            {{-- Overview --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin') || request()->routeIs('admin.dashboard') ? 'is-active' : '' }}"
                href="{{ route('admin') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9632;</span>
                <span>Overview</span>
            </a>

            {{-- Customer Inbox --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.inbox*') ? 'is-active' : '' }}"
                href="{{ route('admin.inbox') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9993;</span>
                <span>Customer Inbox</span>
                <span class="nav-state">Open</span>
            </a>

            <p class="dashboard-nav-label dashboard-nav-label--later">
                Business
            </p>

            {{-- Products --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.products*') ? 'is-active' : '' }}"
                href="{{ route('admin.products.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9675;</span>
                <span>Products</span>
                <span class="nav-state">Open</span>
            </a>

            {{-- Customers --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.customers*') ? 'is-active' : '' }}"
                href="{{ route('admin.customers.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9823;</span>
                <span>Customers</span>
                <span class="nav-state">Open</span>
            </a>

            {{-- Orders --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.orders*') ? 'is-active' : '' }}"
                href="{{ route('admin.orders.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9675;</span>
                <span>Orders</span>
                <span class="nav-state">Open</span>
            </a>

            {{-- Delivery --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.delivery*') ? 'is-active' : '' }}"
                href="{{ route('admin.delivery.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9675;</span>
                <span>Delivery</span>
                <span class="nav-state">Open</span>
            </a>

            {{-- Reports --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.reports*') ? 'is-active' : '' }}"
                href="{{ route('admin.reports.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9675;</span>
                <span>Reports</span>
                <span class="nav-state">Open</span>
            </a>

            {{-- Settings --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.settings*') ? 'is-active' : '' }}"
                href="{{ route('admin.settings.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9675;</span>
                <span>Settings</span>
                <span class="nav-state">Open</span>
            </a>

        </nav>

        <div class="dashboard-sidebar-footer">

            <span class="dashboard-user-mark">
                {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
            </span>

            <span class="dashboard-user-details">
                <strong>{{ auth()->user()->name }}</strong>
                <small>Administrator</small>
            </span>

            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <button
                    type="submit"
                    class="dashboard-signout"
                    title="Sign out"
                    aria-label="Sign out"
                >
                    &#8594;
                </button>
            </form>

        </div>

    </aside>

    <main class="dashboard-main">

        @yield('content')

    </main>

</div>

@stack('scripts')

</body>
</html>