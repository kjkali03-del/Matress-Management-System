<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Inbox | Wonder Godoro Point</title>
        <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
        <link rel="stylesheet" href="{{ asset('css/inbox.css') }}">
    </head>
    <body class="inbox-page">
        <header class="inbox-header">
            <a class="admin-brand" href="{{ route('admin') }}">
                <img src="{{ asset('img/logo.png') }}" alt="Wonder Godoro Point Mattress Shop">
                <span>Admin workspace</span>
            </a>
            <div class="inbox-header-actions">
                <span class="inbox-user">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="admin-logout">Sign out</button>
                </form>
            </div>
        </header>

        <main class="inbox-shell">
            <aside class="conversation-panel" aria-label="Conversations">
                <div class="panel-heading">
                    <div>
                        <p class="auth-kicker">Customer communication</p>
                        <h1>Inbox</h1>
                    </div>
                    <span class="conversation-count">{{ $conversations->count() }}</span>
                </div>

                @if ($conversations->isEmpty())
                    <div class="inbox-empty inbox-empty--list">
                        <span class="empty-mark" aria-hidden="true">&#8722;</span>
                        <h2>No conversations yet</h2>
                        <p>New customer messages will appear here.</p>
                    </div>
                @else
                    <nav class="conversation-list" aria-label="Conversation list">
                        @foreach ($conversations as $conversation)
                            @php($isSelected = $selectedConversation?->id === $conversation->id)
                            <a
                                class="conversation-item {{ $isSelected ? 'is-selected' : '' }}"
                                href="{{ route('admin.inbox', ['conversation' => $conversation->id]) }}"
                                aria-current="{{ $isSelected ? 'page' : 'false' }}"
                            >
                                <span class="conversation-avatar">{{ str($conversation->customer->name)->substr(0, 1)->upper() }}</span>
                                <span class="conversation-summary">
                                    <span class="conversation-topline">
                                        <strong>{{ $conversation->customer->name }}</strong>
                                        @if ($conversation->last_message_at)
                                            <time datetime="{{ $conversation->last_message_at->toIso8601String() }}">{{ $conversation->last_message_at->shortAbsoluteDiffForHumans() }}</time>
                                        @endif
                                    </span>
                                    <span class="conversation-phone">{{ $conversation->customer->phone ?: 'No phone number' }}</span>
                                    <span class="conversation-preview">{{ $conversation->latestMessage?->body ?: 'No messages yet' }}</span>
                                </span>
                                @if ($conversation->unread_messages_count)
                                    <span class="unread-badge" aria-label="{{ $conversation->unread_messages_count }} unread messages">{{ $conversation->unread_messages_count }}</span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                @endif
            </aside>

            <section class="conversation-panel conversation-detail" aria-label="Selected conversation">
                @if ($selectedConversation)
                    <header class="conversation-detail-header">
                        <div class="customer-heading">
                            <span class="conversation-avatar conversation-avatar--large">{{ str($selectedConversation->customer->name)->substr(0, 1)->upper() }}</span>
                            <div>
                                <h2>{{ $selectedConversation->customer->name }}</h2>
                                <p>{{ $selectedConversation->customer->phone ?: 'No phone number' }} <span class="channel-label">{{ ucfirst($selectedConversation->channel) }}</span></p>
                            </div>
                        </div>
                        <span class="status-pill status-pill--{{ $selectedConversation->status }}">{{ ucfirst($selectedConversation->status) }}</span>
                    </header>

                    <div class="message-stream">
                        @if ($selectedConversation->messages->isEmpty())
                            <div class="inbox-empty">
                                <span class="empty-mark" aria-hidden="true">&#8595;</span>
                                <h2>Start the conversation</h2>
                                <p>There are no messages in this thread yet.</p>
                            </div>
                        @else
                            <div class="message-day-label">Conversation history</div>
                            @foreach ($selectedConversation->messages as $message)
                                <article class="message-row message-row--{{ $message->direction }}">
                                    <div class="message-bubble">
                                        <p>{{ $message->body }}</p>
                                        <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('M j, g:i A') }} · {{ ucfirst($message->status) }}</time>
                                    </div>
                                </article>
                            @endforeach
                        @endif
                    </div>

                    <form class="message-composer" method="POST" action="{{ route('admin.inbox.messages.store', $selectedConversation) }}">
                        @csrf
                        <label class="sr-only" for="body">Write a message</label>
                        <textarea id="body" name="body" rows="2" maxlength="5000" placeholder="Write a reply..." required>{{ old('body') }}</textarea>
                        <div class="composer-footer">
                            @error('body')
                                <p class="composer-error">{{ $message }}</p>
                            @enderror
                            @if (session('status'))
                                <p class="composer-status">{{ session('status') }}</p>
                            @endif
                            <button type="submit" class="send-button">Save message <span aria-hidden="true">&rarr;</span></button>
                        </div>
                    </form>
                @else
                    <div class="inbox-empty inbox-empty--detail">
                        <span class="empty-mark" aria-hidden="true">&#8592;</span>
                        <h2>Your inbox is ready</h2>
                        <p>Select a conversation to view its message history.</p>
                    </div>
                @endif
            </section>
        </main>
    </body>
</html>