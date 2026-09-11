@extends('layouts.admin')

@section('title', 'Automations | Wonder Godoro Point')

@section('content')

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Wonder Godoro Point</p>

        <h1>
            Automations
        </h1>
    </div>

    <span class="dashboard-date">
        {{ now()->format('l, F j, Y') }}
    </span>
</header>

<section
    class="dashboard-welcome"
    aria-labelledby="automation-heading"
>
    <div>

        <p class="dashboard-section-kicker">
            Customer automation
        </p>

        <h2 id="automation-heading">
            Let your workspace respond while you focus on the customer.
        </h2>

        <p>
            Create simple rules that automatically respond to customer
            messages and keep your conversations moving.
        </p>

    </div>

    <a
        class="dashboard-primary-action"
        href="{{ route('admin.automations.create') }}"
    >
        Create Automation
        <span aria-hidden="true">&rarr;</span>
    </a>

</section>

<section
    class="dashboard-module-section"
    aria-labelledby="automation-list-heading"
>

    <div class="dashboard-section-heading">

        <div>

            <p class="dashboard-section-kicker">
                Automation rules
            </p>

            <h2 id="automation-list-heading">
                Your automations
            </h2>

        </div>

        <span class="dashboard-module-count">
            {{ $automations->count() }}
            {{ $automations->count() === 1 ? 'automation' : 'automations' }}
        </span>

    </div>

    @if (session('success'))
        <div
            class="automation-alert automation-alert--success"
            role="status"
        >
            {{ session('success') }}
        </div>
    @endif

    @if ($automations->isEmpty())

        <div class="automation-empty-state">

            <div
                class="automation-empty-icon"
                aria-hidden="true"
            >
                &#9881;
            </div>

            <div class="automation-empty-content">

                <p class="automation-empty-kicker">
                    No automations yet
                </p>

                <h3>
                    Your first automation starts here.
                </h3>

                <p>
                    Create a rule that can automatically respond to
                    customers when a specific event happens.
                </p>

                <a
                    class="dashboard-primary-action"
                    href="{{ route('admin.automations.create') }}"
                >
                    Create Your First Automation
                    <span aria-hidden="true">&rarr;</span>
                </a>

            </div>

        </div>

    @else

        <div class="automation-list">

            @foreach ($automations as $automation)

                <article class="automation-card">

                    <div class="automation-card-main">

                        <div class="automation-card-heading">

                            <div>

                                <p class="automation-card-kicker">
                                    Automation rule
                                </p>

                                <h3>
                                    {{ $automation->name }}
                                </h3>

                            </div>

                            <span
                                class="automation-status {{ $automation->is_active ? 'automation-status--active' : 'automation-status--inactive' }}"
                            >
                                <span
                                    class="automation-status-dot"
                                    aria-hidden="true"
                                ></span>

                                {{ $automation->is_active ? 'Active' : 'Inactive' }}
                            </span>

                        </div>

                        @if ($automation->description)
                            <p class="automation-card-description">
                                {{ $automation->description }}
                            </p>
                        @endif

                        <div class="automation-card-meta">

                            <div class="automation-meta-item">

                                <span class="automation-meta-label">
                                    Trigger
                                </span>

                                <strong>
                                    {{ \Illuminate\Support\Str::headline($automation->trigger) }}
                                </strong>

                            </div>

                            <div class="automation-meta-item">

                                <span class="automation-meta-label">
                                    Conditions
                                </span>

                                <strong>
                                    {{ is_array($automation->conditions) ? count($automation->conditions) : 0 }}
                                </strong>

                            </div>

                            <div class="automation-meta-item">

                                <span class="automation-meta-label">
                                    Actions
                                </span>

                                <strong>
                                    {{ is_array($automation->actions) ? count($automation->actions) : 0 }}
                                </strong>

                            </div>

                        </div>

                    </div>

                    <div class="automation-card-actions">

                        <a
                            class="automation-action automation-action--edit"
                            href="{{ route('admin.automations.edit', $automation) }}"
                        >
                            Edit
                        </a>

                        <form
                            method="POST"
                            action="{{ route('admin.automations.toggle', $automation) }}"
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="automation-action automation-action--toggle"
                            >
                                {{ $automation->is_active ? 'Disable' : 'Enable' }}
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('admin.automations.destroy', $automation) }}"
                            onsubmit="return confirm('Are you sure you want to delete this automation?');"
                        >
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="automation-action automation-action--delete"
                            >
                                Delete
                            </button>
                        </form>

                    </div>

                </article>

            @endforeach

        </div>

    @endif

