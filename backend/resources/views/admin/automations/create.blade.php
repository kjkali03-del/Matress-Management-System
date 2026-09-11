@extends('layouts.admin')

@section('title', 'Create Automation | Wonder Godoro Point')

@section('content')

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Wonder Godoro Point</p>

        <h1>
            Create Automation
        </h1>
    </div>

    <span class="dashboard-date">
        {{ now()->format('l, F j, Y') }}
    </span>
</header>

<section
    class="automation-builder"
    aria-labelledby="automation-builder-heading"
>

    <div class="automation-builder-intro">

        <div>
            <p class="dashboard-section-kicker">
                Automation builder
            </p>

            <h2 id="automation-builder-heading">
                Build a rule that works for you.
            </h2>

            <p>
                Tell the system what should start the automation,
                what it should look for, and how it should respond.
            </p>
        </div>

        <a
            class="automation-back-link"
            href="{{ route('admin.automations.index') }}"
        >
            &larr; Back to Automations
        </a>

    </div>

    @if ($errors->any())
        <div
            class="automation-form-alert"
            role="alert"
        >
            <strong>
                Please check the following:
            </strong>

            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('admin.automations.store') }}"
        class="automation-builder-form"
    >
        @csrf

        {{-- BASIC INFORMATION --}}

        <section class="automation-builder-card">

            <div class="automation-builder-card-heading">

                <div class="automation-step-number">
                    01
                </div>

                <div>
                    <p class="automation-builder-kicker">
                        Basic information
                    </p>

                    <h3>
                        Give your automation a name
                    </h3>

                    <p>
                        Use a name that makes the purpose of this rule
                        immediately clear to your team.
                    </p>
                </div>

            </div>

            <div class="automation-field-grid">

                <div class="automation-field automation-field--full">

                    <label for="name">
                        Automation name
                    </label>

                    <input
                        id="name"
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="Example: Auto reply for price enquiries"
                        maxlength="255"
                        required
                    >

                </div>

                <div class="automation-field automation-field--full">

                    <label for="description">
                        Description
                        <span>Optional</span>
                    </label>

                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        placeholder="Example: Automatically responds when a customer asks about mattress prices."
                    >{{ old('description') }}</textarea>

                </div>

            </div>

        </section>

        {{-- TRIGGER --}}

        <section class="automation-builder-card">

            <div class="automation-builder-card-heading">

                <div class="automation-step-number">
                    02
                </div>

                <div>
                    <p class="automation-builder-kicker">
                        WHEN
                    </p>

                    <h3>
                        What should start this automation?
                    </h3>

                    <p>
                        Choose the customer event that activates this rule.
                    </p>
                </div>

            </div>

            <div class="automation-flow-box">

                <div class="automation-flow-icon">
                    WHEN
                </div>

                <div class="automation-field automation-field--flow">

                    <label for="trigger">
                        Customer event
                    </label>

                    <select
                        id="trigger"
                        name="trigger"
                        required
                    >
                        <option
                            value="message_received"
                            {{ old('trigger', 'message_received') === 'message_received' ? 'selected' : '' }}
                        >
                            Customer sends a message
                        </option>
                    </select>

                    <small>
                        The automation will be evaluated when a new
                        customer message is received.
                    </small>

                </div>

            </div>

        </section>

        {{-- CONDITION --}}

        <section class="automation-builder-card">

            <div class="automation-builder-card-heading">

                <div class="automation-step-number">
                    03
                </div>

                <div>
                    <p class="automation-builder-kicker">
                        IF
                    </p>

                    <h3>
                        When should the rule respond?
                    </h3>

                    <p>
                        Start with a keyword condition. We can add more
                        condition types later.
                    </p>
                </div>

            </div>

            <div class="automation-rule-box">

                <div class="automation-rule-label">
                    IF
                </div>

                <div class="automation-field">

                    <label for="condition_type">
                        Condition
                    </label>

                    <select
                        id="condition_type"
                        name="conditions[0][type]"
                    >
                        <option value="keyword">
                            Message contains keyword
                        </option>
                    </select>

                </div>

                <div class="automation-field">

                    <label for="condition_value">
                        Keyword
                    </label>

                    <input
                        id="condition_value"
                        type="text"
                        name="conditions[0][value]"
                        value="{{ old('conditions.0.value') }}"
                        placeholder="Example: bei"
                    >

                    <small>
                        Enter one keyword or phrase for this first rule.
                    </small>

                </div>

            </div>

        </section>

        {{-- ACTION --}}

        <section class="automation-builder-card">

            <div class="automation-builder-card-heading">

                <div class="automation-step-number">
                    04
                </div>

                <div>
                    <p class="automation-builder-kicker">
                        THEN
                    </p>

                    <h3>
                        What should Wonder Godoro Point do?
                    </h3>

                    <p>
                        Define the automatic response sent to the customer.
                    </p>
                </div>

            </div>

            <div class="automation-rule-box automation-rule-box--action">

                <div class="automation-rule-label">
                    THEN
                </div>

                <div class="automation-field">

                    <label for="action_type">
                        Action
                    </label>

                    <select
                        id="action_type"
                        name="actions[0][type]"
                    >
                        <option value="send_text">
                            Send text message
                        </option>
                    </select>

                </div>

                <div class="automation-field automation-field--full">

                    <label for="action_message">
                        Automatic reply
                    </label>

                    <textarea
                        id="action_message"
                        name="actions[0][message]"
                        rows="6"
                        placeholder="Example: Karibu Wonder Godoro Point. Bei za magodoro zinaanzia Tsh 165,000. Tunatoa delivery bure ndani ya Dar es Salaam."
                    >{{ old('actions.0.message') }}</textarea>

                    <small>
                        This message will be sent automatically when the
                        trigger and condition are matched.
                    </small>

                </div>

            </div>

        </section>

        {{-- STATUS --}}

        <section class="automation-builder-card">

            <div class="automation-builder-card-heading">

                <div class="automation-step-number">
                    05
                </div>

                <div>
                    <p class="automation-builder-kicker">
                        STATUS
                    </p>

                    <h3>
                        Activate this automation
                    </h3>

                    <p>
                        You can disable the rule later without deleting it.
                    </p>
                </div>

            </div>

            <label class="automation-toggle">

                <input
                    type="checkbox"
                    name="is_active"
                    value="1"
                    {{ old('is_active', true) ? 'checked' : '' }}
                >

                <span class="automation-toggle-track">
                    <span class="automation-toggle-thumb"></span>
                </span>

                <span class="automation-toggle-content">

                    <strong>
                        Enable automation
                    </strong>

                    <small>
                        Start evaluating this rule as soon as it is saved.
                    </small>

                </span>

            </label>

        </section>

        {{-- ACTION BAR --}}

        <div class="automation-builder-actions">

            <a
                class="automation-secondary-button"
                href="{{ route('admin.automations.index') }}"
            >
                Cancel
            </a>

            <button
                type="submit"
                class="automation-primary-button"
            >
                Create Automation
                <span aria-hidden="true">&rarr;</span>
            </button>

        </div>

    </form>

