<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin | Wonder Godoro Point</title>
        <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    </head>
    <body class="dashboard-page">
        <div class="dashboard-shell">
            <aside class="dashboard-sidebar">
                <a class="dashboard-logo" href="{{ route('admin') }}">
                    <img src="{{ asset('img/logo.png') }}" alt="Wonder Godoro Point Mattress Shop">
                    <span>Admin workspace</span>
                </a>

                <nav class="dashboard-nav" aria-label="Admin navigation">
                    <p class="dashboard-nav-label">Workspace</p>
                    <a class="dashboard-nav-item is-active" href="{{ route('admin') }}">
                        <span class="nav-icon" aria-hidden="true">&#9632;</span>
                        <span>Overview</span>
                    </a>
                    <a class="dashboard-nav-item" href="{{ route('admin.inbox') }}">
                        <span class="nav-icon" aria-hidden="true">&#9993;</span>
                        <span>Customer Inbox</span>
                        <span class="nav-state">Open</span>
                    </a>

                    <p class="dashboard-nav-label dashboard-nav-label--later">Coming later</p>
                    @foreach (['Customers', 'Orders', 'Products', 'Delivery', 'Reports', 'Settings'] as $module)
                        <span class="dashboard-nav-item is-disabled" aria-disabled="true">
                            <span class="nav-icon" aria-hidden="true">&#9675;</span>
                            <span>{{ $module }}</span>
                            <span class="nav-state">Soon</span>
                        </span>
                    @endforeach
                </nav>

                <div class="dashboard-sidebar-footer">
                    <span class="dashboard-user-mark">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
                    <span class="dashboard-user-details">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small>Administrator</small>
                    </span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dashboard-signout" title="Sign out" aria-label="Sign out">&#8594;</button>
                    </form>
                </div>
            </aside>

            <main class="dashboard-main">
                <header class="dashboard-topbar">
                    <div>
                        <p class="auth-kicker">Wonder Godoro Point</p>
                        <h1>Good morning, {{ auth()->user()->name }}.</h1>
                    </div>
                    <span class="dashboard-date">{{ now()->format('l, F j, Y') }}</span>
                </header>

                <section class="dashboard-welcome" aria-labelledby="dashboard-heading">
                    <div>
                        <p class="dashboard-section-kicker">Your workspace</p>
                        <h2 id="dashboard-heading">Everything starts with a thoughtful conversation.</h2>
                        <p>Stay close to every customer interaction from one calm, focused workspace.</p>
                    </div>
                    <a class="dashboard-primary-action" href="{{ route('admin.inbox') }}">
                        Open Customer Inbox <span aria-hidden="true">&rarr;</span>
                    </a>
                </section>

                <section class="dashboard-module-section" aria-labelledby="module-heading">
                    <div class="dashboard-section-heading">
                        <div>
                            <p class="dashboard-section-kicker">Business modules</p>
                            <h2 id="module-heading">Your operations, in one place</h2>
                        </div>
                        <span class="dashboard-module-count">01 active</span>
                    </div>

                    <div class="dashboard-module-grid">
                        <a class="dashboard-module-card dashboard-module-card--active" href="{{ route('admin.inbox') }}">
                            <span class="module-card-icon" aria-hidden="true">&#9993;</span>
                            <span class="module-card-content">
                                <span class="module-card-label">Active module</span>
                                <strong>Customer Inbox</strong>
                                <span>Manage customer conversations and replies.</span>
                            </span>
                            <span class="module-card-arrow" aria-hidden="true">&rarr;</span>
                        </a>

                        <div class="dashboard-module-card dashboard-module-card--disabled">
                            <span class="module-card-icon" aria-hidden="true">&#9675;</span>
                            <span class="module-card-content">
                                <span class="module-card-label">Coming later</span>
                                <strong>Customers, Orders &amp; Products</strong>
                                <span>Core shop operations will be added next.</span>
                            </span>
                            <span class="module-card-lock">Soon</span>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </body>
</html>