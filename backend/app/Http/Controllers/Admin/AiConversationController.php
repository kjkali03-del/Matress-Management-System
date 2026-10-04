<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\AiConversationState;
use App\Models\AiEscalation;
use App\Models\Conversation;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AiConversationController extends Controller
{
    public function show(Conversation $conversation): View
    {
        $conversation->load('customer');
        $state = AiConversationState::query()->firstOrCreate(
            ['conversation_id' => $conversation->id],
            ['status' => 'active', 'stage' => 'NEW', 'context' => []],
        );

        return view('admin.ai.conversation', [
            'conversation' => $conversation,
            'state' => $state,
            'messages' => $conversation->messages()->latest('id')->limit(150)->get()->reverse()->values(),
            'actions' => AiAction::query()->where('conversation_id', $conversation->id)->latest()->limit(100)->get(),
            'escalations' => AiEscalation::query()->where('conversation_id', $conversation->id)->latest()->get(),
            'discussedProducts' => Product::query()
                ->whereIn('id', data_get($state->context, 'discussed_product_ids', []))
                ->get(['id', 'name', 'size', 'price']),
            'latestOrder' => $conversation->customer->orders()->latest('ordered_at')->first(),
            'deploymentEnabled' => filter_var(config('ai.enabled'), FILTER_VALIDATE_BOOL),
        ]);
    }

    public function takeOver(Conversation $conversation): RedirectResponse
    {
        return $this->transition($conversation, 'take_over');
    }

    public function returnToAi(Conversation $conversation): RedirectResponse
    {
        return $this->transition($conversation, 'return_to_ai');
    }

    public function pause(Conversation $conversation): RedirectResponse
    {
        return $this->transition($conversation, 'pause');
    }

    public function resume(Conversation $conversation): RedirectResponse
    {
        return $this->transition($conversation, 'resume');
    }

    public function escalate(Conversation $conversation): RedirectResponse
    {
        return $this->transition($conversation, 'escalate');
    }

    public function close(Conversation $conversation): RedirectResponse
    {
        return $this->transition($conversation, 'close');
    }

    private function transition(Conversation $conversation, string $action): RedirectResponse
    {
        DB::transaction(function () use ($conversation, $action): void {
            $lockedConversation = Conversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $state = AiConversationState::query()->firstOrCreate(
                ['conversation_id' => $lockedConversation->id],
                ['status' => 'active', 'stage' => 'NEW', 'context' => []],
            );
            $state->refresh();
            $escalation = AiEscalation::query()
                ->where('conversation_id', $lockedConversation->id)
                ->whereIn('status', ['open', 'taken_over'])
                ->latest('id')
                ->first();

            switch ($action) {
                case 'take_over':
                    $state->update([
                        'status' => 'human',
                        'stage' => 'HUMAN_HANDOFF',
                        'next_action' => 'Administrator has taken over the conversation.',
                    ]);
                    $escalation ??= AiEscalation::query()->create([
                        'conversation_id' => $lockedConversation->id,
                        'customer_id' => $lockedConversation->customer_id,
                        'status' => 'open',
                        'reason' => 'Administrator takeover requested.',
                        'intent' => $state->intent,
                        'summary' => $state->summary,
                    ]);
                    $escalation->update([
                        'status' => 'taken_over',
                        'taken_over_by' => auth()->id(),
                        'taken_over_at' => now(),
                    ]);
                    break;

                case 'return_to_ai':
                case 'resume':
                    $state->update([
                        'status' => 'active',
                        'stage' => $state->stage === 'HUMAN_HANDOFF' ? 'DISCOVERY' : $state->stage,
                        'next_action' => null,
                    ]);
                    $escalation?->update(['status' => 'resolved', 'resolved_at' => now()]);
                    break;

                case 'pause':
                    $state->update(['status' => 'paused', 'next_action' => 'Autonomous replies paused by an administrator.']);
                    break;

                case 'escalate':
                    $state->update([
                        'status' => 'escalated',
                        'stage' => 'ESCALATED',
                        'next_action' => 'Administrator review required.',
                    ]);
                    $escalation ??= AiEscalation::query()->create([
                        'conversation_id' => $lockedConversation->id,
                        'customer_id' => $lockedConversation->customer_id,
                        'status' => 'open',
                        'reason' => 'Administrator requested escalation.',
                        'intent' => $state->intent,
                        'summary' => $state->summary,
                    ]);
                    break;

                case 'close':
                    $state->update(['status' => 'closed', 'stage' => 'CLOSED', 'next_action' => null]);
                    $escalation?->update(['status' => 'resolved', 'resolved_at' => now()]);
                    break;

                default:
                    throw new NotFoundHttpException();
            }

            AiAction::query()->create([
                'conversation_id' => $lockedConversation->id,
                'customer_id' => $lockedConversation->customer_id,
                'administrator_id' => auth()->id(),
                'actor' => 'administrator',
                'tool' => 'conversation_' . $action,
                'status' => 'completed',
                'summary' => 'Administrator changed the AI conversation mode.',
            ]);
        });

        return redirect()
            ->route('admin.ai.conversations.show', $conversation)
            ->with('status', 'Conversation mode updated.');
    }
}
