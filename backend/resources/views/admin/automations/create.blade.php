@extends('layouts.admin')

@section('title', 'Create Automation | Wonder Godoro Point')

@section('content')

<header class="dashboard-topbar">
    <div>
        <p class="auth-kicker">Wonder Godoro Point</p>

        <h1>Create Automation</h1>
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
                Choose what starts the automation, define when it should
                respond, and decide what Wonder Godoro Point should do.
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
        id="automation-form"
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
                        Use a clear name so your team immediately knows
                        what this automation does.
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
                        placeholder="Example: Welcome New Customers"
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
                        placeholder="Example: Welcomes a customer automatically when they contact us for the first time."
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
                        Select the event that should activate this rule.
                    </p>

                </div>

            </div>

            <div class="automation-trigger-grid">

                <label class="automation-trigger-option">

                    <input
                        type="radio"
                        name="trigger"
                        value="new_customer"
                        {{ old('trigger') === 'new_customer' ? 'checked' : '' }}
                    >

                    <span class="automation-trigger-card">

                        <span class="automation-trigger-icon">
                            &#128075;
                        </span>

                        <span class="automation-trigger-content">

                            <strong>
                                New Customer
                            </strong>

                            <small>
                                First message from a new customer.
                            </small>

                        </span>

                        <span class="automation-trigger-check">
                            &#10003;
                        </span>

                    </span>

                </label>


                <label class="automation-trigger-option">

                    <input
                        type="radio"
                        name="trigger"
                        value="message_received"
                        {{ old('trigger', 'message_received') === 'message_received' ? 'checked' : '' }}
                    >

                    <span class="automation-trigger-card">

                        <span class="automation-trigger-icon">
                            &#128172;
                        </span>

                        <span class="automation-trigger-content">

                            <strong>
                                Message Received
                            </strong>

                            <small>
                                Run when a customer sends a message.
                            </small>

                        </span>

                        <span class="automation-trigger-check">
                            &#10003;
                        </span>

                    </span>

                </label>


                <label class="automation-trigger-option">

                    <input
                        type="radio"
                        name="trigger"
                        value="keyword"
                        {{ old('trigger') === 'keyword' ? 'checked' : '' }}
                    >

                    <span class="automation-trigger-card">

                        <span class="automation-trigger-icon">
                            &#128273;
                        </span>

                        <span class="automation-trigger-content">

                            <strong>
                                Keyword Match
                            </strong>

                            <small>
                                Run when a message contains a keyword.
                            </small>

                        </span>

                        <span class="automation-trigger-check">
                            &#10003;
                        </span>

                    </span>

                </label>


                <label class="automation-trigger-option">

                    <input
                        type="radio"
                        name="trigger"
                        value="no_reply"
                        {{ old('trigger') === 'no_reply' ? 'checked' : '' }}
                    >

                    <span class="automation-trigger-card">

                        <span class="automation-trigger-icon">
                            &#9201;
                        </span>

                        <span class="automation-trigger-content">

                            <strong>
                                No Reply / Follow-up
                            </strong>

                            <small>
                                Follow up when a customer does not reply.
                            </small>

                        </span>

                        <span class="automation-trigger-check">
                            &#10003;
                        </span>

                    </span>

                </label>

            </div>

        </section>


        {{-- CONDITIONS --}}

        <section
            class="automation-builder-card"
            id="conditions-section"
        >

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

                    <p id="condition-description">
                        Add conditions when this automation needs to
                        look for something specific.
                    </p>

                </div>

            </div>


            <div
                class="automation-rule-box"
                id="keyword-condition"
            >

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
                        Keyword / phrase
                    </label>

                    <input
                        id="condition_value"
                        type="text"
                        name="conditions[0][value]"
                        value="{{ old('conditions.0.value') }}"
                        placeholder="Example: bei"
                    >

                    <small>
                        Example: bei, godoro, delivery, size
                    </small>

                </div>

            </div>


            <div
                class="automation-special-info"
                id="welcome-condition"
            >

                <span class="automation-special-icon">
                    &#128075;
                </span>

                <div>

                    <strong>
                        Welcome automation
                    </strong>

                    <p>
                        This automation is designed for the customer's
                        first message. No keyword is required.
                    </p>

                </div>

            </div>


            <div
                class="automation-followup-box"
                id="followup-condition"
            >

                <div class="automation-followup-heading">

                    <span class="automation-special-icon">
                        &#9201;
                    </span>

                    <div>

                        <strong>
                            Follow-up timing
                        </strong>

                        <p>
                            Choose how long the system should wait before
                            sending the follow-up.
                        </p>

                    </div>

                </div>

                <div class="automation-field-grid">

                    <div class="automation-field">

                        <label for="delay_value">
                            Wait for
                        </label>

                        <input
                            id="delay_value"
                            type="number"
                            name="conditions[0][delay_value]"
                            value="{{ old('conditions.0.delay_value', 24) }}"
                            min="1"
                            max="720"
                        >

                    </div>

                    <div class="automation-field">

                        <label for="delay_unit">
                            Unit
                        </label>

                        <select
                            id="delay_unit"
                            name="conditions[0][delay_unit]"
                        >

                            <option
                                value="minutes"
                                {{ old('conditions.0.delay_unit') === 'minutes' ? 'selected' : '' }}
                            >
                                Minutes
                            </option>

                            <option
                                value="hours"
                                {{ old('conditions.0.delay_unit', 'hours') === 'hours' ? 'selected' : '' }}
                            >
                                Hours
                            </option>

                            <option
                                value="days"
                                {{ old('conditions.0.delay_unit') === 'days' ? 'selected' : '' }}
                            >
                                Days
                            </option>

                        </select>

                    </div>

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


            <div class="automation-action-box">

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

            </div>


            <div class="automation-field automation-field--full">

                <label for="action_message">
                    Automatic message
                </label>

                <textarea
                    id="action_message"
                    name="actions[0][message]"
                    rows="7"
                    placeholder="Example: Habari 👋 Karibu Wonder Godoro Point! Tunafurahi kukuhudumia. Unahitaji godoro la size gani?"
                    required
                >{{ old('actions.0.message') }}</textarea>

                <small>
                    This message will be sent automatically when the
                    automation is triggered.
                </small>

            </div>


            <div class="automation-message-preview">

                <div class="automation-message-preview-header">

                    <span>
                        Message preview
                    </span>

                    <span>
                        WhatsApp
                    </span>

                </div>

                <div
                    class="automation-message-bubble"
                    id="message-preview"
                >
                    Your automatic message will appear here.
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


    /* TRIGGER OPTIONS */

    .automation-trigger-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 15px;
    }

    .automation-trigger-option {
        position: relative;
        display: block;
        cursor: pointer;
    }

    .automation-trigger-option input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .automation-trigger-card {
        position: relative;
        display: flex;
        align-items: center;
        gap: 15px;
        min-height: 86px;
        padding: 17px;
        border: 1px solid rgba(185, 145, 65, 0.16);
        border-radius: 16px;
        background: rgba(0, 0, 0, 0.08);
        transition:
            border-color 0.2s ease,
            background 0.2s ease,
            transform 0.2s ease;
    }

    .automation-trigger-card:hover {
        transform: translateY(-1px);
        border-color: rgba(185, 145, 65, 0.4);
    }

    .automation-trigger-option input:checked
        + .automation-trigger-card {
        border-color: rgba(185, 145, 65, 0.7);
        background: rgba(185, 145, 65, 0.09);
    }

    .automation-trigger-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 42px;
        width: 42px;
        height: 42px;
        border: 1px solid rgba(185, 145, 65, 0.25);
        border-radius: 12px;
        font-size: 19px;
    }

    .automation-trigger-content {
        display: flex;
        flex-direction: column;
        gap: 5px;
        min-width: 0;
    }

    .automation-trigger-content strong {
        font-size: 13px;
    }

    .automation-trigger-content small {
        font-size: 11px;
        line-height: 1.5;
        opacity: 0.55;
    }

    .automation-trigger-check {
        margin-left: auto;
        opacity: 0;
        font-size: 14px;
        font-weight: 800;
        transition: opacity 0.2s ease;
    }

    .automation-trigger-option input:checked
        + .automation-trigger-card
        .automation-trigger-check {
        opacity: 1;
    }


    /* CONDITIONS */

    .automation-rule-box {
        display: grid;
        grid-template-columns: auto repeat(2, minmax(0, 1fr));
        gap: 20px;
        align-items: start;
        padding: 22px;
        border: 1px solid rgba(185, 145, 65, 0.14);
        border-radius: 18px;
        background: rgba(0, 0, 0, 0.08);
    }

    .automation-rule-label {
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

    .automation-special-info,
    .automation-followup-box {
        display: none;
    }

    .automation-special-info {
        align-items: flex-start;
        gap: 15px;
        padding: 20px;
        border: 1px solid rgba(185, 145, 65, 0.18);
        border-radius: 16px;
        background: rgba(185, 145, 65, 0.05);
    }

    .automation-special-icon {
        display: flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 40px;
        width: 40px;
        height: 40px;
        border-radius: 11px;
        border: 1px solid rgba(185, 145, 65, 0.25);
        font-size: 18px;
    }

    .automation-special-info strong,
    .automation-followup-heading strong {
        display: block;
        margin-bottom: 5px;
        font-size: 13px;
    }

    .automation-special-info p,
    .automation-followup-heading p {
        margin: 0;
        font-size: 12px;
        line-height: 1.6;
        opacity: 0.6;
    }

    .automation-followup-box {
        padding: 20px;
        border: 1px solid rgba(185, 145, 65, 0.18);
        border-radius: 16px;
        background: rgba(185, 145, 65, 0.04);
    }

    .automation-followup-heading {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 20px;
    }


    /* ACTION */

    .automation-action-box {
        display: grid;
        grid-template-columns: auto minmax(0, 0.7fr);
        gap: 20px;
        align-items: start;
        margin-bottom: 20px;
        padding: 22px;
        border: 1px solid rgba(185, 145, 65, 0.14);
        border-radius: 18px;
        background: rgba(0, 0, 0, 0.08);
    }

    .automation-message-preview {
        margin-top: 25px;
        padding: 18px;
        border: 1px solid rgba(185, 145, 65, 0.12);
        border-radius: 16px;
        background: rgba(0, 0, 0, 0.1);
    }

    .automation-message-preview-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 14px;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        opacity: 0.5;
    }

    .automation-message-bubble {
        max-width: 75%;
        padding: 13px 15px;
        border-radius: 14px 14px 14px 3px;
        background: rgba(185, 145, 65, 0.1);
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-wrap;
    }


    /* STATUS */

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

    .automation-toggle input:checked
        + .automation-toggle-track {
        background: rgba(185, 145, 65, 0.75);
    }

    .automation-toggle input:checked
        + .automation-toggle-track
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


    /* FORM ALERT */

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


    /* BUTTONS */

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


    /* RESPONSIVE */

    @media (max-width: 820px) {

        .automation-builder-intro {
            align-items: flex-start;
            flex-direction: column;
        }

        .automation-field-grid,
        .automation-trigger-grid {
            grid-template-columns: 1fr;
        }

        .automation-rule-box,
        .automation-action-box {
            grid-template-columns: 1fr;
        }

        .automation-rule-label {
            width: fit-content;
        }

        .automation-message-bubble {
            max-width: 90%;
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

        .automation-trigger-card {
            min-height: 76px;
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


<script>

    document.addEventListener('DOMContentLoaded', function () {

        const triggerInputs = document.querySelectorAll(
            'input[name="trigger"]'
        );

        const keywordCondition =
            document.getElementById('keyword-condition');

        const welcomeCondition =
            document.getElementById('welcome-condition');

        const followupCondition =
            document.getElementById('followup-condition');

        const conditionDescription =
            document.getElementById('condition-description');

        const conditionValue =
            document.getElementById('condition_value');

        const actionMessage =
            document.getElementById('action_message');

        const messagePreview =
            document.getElementById('message-preview');


        function updateTriggerInterface() {

            const selectedTrigger =
                document.querySelector(
                    'input[name="trigger"]:checked'
                )?.value;


            keywordCondition.style.display = 'none';
            welcomeCondition.style.display = 'none';
            followupCondition.style.display = 'none';


            if (selectedTrigger === 'new_customer') {

                welcomeCondition.style.display = 'flex';

                conditionDescription.textContent =
                    'This automation runs when a customer contacts Wonder Godoro Point for the first time.';

                conditionValue.removeAttribute('required');

            } else if (selectedTrigger === 'keyword') {

                keywordCondition.style.display = 'grid';

                conditionDescription.textContent =
                    'The automation will respond when the customer message contains the keyword or phrase you specify.';

                conditionValue.setAttribute('required', 'required');

            } else if (selectedTrigger === 'no_reply') {

                followupCondition.style.display = 'block';

                conditionDescription.textContent =
                    'The system will wait for the selected period before sending a follow-up when the customer has not replied.';

                conditionValue.removeAttribute('required');

            } else {

                keywordCondition.style.display = 'grid';

                conditionDescription.textContent =
                    'You can optionally use a keyword or phrase to control when this automation responds.';

                conditionValue.removeAttribute('required');
            }

        }


        function updateMessagePreview() {

            const message =
                actionMessage.value.trim();

            messagePreview.textContent =
                message !== ''
                    ? message
                    : 'Your automatic message will appear here.';
        }


        triggerInputs.forEach(function (input) {

            input.addEventListener(
                'change',
                updateTriggerInterface
            );

        });


        actionMessage.addEventListener(
            'input',
            updateMessagePreview
        );


        updateTriggerInterface();
        updateMessagePreview();

    });

</script>

@endsection