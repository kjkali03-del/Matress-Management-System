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

                                        @if ($message->message_type === 'image')
                                            @if ($message->media_url)
                                                <img
                                                    class="message-media message-media--image"
                                                    src="{{ $message->media_url }}"
                                                    alt="{{ $message->media_filename ?: 'WhatsApp image' }}"
                                                    loading="lazy"
                                                >
                                            @else
                                                <div class="message-media-placeholder">
                                                    <span aria-hidden="true">📷</span>
                                                    <span>{{ $message->media_filename ?: 'Image sent' }}</span>
                                                </div>
                                            @endif

                                            @if ($message->media_caption || $message->body)
                                                <p>{{ $message->media_caption ?: $message->body }}</p>
                                            @endif
                                        @elseif ($message->message_type === 'video')
                                            @if ($message->media_url)
                                                <video
                                                    class="message-media message-media--video"
                                                    controls
                                                    preload="metadata"
                                                >
                                                    <source
                                                        src="{{ $message->media_url }}"
                                                        type="{{ $message->media_mime_type ?: 'video/mp4' }}"
                                                    >
                                                </video>
                                            @else
                                                <div class="message-media-placeholder">
                                                    <span aria-hidden="true">🎥</span>
                                                    <span>{{ $message->media_filename ?: 'Video sent' }}</span>
                                                </div>
                                            @endif

                                            @if ($message->media_caption || $message->body)
                                                <p>{{ $message->media_caption ?: $message->body }}</p>
                                            @endif
                                        @elseif ($message->message_type === 'location')
                                            <div class="message-location">
                                                <div class="message-location-icon" aria-hidden="true">📍</div>
                                                <div>
                                                    <strong>{{ $message->location_name ?: 'Location' }}</strong>
                                                    @if ($message->location_address)
                                                        <span>{{ $message->location_address }}</span>
                                                    @endif
                                                    <a
                                                        href="https://www.google.com/maps?q={{ $message->latitude }},{{ $message->longitude }}"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        Open in Maps
                                                    </a>
                                                </div>
                                            </div>
                                        @else
                                            <p>{{ $message->body }}</p>
                                        @endif

                                        <div class="message-meta">
                                            <time
                                                datetime="{{ $message->created_at->toIso8601String() }}"
                                            >
                                                {{ $message->created_at->format('M j, g:i A') }}

                                                @if ($message->status)
                                                    · {{ ucfirst($message->status) }}
                                                @endif
                                            </time>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.inbox.messages.destroy', $message) }}"
                                                class="message-delete-form"
                                                onsubmit="return confirm('Delete this message from the Inbox?');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="message-delete-button"
                                                    title="Delete from Inbox"
                                                    aria-label="Delete message"
                                                >
                                                    &times;
                                                </button>
                                            </form>
                                        </div>

                                    </div>

                                </article>

                            @endforeach

                        @endif

                    </div>


                    <div class="message-composer">

                        <form
                            class="composer-text-form"
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
                            >{{ old('body') }}</textarea>

                            <div class="composer-footer">
                                <div class="composer-tools" aria-label="Message attachments">
                                    <label class="composer-tool-button" title="Attach photo">
                                        <span aria-hidden="true">📷</span>
                                        <span>Photo</span>
                                        <input
                                            type="file"
                                            name="image"
                                            accept="image/jpeg,image/png,image/webp"
                                            form="image-upload-form"
                                            hidden
                                            id="image-picker"
                                        >
                                    </label>

                                    <label class="composer-tool-button" title="Attach video">
                                        <span aria-hidden="true">🎥</span>
                                        <span>Video</span>
                                        <input
                                            type="file"
                                            name="video"
                                            accept="video/mp4,video/3gpp"
                                            form="video-upload-form"
                                            hidden
                                            id="video-picker"
                                        >
                                    </label>

                                    <button
                                        type="button"
                                        class="composer-tool-button"
                                        id="send-location-button"
                                        title="Send current location"
                                    >
                                        <span aria-hidden="true">📍</span>
                                        <span>Location</span>
                                    </button>
                                </div>

                                <div class="composer-actions">
                                    @error('body')
                                        <p class="composer-error">{{ $message }}</p>
                                    @enderror

                                    @error('image')
                                        <p class="composer-error">{{ $message }}</p>
                                    @enderror

                                    @error('video')
                                        <p class="composer-error">{{ $message }}</p>
                                    @enderror

                                    @if (session('status'))
                                        <p class="composer-status">{{ session('status') }}</p>
                                    @endif

                                    <button type="submit" class="send-button">
                                        Send message
                                        <span aria-hidden="true">&rarr;</span>
                                    </button>
                                </div>
                            </div>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('admin.inbox.messages.image', $selectedConversation) }}"
                            enctype="multipart/form-data"
                            id="image-upload-form"
                            hidden
                        >
                            @csrf
                            <input type="file" name="image" id="image-upload-input" required>
                            <input type="hidden" name="caption" id="image-caption">
                        </form>

                        <form
                            method="POST"
                            action="{{ route('admin.inbox.messages.video', $selectedConversation) }}"
                            enctype="multipart/form-data"
                            id="video-upload-form"
                            hidden
                        >
                            @csrf
                            <input type="file" name="video" id="video-upload-input" required>
                            <input type="hidden" name="caption" id="video-caption">
                        </form>

                        <p class="composer-hint" id="composer-hint" aria-live="polite">
                            Photo, Video and Location are available from the attachment buttons.
                        </p>

                    </div>

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

            {{-- Composer attachments and location --}}
            <script>
                (() => {
                    const imagePicker = document.getElementById('image-picker');
                    const videoPicker = document.getElementById('video-picker');
                    const imageUploadInput = document.getElementById('image-upload-input');
                    const videoUploadInput = document.getElementById('video-upload-input');
                    const imageCaption = document.getElementById('image-caption');
                    const videoCaption = document.getElementById('video-caption');
                    const body = document.getElementById('body');
                    const locationButton = document.getElementById('send-location-button');
                    const hint = document.getElementById('composer-hint');

                    function setHint(text) {
                        if (hint) hint.textContent = text;
                    }

                    imagePicker?.addEventListener('change', () => {
                        if (!imagePicker.files?.length || !imageUploadInput) return;
                        imageUploadInput.files = imagePicker.files;
                        imageCaption.value = body?.value?.trim() || '';
                        setHint(`Sending: ${imagePicker.files[0].name}`);
                        document.getElementById('image-upload-form')?.submit();
                    });

                    videoPicker?.addEventListener('change', () => {
                        if (!videoPicker.files?.length || !videoUploadInput) return;
                        videoUploadInput.files = videoPicker.files;
                        videoCaption.value = body?.value?.trim() || '';
                        setHint(`Sending: ${videoPicker.files[0].name}`);
                        document.getElementById('video-upload-form')?.submit();
                    });

                    locationButton?.addEventListener('click', () => {
                        if (!navigator.geolocation) {
                            setHint('This browser does not support location sharing.');
                            return;
                        }

                        locationButton.disabled = true;
                        setHint('Getting your current location...');

                        navigator.geolocation.getCurrentPosition(async (position) => {
                            try {
                                const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
                                const response = await fetch(@json(route('admin.inbox.messages.location', $selectedConversation)), {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-CSRF-TOKEN': token,
                                    },
                                    body: JSON.stringify({
                                        latitude: position.coords.latitude,
                                        longitude: position.coords.longitude,
                                    }),
                                });

                                const data = await response.json();
                                if (!response.ok) throw new Error(data.message || 'Unable to send location.');

                                if (data.data) {
                                    window.dispatchEvent(
                                        new CustomEvent('inbox:message-created', {
                                            detail: data.data,
                                        })
                                    );
                                }

                                setHint('Current location sent successfully.');
                            } catch (error) {
                                console.error('Location send failed.', error);
                                setHint(error.message || 'Unable to send location.');
                            } finally {
                                locationButton.disabled = false;
                            }
                        }, (error) => {
                            locationButton.disabled = false;
                            const messages = {
                                1: 'Location permission was denied. Allow location access and try again.',
                                2: 'Your location could not be determined. Try again.',
                                3: 'Location request timed out. Try again.',
                            };
                            setHint(messages[error.code] || 'Unable to get your location.');
                        }, {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0,
                        });
                    });
                })();
            </script>

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


                    function escapeHtml(value) {
                        const div = document.createElement('div');
                        div.textContent = value ?? '';
                        return div.innerHTML;
                    }

                    function messageContentHtml(message) {
                        const type = message.message_type || 'text';
                        const caption = message.media_caption || message.body || '';

                        if (type === 'image') {
                            const media = message.media_url
                                ? `<img class="message-media message-media--image" src="${escapeHtml(message.media_url)}" alt="${escapeHtml(message.media_filename || 'WhatsApp image')}" loading="lazy">`
                                : `<div class="message-media-placeholder"><span aria-hidden="true">📷</span><span>${escapeHtml(message.media_filename || 'Image sent')}</span></div>`;
                            return `${media}${caption ? `<p>${escapeHtml(caption)}</p>` : ''}`;
                        }

                        if (type === 'video') {
                            const media = message.media_url
                                ? `<video class="message-media message-media--video" controls preload="metadata"><source src="${escapeHtml(message.media_url)}" type="${escapeHtml(message.media_mime_type || 'video/mp4')}"></video>`
                                : `<div class="message-media-placeholder"><span aria-hidden="true">🎥</span><span>${escapeHtml(message.media_filename || 'Video sent')}</span></div>`;
                            return `${media}${caption ? `<p>${escapeHtml(caption)}</p>` : ''}`;
                        }

                        if (type === 'location') {
                            const lat = Number(message.latitude);
                            const lng = Number(message.longitude);
                            const mapUrl = Number.isFinite(lat) && Number.isFinite(lng)
                                ? `https://www.google.com/maps?q=${lat},${lng}`
                                : '#';
                            return `<div class="message-location"><div class="message-location-icon" aria-hidden="true">📍</div><div><strong>${escapeHtml(message.location_name || 'Location')}</strong>${message.location_address ? `<span>${escapeHtml(message.location_address)}</span>` : ''}<a href="${mapUrl}" target="_blank" rel="noopener noreferrer">Open in Maps</a></div></div>`;
                        }

                        return `<p>${escapeHtml(message.body || '')}</p>`;
                    }

                    window.addEventListener('inbox:message-created', (event) => {
                        if (event.detail) {
                            appendMessage(event.detail);
                            scrollToBottom();
                        }
                    });


                    function appendMessage(message) {
                        if (document.querySelector(`[data-message-id="${message.id}"]`)) return;

                        const row = document.createElement('article');
                        row.className = `message-row message-row--${message.direction}`;
                        row.dataset.messageId = message.id;

                        const bubble = document.createElement('div');
                        bubble.className = 'message-bubble';
                        bubble.innerHTML = messageContentHtml(message);

                        const meta = document.createElement('div');
                        meta.className = 'message-meta';

                        const time = document.createElement('time');
                        if (message.created_at) {
                            time.dateTime = message.created_at;
                            time.textContent = new Date(message.created_at).toLocaleString(undefined, {
                                month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit',
                            });
                        }

                        if (message.status) {
                            const status = message.status.charAt(0).toUpperCase() + message.status.slice(1);
                            time.textContent += ` · ${status}`;
                        }
                        meta.appendChild(time);

                        const deleteForm = document.createElement('form');
                        deleteForm.method = 'POST';
                        deleteForm.action = `{{ url('/admin/inbox/messages') }}/${message.id}`;
                        deleteForm.className = 'message-delete-form';
                        deleteForm.onsubmit = () => confirm('Delete this message from the Inbox?');

                        const csrf = document.createElement('input');
                        csrf.type = 'hidden'; csrf.name = '_token';
                        csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';

                        const method = document.createElement('input');
                        method.type = 'hidden'; method.name = '_method'; method.value = 'DELETE';

                        const button = document.createElement('button');
                        button.type = 'submit';
                        button.className = 'message-delete-button';
                        button.title = 'Delete from Inbox';
                        button.setAttribute('aria-label', 'Delete message');
                        button.textContent = '×';

                        deleteForm.append(csrf, method, button);
                        meta.appendChild(deleteForm);
                        bubble.appendChild(meta);
                        row.appendChild(bubble);
                        stream.appendChild(row);

                        lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
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