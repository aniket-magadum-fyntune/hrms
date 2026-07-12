<?php

use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('user creation can assign department and designation', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'Research']);
    $designation = Designation::factory()->create(['name' => 'Product Designer']);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'department_id' => $department->id,
            'designation_id' => $designation->id,
            'roles' => [],
        ])
        ->assertRedirect(route('users.index'));

    $user = User::query()->where('email', 'new@example.com')->firstOrFail();

    $this->assertSame($department->id, $user->department_id);
    $this->assertSame($designation->id, $user->designation_id);
});

test('user update can change and clear department and designation', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'Research']);
    $designation = Designation::factory()->create(['name' => 'Product Designer']);
    $user = User::factory()->create([
        'department_id' => $department->id,
        'designation_id' => $designation->id,
    ]);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => $user->name,
            'email' => $user->email,
            'department_id' => null,
            'designation_id' => null,
            'roles' => [],
        ])
        ->assertRedirect(route('users.index'));

    $user->refresh();

    $this->assertNull($user->department_id);
    $this->assertNull($user->designation_id);
});

test('invalid department and designation ids are rejected', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'department_id' => 999,
            'designation_id' => 999,
            'roles' => [],
        ])
        ->assertSessionHasErrors(['department_id', 'designation_id']);
});

test('user cannot be assigned to a designation that reached its limit', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create([
        'name' => 'Founder',
        'max_users' => 1,
    ]);
    User::factory()->create(['designation_id' => $designation->id]);

    $this->actingAs($admin)
        ->post(route('users.store'), [
            'name' => 'New User',
            'email' => 'new@example.com',
            'password' => 'password',
            'designation_id' => $designation->id,
            'roles' => [],
        ])
        ->assertSessionHasErrors('designation_id');
});

test('user can keep their current limited designation during update', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create([
        'name' => 'Founder',
        'max_users' => 1,
    ]);
    $user = User::factory()->create(['designation_id' => $designation->id]);

    $this->actingAs($admin)
        ->put(route('users.update', $user), [
            'name' => 'Updated User',
            'email' => $user->email,
            'designation_id' => $designation->id,
            'roles' => [],
        ])
        ->assertRedirect(route('users.index'));

    $this->assertSame('Updated User', $user->refresh()->name);
});

test('user index includes department and designation options', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'A Quality Assurance']);
    $designation = Designation::factory()->create(['name' => 'A Product Designer']);
    User::factory()->create([
        'name' => 'Profiled User',
        'department_id' => $department->id,
        'designation_id' => $designation->id,
    ]);

    $this->actingAs($admin)
        ->get(route('users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('users/index')
            ->has('departments')
            ->has('designations')
            ->where('departments.0.name', 'A Quality Assurance')
            ->where('designations.0.name', 'A Product Designer')
            ->where('users.1.department', 'A Quality Assurance')
            ->where('users.1.designation', 'A Product Designer')
        );
});
