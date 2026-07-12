<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::query()
            ->withCount('users')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'users_count' => $department->users_count,
            ]);

        return Inertia::render('departments/index', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Department::query()->create($validated);

        return to_route('departments.index');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('departments', 'name')->ignore($department->id)],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $department->update($validated);

        return to_route('departments.index');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return to_route('departments.index');
    }
}
