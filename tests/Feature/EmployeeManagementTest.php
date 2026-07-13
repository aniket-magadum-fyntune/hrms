<?php

use App\Access\AccessRegistry;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('employee creation can assign organization profile fields and login access', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'Research']);
    $designation = Designation::factory()->create(['name' => 'Product Designer']);

    $this->actingAs($admin)
        ->post(route('employees.store'), [
            'employee_code' => 'EMP-1001',
            'name' => 'New Employee',
            'work_email' => 'new.employee@example.com',
            'create_login' => true,
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'employment_status' => 'active',
            'joined_on' => '2026-07-12',
        ])
        ->assertRedirect(route('employees.index'));

    $employee = Employee::query()->where('employee_code', 'EMP-1001')->firstOrFail();
    $user = User::query()->where('email', 'new.employee@example.com')->firstOrFail();

    $this->assertSame('new.employee@example.com', $employee->work_email);
    $this->assertTrue($user->refresh()->userable->is($employee));
    $this->assertSame($department->id, $employee->department_id);
    $this->assertSame($designation->id, $employee->designation_id);
    $this->assertSame('2026-07-12', $employee->joined_on?->toDateString());
    $this->assertTrue($user->hasRole(AccessRegistry::EMPLOYEE_ROLE));
});

test('employee update can change and clear department and designation', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'Research']);
    $designation = Designation::factory()->create(['name' => 'Product Designer']);
    $employee = Employee::factory()->create([
        'employee_code' => 'EMP-1001',
        'department_id' => $department->id,
        'designation_id' => $designation->id,
    ]);

    $this->actingAs($admin)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-1001',
            'name' => 'Updated Employee',
            'work_email' => 'updated.employee@example.com',
            'department_id' => null,
            'designation_id' => null,
            'manager_id' => null,
            'employment_status' => 'inactive',
            'joined_on' => null,
        ])
        ->assertRedirect(route('employees.index'));

    $employee->refresh();

    $this->assertSame('Updated Employee', $employee->name);
    $this->assertSame('updated.employee@example.com', $employee->work_email);
    $this->assertNull($employee->department_id);
    $this->assertNull($employee->designation_id);
    $this->assertSame('inactive', $employee->employment_status);
});

test('invalid employee organization fields are rejected', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('employees.store'), [
            'employee_code' => 'EMP-1001',
            'name' => 'New Employee',
            'department_id' => 999,
            'designation_id' => 999,
            'manager_id' => 999,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors(['department_id', 'designation_id', 'manager_id']);
});

test('employee login creation requires a unique work email', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('employees.store'), [
            'employee_code' => 'EMP-1001',
            'name' => 'New Employee',
            'create_login' => true,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors('work_email');

    $this->actingAs($admin)
        ->post(route('employees.store'), [
            'employee_code' => 'EMP-1001',
            'name' => 'New Employee',
            'work_email' => 'admin@example.com',
            'create_login' => true,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors('work_email');
});

test('employee update can create login access from work email', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $employee = Employee::factory()->create([
        'employee_code' => 'EMP-1001',
        'name' => 'Portal Employee',
        'work_email' => 'portal.employee@example.com',
    ]);

    $this->actingAs($admin)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-1001',
            'name' => 'Portal Employee',
            'work_email' => 'portal.employee@example.com',
            'create_login' => true,
            'employment_status' => 'active',
        ])
        ->assertRedirect(route('employees.index'));

    $user = User::query()->where('email', 'portal.employee@example.com')->firstOrFail();

    $this->assertTrue($user->userable->is($employee));
    $this->assertTrue($user->hasRole(AccessRegistry::EMPLOYEE_ROLE));
});

test('employee update login creation requires a unique work email', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $employee = Employee::factory()->create([
        'employee_code' => 'EMP-1001',
        'work_email' => null,
    ]);

    $this->actingAs($admin)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-1001',
            'name' => $employee->name,
            'create_login' => true,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors('work_email');

    $this->actingAs($admin)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-1001',
            'name' => $employee->name,
            'work_email' => 'admin@example.com',
            'create_login' => true,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors('work_email');
});

test('employee cannot be assigned to a designation that reached its limit', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create([
        'name' => 'Founder',
        'max_users' => 1,
    ]);
    Employee::factory()->create(['designation_id' => $designation->id]);

    $this->actingAs($admin)
        ->post(route('employees.store'), [
            'employee_code' => 'EMP-1001',
            'name' => 'New Employee',
            'designation_id' => $designation->id,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors('designation_id');
});

test('employee can keep their current limited designation during update', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create([
        'name' => 'Founder',
        'max_users' => 1,
    ]);
    $employee = Employee::factory()->create([
        'employee_code' => 'EMP-1001',
        'designation_id' => $designation->id,
    ]);

    $this->actingAs($admin)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-1001',
            'name' => 'Updated Employee',
            'work_email' => 'founder@example.com',
            'designation_id' => $designation->id,
            'employment_status' => 'active',
        ])
        ->assertRedirect(route('employees.index'));

    $this->assertSame('Updated Employee', $employee->refresh()->name);
});

test('employee cannot report to themselves', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $employee = Employee::factory()->create(['employee_code' => 'EMP-1001']);

    $this->actingAs($admin)
        ->put(route('employees.update', $employee), [
            'employee_code' => 'EMP-1001',
            'name' => $employee->name,
            'work_email' => $employee->work_email,
            'manager_id' => $employee->id,
            'employment_status' => 'active',
        ])
        ->assertSessionHasErrors('manager_id');
});

test('employee index includes profile options', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'A Quality Assurance']);
    $designation = Designation::factory()->create(['name' => 'A Product Designer']);
    $employee = Employee::factory()->create([
        'employee_code' => 'EMP-1001',
        'name' => 'Profiled Employee',
        'work_email' => 'profiled@example.com',
        'department_id' => $department->id,
        'designation_id' => $designation->id,
    ]);

    $this->actingAs($admin)
        ->get(route('employees.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('employees/index')
            ->has('departments')
            ->has('designations')
            ->has('managers')
            ->has('statuses')
            ->where('departments.0.name', 'A Quality Assurance')
            ->where('designations.0.name', 'A Product Designer')
            ->where('employees.2.id', $employee->id)
            ->where('employees.2.work_email', 'profiled@example.com')
            ->where('employees.2.department', 'A Quality Assurance')
            ->where('employees.2.designation', 'A Product Designer')
        );
});
