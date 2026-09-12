<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PipelineController extends Controller
{
    private const STAGES = [
        'new' => 'New',
        'contacted' => 'Contacted',
        'interested' => 'Interested',
        'negotiating' => 'Negotiating',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search', ''));

        $customers = Customer::query()
            ->with([
                'tags',
                'assignedUser',
            ])
            ->withCount('conversations')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->whereLike('name', '%' . $search . '%')
                        ->orWhereLike('phone', '%' . $search . '%')
                        ->orWhereLike('location', '%' . $search . '%');
                });
            })
            ->latest('last_contact_at')
            ->latest('id')
            ->get();

        $pipeline = [];

        foreach (self::STAGES as $stage => $label) {
            $stageCustomers = $customers
                ->where('status', $stage)
                ->values();

            $pipeline[$stage] = [
                'label' => $label,
                'customers' => $stageCustomers,
                'count' => $stageCustomers->count(),
            ];
        }

        return view('admin.pipeline.index', [
            'pipeline' => $pipeline,
            'search' => $search,
            'stages' => self::STAGES,
        ]);
    }

    public function updateStatus(
        Request $request,
        Customer $customer
    ): RedirectResponse {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                'in:new,contacted,interested,negotiating,won,lost',
            ],
        ]);

        $customer->update([
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('admin.pipeline.index')
            ->with(
                'success',
                'Customer moved to ' . self::STAGES[$validated['status']] . '.'
            );
    }
}