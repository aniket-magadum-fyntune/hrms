<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyDesignationRequest;
use App\Http\Requests\Admin\IndexDesignationsRequest;
use App\Http\Requests\Admin\StoreDesignationRequest;
use App\Http\Requests\Admin\UpdateDesignationRequest;
use App\Models\Designation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DesignationController extends Controller
{
    public function index(IndexDesignationsRequest $request): Response
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

    public function store(StoreDesignationRequest $request): RedirectResponse
    {
        Designation::query()->create($request->validated());

        return to_route('designations.index');
    }

    public function update(UpdateDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $validated = $request->validated();

        $currentUsersCount = $designation->users()->count();

        if (($validated['max_users'] ?? null) !== null && $validated['max_users'] < $currentUsersCount) {
            return back()->withErrors([
                'max_users' => "This designation already has {$currentUsersCount} assigned users.",
            ]);
        }

        $designation->update($validated);

        return to_route('designations.index');
    }

    public function destroy(DestroyDesignationRequest $request, Designation $designation): RedirectResponse
    {
        $designation->delete();

        return to_route('designations.index');
    }
}
