<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function index(): Response
    {
        $designations = Designation::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Designation $designation): array => [
                'id' => $designation->id,
                'name' => $designation->name,
                'description' => $designation->description,
                'max_users' => $designation->max_users,
                'users_count' => $designation->users_count,
            ]);

        return Inertia::render('designations/index', [
            'designations' => $designations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_users' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        Designation::query()->create($validated);

        return to_route('designations.index');
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('designations', 'name')->ignore($designation->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'max_users' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ]);

        $currentUsersCount = $designation->users()->count();

        if (($validated['max_users'] ?? null) !== null && $validated['max_users'] < $currentUsersCount) {
            return back()->withErrors([
                'max_users' => "This designation already has {$currentUsersCount} assigned users.",
            ]);
        }

        $designation->update($validated);

        return to_route('designations.index');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $designation->delete();

        return to_route('designations.index');
    }
}
