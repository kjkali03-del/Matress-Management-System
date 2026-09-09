<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\ConversationMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function __construct(private readonly ConversationMessageService $messageService) {}

    public function index(Request $request): View
    {
        $conversations = Conversation::query()
            ->with('customer')
            ->with('latestMessage')
            ->withCount(['messages as unread_messages_count' => function ($query) {
                $query->where('direction', 'inbound')->where('status', '!=', 'read');
            }])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $selectedConversation = $conversations->firstWhere('id', (int) $request->integer('conversation'))
            ?? $conversations->first();

        if ($selectedConversation) {
            $selectedConversation->load(['customer', 'messages' => function ($query) {
                $query->orderBy('created_at')->orderBy('id');
            }]);
        }

        return view('admin.inbox', compact('conversations', 'selectedConversation'));
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->messageService->sendText($conversation, trim($validated['body']));

        return redirect()->route('admin.inbox', ['conversation' => $conversation->id])
            ->with('status', 'Message saved to the conversation.');
    }
}