<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Message;
use App\Services\ConversationMessageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function __construct(
        private readonly ConversationMessageService $messageService
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $conversations = Conversation::query()
            ->with('customer')
            ->with('latestMessage')
            ->withCount([
                'messages as unread_messages_count' => function ($query) {
                    $query
                        ->where('direction', 'inbound')
                        ->whereNull('read_at');
                },
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($conversationQuery) use ($search) {
                    $conversationQuery
                        ->whereHas('customer', function ($customerQuery) use ($search) {
                            $customerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        })
                        ->orWhereHas('messages', function ($messageQuery) use ($search) {
                            $messageQuery->where('body', 'like', "%{$search}%");
                        });
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $selectedConversation = $conversations->firstWhere(
            'id',
            (int) $request->integer('conversation')
        ) ?? $conversations->first();

        if ($selectedConversation) {
            $selectedConversation->load([
                'customer',
                'messages' => function ($query) {
                    $query
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ]);

            $selectedConversation->messages()
                ->where('direction', 'inbound')
                ->whereNull('read_at')
                ->update([
                    'read_at' => now(),
                ]);
        }

        return view('admin.inbox', [
            'conversations' => $conversations,
            'selectedConversation' => $selectedConversation,
            'search' => $search,
        ]);
    }

    public function messages(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $afterId = $request->integer('after_id', 0);

        $messages = $conversation->messages()
            ->where('id', '>', $afterId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->map(fn (Message $message) => [
                'id' => $message->id,
                'direction' => $message->direction,
                'message_type' => $message->message_type,
                'body' => $message->body,
                'status' => $message->status,
                'media_id' => $message->media_id,
                'media_url' => $message->media_url,
                'media_mime_type' => $message->media_mime_type,
                'media_filename' => $message->media_filename,
                'media_caption' => $message->media_caption,
                'latitude' => $message->latitude,
                'longitude' => $message->longitude,
                'location_name' => $message->location_name,
                'location_address' => $message->location_address,
                'created_at' => $message->created_at?->toISOString(),
            ]);

        return response()->json([
            'messages' => $messages,
        ]);
    }

    public function store(
        Request $request,
        Conversation $conversation
    ): RedirectResponse {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->messageService->sendText(
            $conversation,
            trim($validated['body'])
        );

        return redirect()->route('admin.inbox', [
            'conversation' => $conversation->id,
        ])->with('status', 'Message saved to the conversation.');
    }

    public function sendImage(
        Request $request,
        Conversation $conversation
    ): RedirectResponse {
        $validated = $request->validate([
            'image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'caption' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        /** @var UploadedFile $image */
        $image = $validated['image'];

        $this->messageService->sendImageFile(
            $conversation,
            $image,
            filled($validated['caption'] ?? null)
                ? trim((string) $validated['caption'])
                : null
        );

        return redirect()->route('admin.inbox', [
            'conversation' => $conversation->id,
        ])->with('status', 'Image sent successfully.');
    }

    public function sendVideo(
        Request $request,
        Conversation $conversation
    ): RedirectResponse {
        $validated = $request->validate([
            'video' => [
                'required',
                'file',
                'mimes:mp4,3gp',
                'max:16384',
            ],
            'caption' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);

        /** @var UploadedFile $video */
        $video = $validated['video'];

        $this->messageService->sendVideoFile(
            $conversation,
            $video,
            filled($validated['caption'] ?? null)
                ? trim((string) $validated['caption'])
                : null
        );

        return redirect()->route('admin.inbox', [
            'conversation' => $conversation->id,
        ])->with('status', 'Video sent successfully.');
    }

    public function sendLocation(
        Request $request,
        Conversation $conversation
    ): JsonResponse {
        $validated = $request->validate([
            'latitude' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'address' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $message = $this->messageService->sendLocation(
            $conversation,
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            filled($validated['name'] ?? null)
                ? trim((string) $validated['name'])
                : null,
            filled($validated['address'] ?? null)
                ? trim((string) $validated['address'])
                : null,
        );

        return response()->json([
            'message' => 'Location sent successfully.',
            'data' => [
                'id' => $message->id,
                'direction' => $message->direction,
                'message_type' => $message->message_type,
                'latitude' => $message->latitude,
                'longitude' => $message->longitude,
                'location_name' => $message->location_name,
                'location_address' => $message->location_address,
                'status' => $message->status,
                'created_at' => $message->created_at?->toISOString(),
            ],
        ]);
    }

    public function destroyMessage(Message $message): RedirectResponse
    {
        $conversationId = $message->conversation_id;

        $message->delete();

        return redirect()->route('admin.inbox', [
            'conversation' => $conversationId,
        ])->with('status', 'Message deleted from the Inbox.');
    }

    public function updateCustomerNotes(
        Request $request,
        Customer $customer
    ): RedirectResponse {
        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);

        $customer->update([
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
        ]);

        $conversation = $customer->conversations()
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->first();

        return redirect()->route('admin.inbox', array_filter([
            'conversation' => $conversation?->id,
        ]))->with('notes_status', 'Customer notes saved successfully.');
    }

    public function downloadCustomerNotes(Customer $customer): Response
    {
        $customerName = $customer->name ?: $customer->phone ?: 'Customer';
        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $customerName) ?: 'customer';
        $safeName = trim($safeName, '-_') ?: 'customer';

        $content = implode(PHP_EOL, [
            'WONDER GODORO POINT',
            'CUSTOMER NOTES',
            str_repeat('=', 40),
            'Customer: ' . $customerName,
            'Phone: ' . ($customer->phone ?: 'N/A'),
            'Downloaded: ' . now()->format('Y-m-d H:i:s'),
            '',
            'NOTES',
            str_repeat('-', 40),
            $customer->notes ?: 'No notes have been added for this customer.',
            '',
        ]);

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $safeName . '-notes.txt"',
        ]);
    }
}