</section>

<style>
    .automation-builder {
        width: 100%;
        max-width: 1100px;
        margin: 0 auto;
    }

    .automation-builder-intro {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 30px;
        margin-bottom: 30px;
    }

    .automation-builder-intro h2 {
        margin: 0 0 10px;
        max-width: 760px;
        font-size: 30px;
        line-height: 1.25;
    }

    .automation-builder-intro p:not(.dashboard-section-kicker) {
        max-width: 720px;
        margin: 0;
        line-height: 1.7;
        opacity: 0.7;
    }

    .automation-back-link {
        flex: 0 0 auto;
        text-decoration: none;
        font-size: 13px;
        font-weight: 700;
        opacity: 0.75;
        transition: opacity 0.2s ease;
    }

    .automation-back-link:hover {
        opacity: 1;
    }

    .automation-builder-form {
        display: grid;
        gap: 18px;
    }

    .automation-builder-card {
        padding: 30px;
        border: 1px solid rgba(185, 145, 65, 0.2);
        border-radius: 22px;
        background: rgba(255, 255, 255, 0.025);
    }

    .automation-builder-card-heading {
        display: flex;
        align-items: flex-start;
        gap: 18px;
        margin-bottom: 28px;
    }

    .automation-step-number {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 48px;
        width: 48px;
        height: 48px;
        border: 1px solid rgba(185, 145, 65, 0.35);
        border-radius: 14px;
        font-size: 12px;
        font-weight: 800;
        letter-spacing: 0.08em;
    }

    .automation-builder-kicker {
        margin: 0 0 5px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        opacity: 0.55;
    }

    .automation-builder-card-heading h3 {
        margin: 0 0 7px;
        font-size: 20px;
        line-height: 1.35;
    }

    .automation-builder-card-heading p:last-child {
        max-width: 700px;
        margin: 0;
        font-size: 13px;
        line-height: 1.6;
        opacity: 0.65;
    }

    .automation-field-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 20px;
    }

    .automation-field {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .automation-field--full {
        grid-column: 1 / -1;
    }

    .automation-field label {
        font-size: 12px;
        font-weight: 700;
    }

    .automation-field label span {
        margin-left: 5px;
        font-size: 10px;
        font-weight: 500;
        opacity: 0.5;
    }

    .automation-field input,
    .automation-field select,
    .automation-field textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid rgba(185, 145, 65, 0.2);
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.12);
        padding: 13px 14px;
        color: inherit;
        font: inherit;
        font-size: 13px;
        outline: none;
        transition:
            border-color 0.2s ease,
            background 0.2s ease;
    }

    .automation-field input:focus,
    .automation-field select:focus,
    .automation-field textarea:focus {
        border-color: rgba(185, 145, 65, 0.65);
        background: rgba(0, 0, 0, 0.18);
    }

    .automation-field textarea {
        resize: vertical;
        min-height: 100px;
        line-height: 1.6;
    }

    .automation-field select {
        cursor: pointer;
    }

    .automation-field small {
        font-size: 11px;
        line-height: 1.5;
        opacity: 0.5;
    }

    .automation-flow-box,
    .automation-rule-box {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        gap: 20px;
        align-items: start;
        padding: 22px;
        border: 1px solid rgba(185, 145, 65, 0.14);
        border-radius: 18px;
        background: rgba(0, 0, 0, 0.08);
    }

    .automation-rule-box {
        grid-template-columns: auto repeat(2, minmax(0, 1fr));
    }

    .automation-rule-box--action {
        grid-template-columns: auto minmax(0, 0.7fr) minmax(0, 1.3fr);
    }

    .automation-rule-label,
    .automation-flow-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        min-width: 58px;
        min-height: 38px;
        padding: 0 12px;
        border: 1px solid rgba(185, 145, 65, 0.3);
        border-radius: 10px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.12em;
    }

    .automation-flow-icon {
        align-self: center;
    }

    .automation-form-alert {
        margin-bottom: 20px;
        padding: 16px 18px;
        border: 1px solid rgba(190, 70, 70, 0.35);
        border-radius: 14px;
        background: rgba(190, 70, 70, 0.07);
        font-size: 13px;
        line-height: 1.6;
    }

    .automation-form-alert ul {
        margin: 8px 0 0;
        padding-left: 20px;
    }

    .automation-toggle {
        display: flex;
        align-items: center;
        gap: 14px;
        cursor: pointer;
    }

    .automation-toggle input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .automation-toggle-track {
        position: relative;
        display: block;
        flex: 0 0 48px;
        width: 48px;
        height: 28px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        transition: background 0.2s ease;
    }

    .automation-toggle-thumb {
        position: absolute;
        top: 4px;
        left: 4px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #fff;
        transition: transform 0.2s ease;
    }

    .automation-toggle input:checked + .automation-toggle-track {
        background: rgba(185, 145, 65, 0.75);
    }

    .automation-toggle input:checked + .automation-toggle-track
        .automation-toggle-thumb {
        transform: translateX(20px);
    }

    .automation-toggle-content {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .automation-toggle-content strong {
        font-size: 13px;
    }

    .automation-toggle-content small {
        font-size: 11px;
        opacity: 0.55;
    }

    .automation-builder-actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        padding: 8px 0 30px;
    }

    .automation-primary-button,
    .automation-secondary-button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        min-height: 46px;
        padding: 0 20px;
        border-radius: 12px;
        font: inherit;
        font-size: 13px;
        font-weight: 800;
        text-decoration: none;
        cursor: pointer;
        transition:
            transform 0.2s ease,
            border-color 0.2s ease,
            background 0.2s ease;
    }

    .automation-primary-button {
        border: 1px solid rgba(185, 145, 65, 0.65);
        background: rgba(185, 145, 65, 0.16);
        color: inherit;
    }

    .automation-secondary-button {
        border: 1px solid rgba(185, 145, 65, 0.2);
        background: transparent;
        color: inherit;
    }

    .automation-primary-button:hover,
    .automation-secondary-button:hover {
        transform: translateY(-1px);
        border-color: rgba(185, 145, 65, 0.55);
        background: rgba(185, 145, 65, 0.08);
    }

    @media (max-width: 820px) {

        .automation-builder-intro {
            align-items: flex-start;
            flex-direction: column;
        }

        .automation-field-grid {
            grid-template-columns: 1fr;
        }

        .automation-rule-box,
        .automation-rule-box--action {
            grid-template-columns: 1fr;
        }

        .automation-rule-label {
            width: fit-content;
        }

    }

    @media (max-width: 560px) {

        .automation-builder-intro h2 {
            font-size: 25px;
        }

        .automation-builder-card {
            padding: 20px;
            border-radius: 18px;
        }

        .automation-builder-card-heading {
            gap: 13px;
        }

        .automation-step-number {
            flex-basis: 40px;
            width: 40px;
            height: 40px;
        }

        .automation-flow-box,
        .automation-rule-box {
            padding: 16px;
        }

        .automation-builder-actions {
            flex-direction: column-reverse;
            align-items: stretch;
        }

        .automation-primary-button,
        .automation-secondary-button {
            width: 100%;
        }

    }
</style>

@endsection