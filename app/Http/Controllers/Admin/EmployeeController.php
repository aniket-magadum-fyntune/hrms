<?php

namespace App\Http\Controllers\Admin;

use App\Access\AccessRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyEmployeeRequest;
use App\Http\Requests\Admin\IndexEmployeesRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(IndexEmployeesRequest $request): Response
    {
        $employees = Employee::query()
            ->with([
                'user:id,name,email',
                'department:id,name',
                'designation:id,name',
                'manager:id,name,employee_code',
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Employee $employee): array => [
                'id' => $employee->id,
                'employee_code' => $employee->employee_code,
                'name' => $employee->name,
                'work_email' => $employee->work_email,
                'user_id' => $employee->user?->id,
                'user_name' => $employee->user?->name,
                'user_email' => $employee->user?->email,
                'department_id' => $employee->department_id,
                'department' => $employee->department?->name,
                'designation_id' => $employee->designation_id,
                'designation' => $employee->designation?->name,
                'manager_id' => $employee->manager_id,
                'manager' => $employee->manager ? "{$employee->manager->employee_code} - {$employee->manager->name}" : null,
                'employment_status' => $employee->employment_status,
                'joined_on' => $employee->joined_on?->toDateString(),
            ]);

        return Inertia::render('employees/index', [
            'employees' => $employees,
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'designations' => Designation::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'managers' => Employee::query()
                ->orderBy('name')
                ->get(['id', 'name', 'employee_code'])
                ->map(fn (Employee $employee): array => [
                    'id' => $employee->id,
                    'name' => "{$employee->employee_code} - {$employee->name}",
                ]),
            'statuses' => Employee::statuses(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            /** @var Employee $employee */
            $employee = Employee::query()->create($request->safe()->except('create_login'));

            $this->createLoginAccess($employee, $request->boolean('create_login'));
        });

        return to_route('employees.index');
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($request, $employee): void {
            $employee->update($request->safe()->except('create_login'));

            $this->createLoginAccess($employee, $request->boolean('create_login'));
        });

        return to_route('employees.index');
    }

    public function destroy(DestroyEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->delete();

        return to_route('employees.index');
    }

    private function createLoginAccess(Employee $employee, bool $shouldCreateLogin): void
    {
        if (! $shouldCreateLogin || $employee->user !== null) {
            return;
        }

        $user = User::query()->create([
            'name' => $employee->name,
            'email' => $employee->work_email,
            'password' => Str::password(24),
        ]);

        $user->assignRole(AccessRegistry::EMPLOYEE_ROLE);
        $user->userable()->associate($employee);
        $user->save();
    }
}
