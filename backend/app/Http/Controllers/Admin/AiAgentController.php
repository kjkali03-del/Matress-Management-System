<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiAction;
use App\Models\AiConversationState;
use App\Models\AiEscalation;
use App\Models\AiFollowUp;
use App\Models\AiKnowledge;
use App\Models\AiSetting;
use App\Models\Order;
use App\Services\AI\AiProviderInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    private const KNOWLEDGE_CATEGORIES = [
        'business', 'faq', 'sales', 'delivery', 'payment',
        'warranty', 'returns', 'promotion', 'escalation', 'rules',
    ];

    public function index(Request $request, AiProviderInterface $provider): View
    {
        $filter = $request->validate([
            'status' => ['nullable', 'in:active,waiting,escalated,human,paused,closed,all'],
            'period' => ['nullable', 'in:today,week,all'],
        ]);

        $states = AiConversationState::query()
            ->with(['conversation.customer', 'conversation.latestMessage'])
            ->when(
                ($filter['status'] ?? '') === 'waiting',
                fn ($query) => $query->where('status', 'active')
                    ->whereColumn('last_customer_message_at', '>', 'last_ai_message_at'),
            )
            ->when(
                in_array($filter['status'] ?? '', ['active', 'escalated', 'human', 'paused', 'closed'], true),
                fn ($query) => $query->where('status', $filter['status']),
            )
            ->when(
                ($filter['period'] ?? '') === 'today',
                fn ($query) => $query->where('last_customer_message_at', '>=', today()),
            )
            ->when(
                ($filter['period'] ?? '') === 'week',
                fn ($query) => $query->where('last_customer_message_at', '>=', now()->startOfWeek()),
            )
            ->latest('last_customer_message_at')
            ->paginate(25)
            ->withQueryString();

        $today = today();
        $aiOrderIds = AiAction::query()
            ->where('tool', 'create_confirmed_order')
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->whereNotNull('order_id')
            ->select('order_id');

        $stats = [
            'active_conversations' => AiConversationState::query()->where('status', 'active')->count(),
            'ai_conversations_today' => AiConversationState::query()->whereDate('last_ai_message_at', $today)->count(),
            'orders_created_today' => AiAction::query()
                ->where('tool', 'create_confirmed_order')
                ->where('status', 'completed')
                ->whereDate('created_at', $today)
                ->count(),
            'customers_assisted_today' => AiAction::query()
                ->where('actor', 'ai')
                ->whereDate('created_at', $today)
                ->distinct('customer_id')
                ->count('customer_id'),
            'escalated_conversations' => AiConversationState::query()->where('status', 'escalated')->count(),
            'follow_ups_sent_today' => AiFollowUp::query()->where('status', 'sent')->whereDate('sent_at', $today)->count(),
            'ai_sales_value_today' => Order::query()->whereIn('id', $aiOrderIds)->sum('total_amount'),
        ];

        return view('admin.ai.index', [
            'states' => $states,
            'stats' => $stats,
            'filters' => $filter,
            'settings' => AiSetting::query()->pluck('value', 'key'),
            'knowledge' => AiKnowledge::query()->orderBy('category')->orderBy('sort_order')->orderBy('title')->get(),
            'providerConfigured' => $provider->isConfigured(),
            'deploymentEnabled' => filter_var(config('ai.enabled'), FILTER_VALIDATE_BOOL),
            'knowledgeCategories' => self::KNOWLEDGE_CATEGORIES,
        ]);
    }

    public function updateSettings(Request $request, AiProviderInterface $provider): RedirectResponse
    {
        $values = Validator::make($request->all(), [
            'ai_enabled' => ['sometimes', 'boolean'],
            'auto_reply_enabled' => ['sometimes', 'boolean'],
            'auto_order_creation' => ['sometimes', 'boolean'],
            'auto_follow_up' => ['sometimes', 'boolean'],
            'default_language' => ['required', 'in:auto,sw,en'],
            'business_tone' => ['required', 'in:warm_professional,professional,friendly'],
            'maximum_follow_ups' => ['required', 'integer', 'min:0', 'max:5'],
            'escalation_threshold' => ['required', 'numeric', 'between:0.1,0.95'],
            'follow_up_delay_minutes' => ['required', 'integer', 'min:5', 'max:1380'],
            'quiet_hours_start' => ['required', 'date_format:H:i'],
            'quiet_hours_end' => ['required', 'date_format:H:i'],
        ])->validate();

        if ($values['quiet_hours_start'] === $values['quiet_hours_end']) {
            throw ValidationException::withMessages([
                'quiet_hours_end' => 'Quiet-hours start and end times must be different.',
            ]);
        }

        if (
            ($values['ai_enabled'] ?? false)
            && (! filter_var(config('ai.enabled'), FILTER_VALIDATE_BOOL) || ! $provider->isConfigured())
        ) {
            throw ValidationException::withMessages([
                'ai_enabled' => 'Configure AI_ENABLED=true, AI_API_KEY and AI_MODEL on the server before enabling the agent.',
            ]);
        }

        foreach ([
            'ai_enabled',
            'auto_reply_enabled',
            'auto_order_creation',
            'auto_follow_up',
        ] as $key) {
            AiSetting::put($key, ($values[$key] ?? false) ? 'true' : 'false');
        }
        foreach ([
            'default_language',
            'business_tone',
            'maximum_follow_ups',
            'escalation_threshold',
            'follow_up_delay_minutes',
            'quiet_hours_start',
            'quiet_hours_end',
        ] as $key) {
            AiSetting::put($key, (string) $values[$key]);
        }
        $this->auditAdministratorAction(
            'update_ai_settings',
            'Administrator updated AI-agent settings.',
            ['setting_keys' => array_keys($values)],
        );

        return back()->with('status', 'AI agent settings updated.');
    }

    public function storeKnowledge(Request $request): RedirectResponse
    {
        $knowledge = Validator::make($request->all(), [
            'category' => ['required', 'in:' . implode(',', self::KNOWLEDGE_CATEGORIES)],
            'title' => ['required', 'string', 'max:180'],
            'question' => ['nullable', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'language' => ['required', 'in:sw,en'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:10000'],
        ])->validate();

        $entry = AiKnowledge::query()->create([
            ...$knowledge,
            'is_active' => true,
            'sort_order' => $knowledge['sort_order'] ?? 0,
        ]);
        $this->auditAdministratorAction(
            'create_ai_knowledge',
            'Administrator added approved AI business knowledge.',
            ['knowledge_id' => $entry->id, 'category' => $entry->category],
        );

        return back()->with('status', 'Approved business knowledge added.');
    }

    public function updateKnowledge(Request $request, AiKnowledge $knowledge): RedirectResponse
    {
        $values = Validator::make($request->all(), [
            'category' => ['required', 'in:' . implode(',', self::KNOWLEDGE_CATEGORIES)],
            'title' => ['required', 'string', 'max:180'],
            'question' => ['nullable', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:5000'],
            'language' => ['required', 'in:sw,en'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:10000'],
        ])->validate();

        $knowledge->update($values);
        $this->auditAdministratorAction(
            'update_ai_knowledge',
            'Administrator updated approved AI business knowledge.',
            ['knowledge_id' => $knowledge->id, 'category' => $knowledge->category],
        );

        return back()->with('status', 'Business knowledge updated.');
    }

    public function destroyKnowledge(AiKnowledge $knowledge): RedirectResponse
    {
        $knowledgeId = $knowledge->id;
        $category = $knowledge->category;
        $knowledge->delete();
        $this->auditAdministratorAction(
            'delete_ai_knowledge',
            'Administrator removed approved AI business knowledge.',
            ['knowledge_id' => $knowledgeId, 'category' => $category],
        );

        return back()->with('status', 'Business knowledge removed.');
    }

    /** @param array<string, mixed> $metadata */
    private function auditAdministratorAction(string $tool, string $summary, array $metadata = []): void
    {
        AiAction::query()->create([
            'administrator_id' => auth()->id(),
            'actor' => 'administrator',
            'tool' => $tool,
            'status' => 'completed',
            'summary' => $summary,
            'metadata' => $metadata,
        ]);
    }
}
