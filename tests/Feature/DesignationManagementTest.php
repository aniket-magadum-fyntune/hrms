<?php

use App\Models\Designation;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('authorized user can manage designations', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('designations.store'), [
            'name' => 'Product Designer',
            'description' => 'Designs product experiences',
            'max_users' => 2,
        ])
        ->assertRedirect(route('designations.index'));

    $designation = Designation::query()->where('name', 'Product Designer')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('designations.update', $designation), [
            'name' => 'Senior Software Engineer',
            'description' => 'Builds and reviews product features',
            'max_users' => null,
        ])
        ->assertRedirect(route('designations.index'));

    $this->assertDatabaseHas('designations', [
        'id' => $designation->id,
        'name' => 'Senior Software Engineer',
        'description' => 'Builds and reviews product features',
        'max_users' => null,
    ]);

    $this->actingAs($admin)
        ->delete(route('designations.destroy', $designation))
        ->assertRedirect(route('designations.index'));

    $this->assertDatabaseMissing('designations', [
        'id' => $designation->id,
    ]);
});

test('designation max users cannot be lower than assigned users', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create([
        'name' => 'Product Designer',
        'max_users' => null,
    ]);
    Employee::factory()->count(2)->create(['designation_id' => $designation->id]);

    $this->actingAs($admin)
        ->put(route('designations.update', $designation), [
            'name' => 'Product Designer',
            'description' => null,
            'max_users' => 1,
        ])
        ->assertSessionHasErrors('max_users');
});

test('duplicate designation names are rejected', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->post(route('designations.store'), [
            'name' => 'Software Engineer',
        ])
        ->assertSessionHasErrors('name');
});

test('designation index includes user counts', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create(['name' => 'A Product Designer']);
    Employee::factory()->count(2)->create(['designation_id' => $designation->id]);

    $this->actingAs($admin)
        ->get(route('designations.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('designations/index')
            ->where('designations.0.name', 'A Product Designer')
            ->where('designations.0.users_count', 2)
        );
});

test('deleting designation clears user assignment', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $designation = Designation::factory()->create(['name' => 'Product Designer']);
    $employee = Employee::factory()->create(['designation_id' => $designation->id]);

    $this->actingAs($admin)
        ->delete(route('designations.destroy', $designation))
        ->assertRedirect(route('designations.index'));

    $this->assertNull($employee->refresh()->designation_id);
});
