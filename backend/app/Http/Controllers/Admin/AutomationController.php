<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Automation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger' => ['required', 'string', 'max:50'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'trigger' => ['required', 'string', 'max:50'],
            'conditions' => ['nullable', 'array'],
            'actions' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

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
}