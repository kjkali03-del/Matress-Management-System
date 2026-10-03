<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Admin | Wonder Godoro Point')</title>

    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-ui.css') }}">
    <link rel="stylesheet" href="{{ asset('css/wgp-premium.css') }}">

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

            {{-- Pipeline --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.pipeline*') ? 'is-active' : '' }}"
                href="{{ route('admin.pipeline.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#8594;</span>
                <span>Pipeline</span>
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

            {{-- Automations --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.automations*') ? 'is-active' : '' }}"
                href="{{ route('admin.automations.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">&#9881;</span>
                <span>Automations</span>
                <span class="nav-state">Open</span>
            </a>

            {{-- Tags --}}
            <a
                class="dashboard-nav-item {{ request()->routeIs('admin.tags*') ? 'is-active' : '' }}"
                href="{{ route('admin.tags.index') }}"
            >
                <span class="nav-icon" aria-hidden="true">#</span>
                <span>Tags</span>
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

        <header class="wgp-appbar">
            <button class="wgp-mobile-menu" type="button" aria-label="Open navigation" aria-expanded="false">
                <span></span><span></span><span></span>
            </button>
            <div class="wgp-search" role="search">
                <span aria-hidden="true">⌕</span>
                <input type="search" placeholder="Search anything..." aria-label="Search anything">
                <kbd>⌘ K</kbd>
            </div>
            <div class="wgp-appbar-actions">
                <button class="wgp-icon-button" type="button" aria-label="Notifications"><span>♧</span><i>3</i></button>
                <div class="wgp-admin-chip">
                    <span class="wgp-admin-avatar">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                    <span><strong>{{ auth()->user()->name }}</strong><small>Store Manager</small></span>
                    <span class="wgp-chevron">⌄</span>
                </div>
            </div>
        </header>

        @yield('content')

    </main>

</div>

<script src="{{ asset('js/wgp-premium.js') }}" defer></script>
@stack('scripts')

</body>
</html>