<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function destroy(
        Automation $automation
    ): RedirectResponse {
        $automation->delete();

        return redirect()
            ->route('admin.automations.index')
            ->with('success', 'Automation deleted successfully.');
    }

    public function toggle(
        Automation $automation
    ): RedirectResponse {
        $automation->update([
            'is_active' => ! $automation->is_active,
        ]);

        return redirect()
            ->route('admin.automations.index')
            ->with('success', 'Automation status updated.');
    }

    private function validateAutomation(
        Request $request
    ): array {
        $trigger = (string) $request->input('trigger');

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

            'conditions.*.type' => [
                'nullable',
                'string',
                Rule::in([
                    'keyword',
                    'delay',
                ]),
            ],

            'conditions.*.value' => [
                'nullable',
                'string',
                'max:255',
            ],

            'conditions.*.response' => [
                'nullable',
                'string',
                'max:4096',
            ],

            'conditions.*.delay_value' => [
                'nullable',
                'integer',
                'min:1',
                'max:720',
            ],

            'conditions.*.delay_unit' => [
                'nullable',
                Rule::in([
                    'minutes',
                    'hours',
                    'days',
                ]),
            ],

            'actions' => [
                'nullable',
                'array',
            ],

            'actions.*.type' => [
                'required_with:actions',
                'string',
                Rule::in([
                    'send_text',
                ]),
            ],

            'actions.*.message' => [
                'required_with:actions',
                'string',
                'max:4096',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);

        /*
         * KEYWORD AUTOMATION
         *
         * Each keyword carries its own response:
         *
         * conditions:
         * [
         *     [
         *         'type' => 'keyword',
         *         'value' => 'bei',
         *         'response' => 'Bei zetu ni...'
         *     ],
         *     [
         *         'type' => 'keyword',
         *         'value' => 'delivery',
         *         'response' => 'Delivery ni bure...'
         *     ]
         * ]
         */
        if ($trigger === 'keyword') {
            $conditions = $validated['conditions'] ?? [];

            $conditions = array_values(
                array_filter(
                    $conditions,
                    function ($condition): bool {
                        if (! is_array($condition)) {
                            return false;
                        }

                        return ($condition['type'] ?? null) === 'keyword'
                            && trim(
                                (string) ($condition['value'] ?? '')
                            ) !== ''
                            && trim(
                                (string) ($condition['response'] ?? '')
                            ) !== '';
                    }
                )
            );

            if ($conditions === []) {
                abort(
                    422,
                    'At least one keyword with its response is required.'
                );
            }

            $validated['conditions'] =
                $this->normalizeKeywordConditions($conditions);

            /*
             * Keyword automations do not need a generic action.
             * Their response is stored beside each keyword.
             */
            $validated['actions'] = [];
        }

        /*
         * MESSAGE RECEIVED
         */
        elseif ($trigger === 'message_received') {

            $conditions = $validated['conditions'] ?? [];

            $validated['conditions'] =
                $this->normalizeOptionalKeywordConditions(
                    $conditions
                );

            $validated['actions'] =
                $this->normalizeActions(
                    $validated['actions'] ?? []
                );

            if ($validated['actions'] === []) {
                abort(
                    422,
                    'An automatic response message is required.'
                );
            }
        }

        /*
         * NEW CUSTOMER
         */
        elseif ($trigger === 'new_customer') {

            $validated['conditions'] = [];

            $validated['actions'] =
                $this->normalizeActions(
                    $validated['actions'] ?? []
                );

            if ($validated['actions'] === []) {
                abort(
                    422,
                    'An automatic response message is required.'
                );
            }
        }

        /*
         * NO REPLY / FOLLOW-UP
         */
        elseif ($trigger === 'no_reply') {

            $conditions = $validated['conditions'] ?? [];

            $first = $conditions[0] ?? [];

            if (! is_array($first)) {
                $first = [];
            }

            $delayValue = (int) (
                $first['delay_value'] ?? 0
            );

            $delayUnit = (string) (
                $first['delay_unit'] ?? 'hours'
            );

            if ($delayValue < 1) {
                abort(
                    422,
                    'A valid follow-up delay is required.'
                );
            }

            $validated['conditions'] = [[
                'type' => 'delay',
                'delay_value' => $delayValue,
                'delay_unit' => $delayUnit,
            ]];

            $validated['actions'] =
                $this->normalizeActions(
                    $validated['actions'] ?? []
                );

            if ($validated['actions'] === []) {
                abort(
                    422,
                    'An automatic follow-up message is required.'
                );
            }
        }

        $validated['is_active'] = $request->boolean(
            'is_active'
        );

        return $validated;
    }

    private function normalizeKeywordConditions(
        array $conditions
    ): array {
        $normalized = [];

        foreach ($conditions as $condition) {

            if (! is_array($condition)) {
                continue;
            }

            $keyword = trim(
                (string) ($condition['value'] ?? '')
            );

            $response = trim(
                (string) ($condition['response'] ?? '')
            );

            if (
                $keyword === ''
                || $response === ''
            ) {
                continue;
            }

            $normalized[] = [
                'type' => 'keyword',
                'value' => $keyword,
                'response' => $response,
            ];
        }

        return $normalized;
    }

    private function normalizeOptionalKeywordConditions(
        array $conditions
    ): array {
        $normalized = [];

        foreach ($conditions as $condition) {

            if (! is_array($condition)) {
                continue;
            }

            if (($condition['type'] ?? null) !== 'keyword') {
                continue;
            }

            $keyword = trim(
                (string) ($condition['value'] ?? '')
            );

            if ($keyword === '') {
                continue;
            }

            $normalized[] = [
                'type' => 'keyword',
                'value' => $keyword,
            ];
        }

        return $normalized;
    }

    private function normalizeActions(
        array $actions
    ): array {
        $normalized = [];

        foreach ($actions as $action) {

            if (! is_array($action)) {
                continue;
            }

            $type = (string) (
                $action['type'] ?? ''
            );

            $message = trim(
                (string) (
                    $action['message'] ?? ''
                )
            );

            if (
                $type !== 'send_text'
                || $message === ''
            ) {
                continue;
            }

            $normalized[] = [
                'type' => 'send_text',
                'message' => $message,
            ];
        }

        return $normalized;
    }
}