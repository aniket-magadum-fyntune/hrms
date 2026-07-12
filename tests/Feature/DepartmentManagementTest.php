<?php

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('authorized user can manage departments', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('departments.store'), [
            'name' => 'Research',
            'description' => 'Research and development team',
        ])
        ->assertRedirect(route('departments.index'));

    $department = Department::query()->where('name', 'Research')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('departments.update', $department), [
            'name' => 'Technology',
            'description' => 'Technology team',
        ])
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseHas('departments', [
        'id' => $department->id,
        'name' => 'Technology',
        'description' => 'Technology team',
    ]);

    $this->actingAs($admin)
        ->delete(route('departments.destroy', $department))
        ->assertRedirect(route('departments.index'));

    $this->assertDatabaseMissing('departments', [
        'id' => $department->id,
    ]);
});

test('duplicate department names are rejected', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('departments.store'), [
            'name' => 'Engineering',
        ])
        ->assertSessionHasErrors('name');
});

test('department index includes user counts', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'A Quality Assurance']);
    User::factory()->count(2)->create(['department_id' => $department->id]);

    $this->actingAs($admin)
        ->get(route('departments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('departments/index')
            ->where('departments.0.name', 'A Quality Assurance')
            ->where('departments.0.users_count', 2)
        );
});

test('deleting department clears user assignment', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $department = Department::factory()->create(['name' => 'Quality Assurance']);
    $user = User::factory()->create(['department_id' => $department->id]);

    $this->actingAs($admin)
        ->delete(route('departments.destroy', $department))
        ->assertRedirect(route('departments.index'));

    $this->assertNull($user->refresh()->department_id);
});
