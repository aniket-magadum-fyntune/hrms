<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyDepartmentRequest;
use App\Http\Requests\Admin\IndexDepartmentsRequest;
use App\Http\Requests\Admin\StoreDepartmentRequest;
use App\Http\Requests\Admin\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(IndexDepartmentsRequest $request): Response
    {
        $departments = Department::query()
            ->withCount('employees')
            ->orderBy('name')
            ->get()
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'description' => $department->description,
                'users_count' => $department->employees_count,
            ]);

        return Inertia::render('departments/index', [
            'departments' => $departments,
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::query()->create($request->validated());

        return to_route('departments.index');
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return to_route('departments.index');
    }

    public function destroy(DestroyDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->delete();

        return to_route('departments.index');
    }
}
