<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AutomationController extends Controller
{
    public function index(): View
    {
        $automations = Automation::query()
            ->latest()
            ->get();

        return view('admin.automations.index', compact('automations'));
    }

    public function create(): View
    {
        return view('admin.automations.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateAutomation($request);

        Automation::create($validated);

        return redirect()
            ->route('admin.automations.index')
            ->with('success', 'Automation created successfully.');
    }

    public function edit(Automation $automation): View
    {
        return view('admin.automations.edit', compact('automation'));
    }

    public function update(
        Request $request,
        Automation $automation
    ): RedirectResponse {
        $validated = $this->validateAutomation($request);

        $automation->update($validated);

        return redirect()
            ->route('admin.automations.index')
            ->with('success', 'Automation updated successfully.');
    }

    public function destroy(Automation $automation): RedirectResponse
    {
        $automation->delete();

        return redirect()
            ->route('admin.automations.index')
            ->with('success', 'Automation deleted successfully.');
    }

    public function toggle(Automation $automation): RedirectResponse
    {
        $automation->update([
            'is_active' => ! $automation->is_active,
        ]);

        return redirect()
            ->route('admin.automations.index')
            ->with('success', 'Automation status updated.');
    }

    /**
     * Validate and normalize automation data.
     *
     * @return array<string, mixed>
     */
    private function validateAutomation(
        Request $request
    ): array {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'trigger' => [
                'required',
                'string',
                Rule::in([
                    'new_customer',
                    'message_received',
                    'keyword',
                    'no_reply',
                ]),
            ],

            'conditions' => [
                'nullable',
                'array',
            ],

            'conditions.0.value' => [
                Rule::requiredIf(
                    fn () => $request->input('trigger') === 'keyword'
                ),
                'nullable',
                'string',
                'max:255',
            ],

            'conditions.0.delay_value' => [
                Rule::requiredIf(
                    fn () => $request->input('trigger') === 'no_reply'
                ),
                'nullable',
                'integer',
                'min:1',
                'max:720',
            ],

            'conditions.0.delay_unit' => [
                Rule::requiredIf(
                    fn () => $request->input('trigger') === 'no_reply'
                ),
                'nullable',
                Rule::in([
                    'minutes',
                    'hours',
                    'days',
                ]),
            ],

            'actions' => [
                'required',
                'array',
                'min:1',
            ],

            'actions.0.type' => [
                'required',
                'string',
                Rule::in([
                    'send_text',
                ]),
            ],

            'actions.0.message' => [
                'required',
                'string',
                'max:4096',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $validated['conditions'] = $this->normalizeConditions(
            $validated['trigger'],
            $validated['conditions'] ?? []
        );

        return $validated;
    }

    /**
     * Keep conditions consistent with the selected trigger.
     *
     * @param array<int|string, mixed> $conditions
     * @return array<int, array<string, mixed>>
     */
    private function normalizeConditions(
        string $trigger,
        array $conditions
    ): array {
        $first = $conditions[0] ?? [];

        if (! is_array($first)) {
            $first = [];
        }

        return match ($trigger) {
            'new_customer' => [],

            'message_received' => $this->normalizeKeywordCondition(
                $first
            ),

            'keyword' => $this->normalizeKeywordCondition(
                $first
            ),

            'no_reply' => [[
                'type' => 'delay',
                'delay_value' => (int) ($first['delay_value'] ?? 0),
                'delay_unit' => (string) (
                    $first['delay_unit'] ?? 'minutes'
                ),
            ]],

            default => [],
        };
    }

    /**
     * Normalize an optional keyword condition.
     *
     * @param array<string, mixed> $condition
     * @return array<int, array<string, string>>
     */
    private function normalizeKeywordCondition(
        array $condition
    ): array {
        $value = trim(
            (string) ($condition['value'] ?? '')
        );

        if ($value === '') {
            return [];
        }

        return [[
            'type' => 'keyword',
            'value' => $value,
        ]];
    }
}