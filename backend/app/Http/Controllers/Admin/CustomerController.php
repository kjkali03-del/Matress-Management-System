<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));

        $customers = Customer::query()
            ->with([
                'tags',
                'assignedUser',
            ])
            ->withCount('conversations')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'ilike', '%' . $search . '%')
                        ->orWhere('phone', 'ilike', '%' . $search . '%')
                        ->orWhere('location', 'ilike', '%' . $search . '%');
                });
            })
            ->latest('last_contact_at')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', [
            'customers' => $customers,
            'search' => $search,
        ]);
    }

    /**
     * Display the specified customer.
     */
    public function show(Customer $customer): View
    {
        $customer->load([
            'tags',
            'assignedUser',
            'conversations' => function ($query): void {
                $query
                    ->with('latestMessage')
                    ->latest('last_message_at');
            },
        ]);

        $tags = Tag::query()
            ->orderBy('name')
            ->get();

        $users = User::query()
            ->orderBy('name')
            ->get();

        return view('admin.customers.show', [
            'customer' => $customer,
            'tags' => $tags,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(
        Request $request,
        Customer $customer
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:50',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],

            'status' => [
                'required',
                'string',
                'in:new,contacted,interested,negotiating,won,lost',
            ],

            'assigned_to' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'tags' => [
                'nullable',
                'array',
            ],

            'tags.*' => [
                'integer',
                'exists:tags,id',
            ],
        ]);

        $customer->update([
            'name' => $validated['name'],
            'phone' => $validated['phone'] ?? null,
            'location' => $validated['location'] ?? null,
            'status' => $validated['status'],
            'assigned_to' => $validated['assigned_to'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $customer->tags()->sync($validated['tags'] ?? []);

        return redirect()
            ->route('admin.customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }
}