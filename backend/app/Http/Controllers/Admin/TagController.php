<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    /**
     * Display a listing of tags.
     */
    public function index(): View
    {
        $tags = Tag::query()
            ->withCount('customers')
            ->orderBy('name')
            ->get();

        return view('admin.tags.index', [
            'tags' => $tags,
        ]);
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:tags,name',
            ],

            'color' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        Tag::create([
            'name' => trim($validated['name']),
            'color' => $validated['color'] ?? null,
        ]);

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Update the specified tag.
     */
    public function update(
        Request $request,
        Tag $tag
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->ignore($tag->id),
            ],

            'color' => [
                'nullable',
                'string',
                'max:20',
            ],
        ]);

        $tag->update([
            'name' => trim($validated['name']),
            'color' => $validated['color'] ?? null,
        ]);

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()
            ->route('admin.tags.index')
            ->with('success', 'Tag deleted successfully.');
    }
}