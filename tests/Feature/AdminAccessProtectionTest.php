<?php

use App\Access\AccessRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('super admin role cannot be updated or deleted from the portal', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
    $superAdminRole = Role::query()->where('name', AccessRegistry::SUPER_ADMIN_ROLE)->firstOrFail();

    $this->actingAs($superAdmin)
        ->put(route('roles.update', $superAdminRole), [
            'name' => AccessRegistry::SUPER_ADMIN_ROLE,
            'permissions' => [],
        ])
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->delete(route('roles.destroy', $superAdminRole))
        ->assertForbidden();

    $this->assertDatabaseHas('roles', [
        'id' => $superAdminRole->id,
        'name' => AccessRegistry::SUPER_ADMIN_ROLE,
    ]);
});

test('admin role permissions can only be changed by super admin', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
    $adminRole = Role::query()->where('name', AccessRegistry::ADMIN_ROLE)->firstOrFail();

    $this->actingAs($admin)
        ->put(route('roles.update', $adminRole), [
            'name' => AccessRegistry::ADMIN_ROLE,
            'permissions' => ['users.view'],
        ])
        ->assertForbidden();

    $this->actingAs($superAdmin)
        ->put(route('roles.update', $adminRole), [
            'name' => AccessRegistry::ADMIN_ROLE,
            'permissions' => ['users.view'],
        ])
        ->assertRedirect(route('roles.index'));

    $this->assertSame(['users.view'], $adminRole->refresh()->permissions->pluck('name')->all());

    $this->actingAs($superAdmin)
        ->delete(route('roles.destroy', $adminRole))
        ->assertForbidden();
});

test('system permissions cannot be updated or deleted from the portal', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
    $permission = Permission::query()->where('name', 'users.view')->firstOrFail();

    $this->assertFalse(Route::has('permissions.store'));
    $this->assertFalse(Route::has('permissions.update'));
    $this->assertFalse(Route::has('permissions.destroy'));

    $this->assertDatabaseHas('permissions', [
        'id' => $permission->id,
        'name' => 'users.view',
    ]);
});

test('permissions page is only visible to super admin', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($superAdmin)
        ->get(route('permissions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('permissions/index')
            ->where('auth.isSuperAdmin', true)
        );

    $this->actingAs($admin)
        ->get(route('permissions.index'))
        ->assertForbidden();
});

test('role management still lists code defined permissions for assignment', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('roles/index')
            ->has('permissions')
            ->whereContains('permissions', 'users.view')
        );
});

test('admin does not see super admin role in role management', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('roles/index')
            ->where('roles.0.name', AccessRegistry::ADMIN_ROLE)
        );
});
