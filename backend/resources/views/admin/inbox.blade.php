<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <title>Inbox | Wonder Godoro Point</title>

        <link
            rel="stylesheet"
            href="{{ asset('css/auth.css') }}"
        >

        <link
            rel="stylesheet"
            href="{{ asset('css/inbox.css') }}"
        >
    </head>

    <body class="inbox-page">

        <header class="inbox-header">

            <a
                class="admin-brand"
                href="{{ route('admin') }}"
            >
                <img
                    src="{{ asset('img/logo.png') }}"
                    alt="Wonder Godoro Point Mattress Shop"
                >

                <span>
                    Admin workspace
                </span>
            </a>

            <div class="inbox-header-actions">

                <span class="inbox-user">
                    {{ auth()->user()->name }}
                </span>

                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >
                    @csrf

                    <button
                        type="submit"
                        class="admin-logout"
                    >
                        Sign out
                    </button>
                </form>

            </div>

        </header>


        <main class="inbox-shell">

            {{-- Conversation list --}}
            <aside
                class="conversation-panel"
                aria-label="Conversations"
            >

                <div class="panel-heading">

                    <div>
                        <p class="auth-kicker">
                            Customer communication
                        </p>

                        <h1>
                            Inbox
                        </h1>
                    </div>

                    <span
                        class="conversation-count"
                        id="conversation-count"
                    >
                        {{ $conversations->count() }}
                    </span>

                </div>


                {{-- Conversation search --}}
                <form
                    class="conversation-search"
                    id="conversation-search-form"
                    method="GET"
                    action="{{ route('admin.inbox') }}"
                >

                    <label
                        class="sr-only"
                        for="conversation-search-input"
                    >
                        Search conversations
                    </label>

                    <input
                        id="conversation-search-input"
                        type="search"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Search customer, phone or message..."
                        autocomplete="off"
                        spellcheck="false"
                    >


                    <button
                        type="button"
                        class="conversation-search-clear"
                        id="conversation-search-clear"
                        aria-label="Clear search"
                        title="Clear search"
                        @if ($search === '') hidden @endif
                    >
                        &times;
                    </button>


                    <button
                        type="submit"
                        class="conversation-search-button"
                    >
                        Search
                    </button>

                </form>


                @if ($conversations->isEmpty())

                    <div
                        class="inbox-empty inbox-empty--list"
                        id="conversation-empty-state"
                    >

                        <span
                            class="empty-mark"
                            aria-hidden="true"
                        >
                            @if ($search !== '')
                                &#128269;
                            @else
                                &#8722;
                            @endif
                        </span>

                        <h2>
                            @if ($search !== '')
                                No conversations found
                            @else
                                No conversations yet
                            @endif
                        </h2>

                        <p>
                            @if ($search !== '')
                                Try another customer name, phone number or message.
                            @else
                                New customer messages will appear here.
                            @endif
                        </p>

                        @if ($search !== '')

                            <a
                                class="conversation-search-reset"
                                href="{{ route('admin.inbox') }}"
                            >
                                Clear search
                            </a>

                        @endif

                    </div>

                @else

                    <nav
                        class="conversation-list"
                        id="conversation-list"
                        aria-label="Conversation list"
                    >

                        @foreach ($conversations as $conversation)

                            @php
                                $isSelected =
                                    $selectedConversation?->id === $conversation->id;

                                $customerName =
                                    $conversation->customer?->name
                                    ?: $conversation->customer?->phone
                                    ?: 'Unknown customer';

                                $customerPhone =
                                    $conversation->customer?->phone
                                    ?: 'No phone number';

                                $preview =
                                    $conversation->latestMessage?->body
                                    ?: 'No messages yet';

                                $avatar =
                                    str($customerName)
                                        ->substr(0, 1)
                                        ->upper();
                            @endphp

                            <a
                                class="conversation-item {{ $isSelected ? 'is-selected' : '' }}"
                                href="{{ route('admin.inbox', ['conversation' => $conversation->id]) }}"
                                aria-current="{{ $isSelected ? 'page' : 'false' }}"
                            >

                                <span class="conversation-avatar">
                                    {{ $avatar }}
                                </span>


                                <span class="conversation-summary">

                                    <span class="conversation-topline">

                                        <strong>
                                            {{ $customerName }}
                                        </strong>

                                        @if ($conversation->last_message_at)

                                            <time
                                                datetime="{{ $conversation->last_message_at->toIso8601String() }}"
                                            >
                                                {{ $conversation->last_message_at->shortAbsoluteDiffForHumans() }}
                                            </time>

                                        @endif

                                    </span>


                                    <span class="conversation-phone">
                                        {{ $customerPhone }}
                                    </span>


                                    <span class="conversation-preview">
                                        {{ $preview }}
                                    </span>

                                </span>


                                @if ($conversation->unread_messages_count > 0)

                                    <span
                                        class="unread-badge"
                                        aria-label="{{ $conversation->unread_messages_count }} unread messages"
                                    >
                                        {{ $conversation->unread_messages_count }}
                                    </span>

                                @endif

                            </a>

                        @endforeach

                    </nav>

                @endif

            </aside>


            {{-- Selected conversation --}}
            <section
                class="conversation-panel conversation-detail"
                aria-label="Selected conversation"
            >

                @if ($selectedConversation)

                    @php
                        $selectedCustomer =
                            $selectedConversation->customer;

                        $selectedCustomerName =
                            $selectedCustomer?->name
                            ?: $selectedCustomer?->phone
                            ?: 'Unknown customer';

                        $selectedCustomerPhone =
                            $selectedCustomer?->phone
                            ?: 'No phone number';

                        $selectedAvatar =
                            str($selectedCustomerName)
                                ->substr(0, 1)
                                ->upper();
                    @endphp


                    <header class="conversation-detail-header">

                        <div class="customer-heading">

                            <span class="conversation-avatar conversation-avatar--large">
                                {{ $selectedAvatar }}
                            </span>

                            <div>

                                <h2>
                                    {{ $selectedCustomerName }}
                                </h2>

                                <p>

                                    {{ $selectedCustomerPhone }}

                                    <span class="channel-label">
                                        {{ ucfirst($selectedConversation->channel) }}
                                    </span>

                                </p>

                            </div>

                        </div>


                        <span
                            class="status-pill status-pill--{{ $selectedConversation->status }}"
                        >
                            {{ ucfirst($selectedConversation->status) }}
                        </span>

                    </header>


                    <div
                        class="message-stream"
                        id="message-stream"
                    >

                        @if ($selectedConversation->messages->isEmpty())

                            <div class="inbox-empty">

                                <span
                                    class="empty-mark"
                                    aria-hidden="true"
                                >
                                    &#8595;
                                </span>

                                <h2>
                                    Start the conversation
                                </h2>

                                <p>
                                    There are no messages in this thread yet.
                                </p>

                            </div>

                        @else

                            <div class="message-day-label">
                                Conversation history
                            </div>


                            @foreach ($selectedConversation->messages as $message)

                                <article
                                    class="message-row message-row--{{ $message->direction }}"
                                    data-message-id="{{ $message->id }}"
                                >

                                    <div class="message-bubble">

                                        <p>
                                            {{ $message->body }}
                                        </p>

                                        <time
                                            datetime="{{ $message->created_at->toIso8601String() }}"
                                        >
                                            {{ $message->created_at->format('M j, g:i A') }}

                                            @if ($message->status)
                                                · {{ ucfirst($message->status) }}
                                            @endif
                                        </time>

                                    </div>

                                </article>

                            @endforeach

                        @endif

                    </div>


                    <form
                        class="message-composer"
                        method="POST"
                        action="{{ route('admin.inbox.messages.store', $selectedConversation) }}"
                    >

                        @csrf

                        <label
                            class="sr-only"
                            for="body"
                        >
                            Write a message
                        </label>


                        <textarea
                            id="body"
                            name="body"
                            rows="2"
                            maxlength="5000"
                            placeholder="Write a reply..."
                            required
                        >{{ old('body') }}</textarea>


                        <div class="composer-footer">

                            @error('body')

                                <p class="composer-error">
                                    {{ $message }}
                                </p>

                            @enderror


                            @if (session('status'))

                                <p class="composer-status">
                                    {{ session('status') }}
                                </p>

                            @endif


                            <button
                                type="submit"
                                class="send-button"
                            >
                                Send message

                                <span aria-hidden="true">
                                    &rarr;
                                </span>
                            </button>

                        </div>

                    </form>

                @else

                    <div class="inbox-empty inbox-empty--detail">

                        <span
                            class="empty-mark"
                            aria-hidden="true"
                        >
                            &#8592;
                        </span>

                        <h2>
                            Your inbox is ready
                        </h2>

                        <p>
                            Select a conversation to view its message history.
                        </p>

                    </div>

                @endif

            </section>

            {{-- Customer profile --}}
            @if ($selectedConversation)
                @php
                    $profileCustomer = $selectedConversation->customer;
                    $profileCustomerName =
                        $profileCustomer?->name
                        ?: $profileCustomer?->phone
                        ?: 'Unknown customer';

                    $profileAvatar =
                        str($profileCustomerName)
                            ->substr(0, 1)
                            ->upper();
                @endphp

                <aside
                    class="customer-profile-panel"
                    aria-label="Customer profile"
                >
                    <div class="customer-profile-header">
                        <p class="customer-profile-kicker">Customer profile</p>
                        <h2 class="customer-profile-title">Customer details</h2>
                    </div>

                    <div class="customer-profile-body">
                        <div class="customer-profile-identity">
                            <span class="conversation-avatar conversation-avatar--large">
                                {{ $profileAvatar }}
                            </span>
                            <div>
                                <p class="customer-profile-name">{{ $profileCustomerName }}</p>
                                <p class="customer-profile-phone">
                                    {{ $profileCustomer?->phone ?: 'No phone number' }}
                                </p>
                            </div>
                        </div>

                        <section class="customer-profile-section">
                            <div class="customer-profile-section-heading">
                                <h3>Customer notes</h3>
                                <a
                                    class="customer-notes-download"
                                    href="{{ route('admin.inbox.customers.notes.download', $profileCustomer) }}"
                                >
                                    Download .txt
                                </a>
                            </div>

                            @if (session('notes_status'))
                                <p class="customer-notes-status">
                                    {{ session('notes_status') }}
                                </p>
                            @endif

                            <form
                                class="customer-notes-form"
                                method="POST"
                                action="{{ route('admin.inbox.customers.notes.update', $profileCustomer) }}"
                            >
                                @csrf
                                @method('PATCH')

                                <label
                                    class="sr-only"
                                    for="customer-notes"
                                >
                                    Customer notes
                                </label>

                                <textarea
                                    id="customer-notes"
                                    name="notes"
                                    maxlength="10000"
                                    placeholder="Add notes about this customer..."
                                >{{ old('notes', $profileCustomer?->notes) }}</textarea>

                                @error('notes')
                                    <p class="customer-notes-error">{{ $message }}</p>
                                @enderror

                                <button
                                    type="submit"
                                    class="customer-notes-save"
                                >
                                    Save notes
                                </button>
                            </form>
                        </section>

                        <section class="customer-profile-section">
                            <h3>Conversation</h3>
                            <dl class="customer-profile-meta">
                                <div class="customer-profile-meta-row">
                                    <dt>Channel</dt>
                                    <dd>{{ ucfirst($selectedConversation->channel) }}</dd>
                                </div>
                                <div class="customer-profile-meta-row">
                                    <dt>Status</dt>
                                    <dd>{{ ucfirst($selectedConversation->status) }}</dd>
                                </div>
                                <div class="customer-profile-meta-row">
                                    <dt>Messages</dt>
                                    <dd>{{ $selectedConversation->messages->count() }}</dd>
                                </div>
                            </dl>
                        </section>
                    </div>
                </aside>
            @endif

        </main>


        {{-- Live search --}}
        <script>
            (() => {
                const searchForm =
                    document.getElementById(
                        'conversation-search-form'
                    );

                const searchInput =
                    document.getElementById(
                        'conversation-search-input'
                    );

                const clearButton =
                    document.getElementById(
                        'conversation-search-clear'
                    );


                if (!searchForm || !searchInput) {
                    return;
                }


                let searchTimer = null;

                let lastSearchValue =
                    searchInput.value.trim();


                function updateClearButton() {

                    if (!clearButton) {
                        return;
                    }

                    clearButton.hidden =
                        searchInput.value.trim() === '';
                }


                function performSearch() {

                    const search =
                        searchInput.value.trim();


                    if (search === lastSearchValue) {
                        return;
                    }


                    lastSearchValue =
                        search;


                    const url =
                        new URL(
                            searchForm.action,
                            window.location.origin
                        );


                    if (search !== '') {

                        url.searchParams.set(
                            'search',
                            search
                        );

                    } else {

                        url.searchParams.delete(
                            'search'
                        );
                    }


                    window.location.href =
                        url.toString();
                }


                searchInput.addEventListener(
                    'input',
                    () => {

                        updateClearButton();

                        clearTimeout(searchTimer);


                        searchTimer =
                            setTimeout(
                                performSearch,
                                400
                            );
                    }
                );


                searchInput.addEventListener(
                    'keydown',
                    (event) => {

                        if (event.key === 'Escape') {

                            event.preventDefault();

                            searchInput.value = '';

                            updateClearButton();

                            clearTimeout(searchTimer);

                            window.location.href =
                                searchForm.action;
                        }
                    }
                );


                if (clearButton) {

                    clearButton.addEventListener(
                        'click',
                        () => {

                            searchInput.value = '';

                            updateClearButton();

                            clearTimeout(searchTimer);

                            window.location.href =
                                searchForm.action;
                        }
                    );
                }


                searchForm.addEventListener(
                    'submit',
                    (event) => {

                        event.preventDefault();

                        clearTimeout(searchTimer);

                        lastSearchValue = '';

                        performSearch();
                    }
                );


                updateClearButton();

            })();
        </script>


        @if ($selectedConversation)

            {{-- Message polling --}}
            <script>
                (() => {

                    const conversationId =
                        @json($selectedConversation->id);

                    const stream =
                        document.getElementById(
                            'message-stream'
                        );

                    const messagesUrl =
                        @json(route(
                            'admin.inbox.messages',
                            $selectedConversation
                        ));


                    if (!stream || !conversationId) {
                        return;
                    }


                    let lastMessageId = 0;


                    const existingMessages =
                        stream.querySelectorAll(
                            '[data-message-id]'
                        );


                    if (existingMessages.length > 0) {

                        const lastExistingMessage =
                            existingMessages[
                                existingMessages.length - 1
                            ];


                        lastMessageId =
                            Number(
                                lastExistingMessage.getAttribute(
                                    'data-message-id'
                                )
                            ) || 0;
                    }


                    function scrollToBottom() {

                        stream.scrollTop =
                            stream.scrollHeight;
                    }


                    function appendMessage(message) {

                        if (
                            document.querySelector(
                                `[data-message-id="${message.id}"]`
                            )
                        ) {
                            return;
                        }


                        const row =
                            document.createElement(
                                'article'
                            );

                        row.className =
                            `message-row message-row--${message.direction}`;

                        row.dataset.messageId =
                            message.id;


                        const bubble =
                            document.createElement(
                                'div'
                            );

                        bubble.className =
                            'message-bubble';


                        const body =
                            document.createElement(
                                'p'
                            );

                        body.textContent =
                            message.body || '';


                        const time =
                            document.createElement(
                                'time'
                            );


                        if (message.created_at) {

                            time.dateTime =
                                message.created_at;

                            time.textContent =
                                new Date(
                                    message.created_at
                                ).toLocaleString(
                                    undefined,
                                    {
                                        month: 'short',
                                        day: 'numeric',
                                        hour: 'numeric',
                                        minute: '2-digit',
                                    }
                                );
                        }


                        if (message.status) {

                            const status =
                                message.status
                                    .charAt(0)
                                    .toUpperCase()
                                + message.status.slice(1);


                            time.textContent +=
                                ` · ${status}`;
                        }


                        bubble.appendChild(body);

                        bubble.appendChild(time);

                        row.appendChild(bubble);

                        stream.appendChild(row);


                        lastMessageId =
                            Math.max(
                                lastMessageId,
                                Number(message.id) || 0
                            );
                    }


                    async function pollMessages() {

                        try {

                            const response =
                                await fetch(
                                    `${messagesUrl}?after_id=${lastMessageId}`,
                                    {
                                        method: 'GET',
                                        headers: {
                                            'Accept': 'application/json',
                                        },
                                        cache: 'no-store',
                                    }
                                );


                            if (!response.ok) {
                                return;
                            }


                            const data =
                                await response.json();


                            if (
                                !data.messages ||
                                !Array.isArray(data.messages)
                            ) {
                                return;
                            }


                            if (
                                data.messages.length === 0
                            ) {
                                return;
                            }


                            data.messages.forEach(
                                (message) => {
                                    appendMessage(message);
                                }
                            );


                            scrollToBottom();

                        } catch (error) {

                            console.error(
                                'Inbox polling failed.',
                                error
                            );
                        }
                    }


                    scrollToBottom();


                    setInterval(
                        pollMessages,
                        5000
                    );

                })();
            </script>

        @endif

    </body>
</html>