</section>

<style>
    .automation-alert {
        margin-bottom: 24px;
        padding: 14px 18px;
        border: 1px solid rgba(185, 145, 65, 0.35);
        border-radius: 14px;
        font-size: 14px;
        line-height: 1.5;
    }

    .automation-alert--success {
        background: rgba(185, 145, 65, 0.08);
    }

    .automation-empty-state {
        display: flex;
        align-items: center;
        gap: 28px;
        padding: 42px;
        border: 1px solid rgba(185, 145, 65, 0.2);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.025);
    }

    .automation-empty-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 72px;
        width: 72px;
        height: 72px;
        border: 1px solid rgba(185, 145, 65, 0.35);
        border-radius: 20px;
        font-size: 30px;
    }

    .automation-empty-content {
        max-width: 680px;
    }

    .automation-empty-kicker,
    .automation-card-kicker {
        margin: 0 0 6px;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        opacity: 0.65;
    }

    .automation-empty-content h3 {
        margin: 0 0 10px;
        font-size: 24px;
    }

    .automation-empty-content p:not(.automation-empty-kicker) {
        margin: 0 0 22px;
        line-height: 1.7;
        opacity: 0.72;
    }

    .automation-list {
        display: grid;
        gap: 16px;
    }

    .automation-card {
        display: flex;
        align-items: stretch;
        justify-content: space-between;
        gap: 24px;
        padding: 24px;
        border: 1px solid rgba(185, 145, 65, 0.2);
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.025);
        transition:
            transform 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease;
    }

    .automation-card:hover {
        transform: translateY(-2px);
        border-color: rgba(185, 145, 65, 0.42);
        background: rgba(255, 255, 255, 0.04);
    }

    .automation-card-main {
        min-width: 0;
        flex: 1;
    }

    .automation-card-heading {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 20px;
    }

    .automation-card-heading h3 {
        margin: 0;
        font-size: 20px;
        line-height: 1.3;
    }

    .automation-card-description {
        margin: 12px 0 20px;
        max-width: 760px;
        line-height: 1.6;
        opacity: 0.7;
    }

    .automation-status {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        flex: 0 0 auto;
        padding: 7px 11px;
        border-radius: 999px;
        font-size: 12px;
        font-weight: 700;
    }

    .automation-status--active {
        background: rgba(76, 175, 80, 0.1);
    }

    .automation-status--inactive {
        background: rgba(255, 255, 255, 0.07);
        opacity: 0.7;
    }

    .automation-status-dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: currentColor;
    }

    .automation-card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 26px;
    }

    .automation-meta-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 100px;
    }

    .automation-meta-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        opacity: 0.5;
    }

    .automation-meta-item strong {
        font-size: 14px;
        font-weight: 600;
    }

    .automation-card-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 8px;
        flex-wrap: wrap;
        align-content: center;
    }

    .automation-card-actions form {
        margin: 0;
    }

    .automation-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 38px;
        padding: 8px 13px;
        border: 1px solid rgba(185, 145, 65, 0.22);
        border-radius: 10px;
        background: transparent;
        font: inherit;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        transition:
            border-color 0.2s ease,
            background 0.2s ease,
            transform 0.2s ease;
    }

    .automation-action:hover {
        transform: translateY(-1px);
        border-color: rgba(185, 145, 65, 0.5);
        background: rgba(185, 145, 65, 0.08);
    }

    .automation-action--delete {
        border-color: rgba(190, 70, 70, 0.2);
    }

    .automation-action--delete:hover {
        border-color: rgba(190, 70, 70, 0.45);
        background: rgba(190, 70, 70, 0.08);
    }

    @media (max-width: 820px) {

        .automation-empty-state {
            align-items: flex-start;
            flex-direction: column;
            padding: 28px;
        }

        .automation-card {
            flex-direction: column;
        }

        .automation-card-actions {
            justify-content: flex-start;
        }

    }

    @media (max-width: 560px) {

        .automation-card {
            padding: 18px;
            border-radius: 16px;
        }

        .automation-card-heading {
            align-items: flex-start;
            flex-direction: column;
            gap: 12px;
        }

        .automation-card-meta {
            gap: 18px;
        }

        .automation-card-actions {
            width: 100%;
        }

        .automation-action {
            flex: 1;
        }

    }
</style>

@endsection