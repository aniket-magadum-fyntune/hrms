<?php

use App\Access\AccessRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('sync creates registry permissions and system roles', function (): void {
    $exitCode = Artisan::call('app:sync-access', ['--force' => true]);

    $this->assertSame(0, $exitCode);

    foreach (AccessRegistry::permissions() as $permission) {
        $this->assertDatabaseHas('permissions', [
            'name' => $permission,
            'guard_name' => 'web',
        ]);
    }

    $this->assertContains('departments.view', AccessRegistry::permissions());
    $this->assertContains('departments.create', AccessRegistry::permissions());
    $this->assertContains('designations.view', AccessRegistry::permissions());
    $this->assertContains('designations.create', AccessRegistry::permissions());
    $this->assertNotContains('permissions.view', AccessRegistry::permissions());
    $this->assertNotContains('permissions.create', AccessRegistry::permissions());
    $this->assertDatabaseMissing('permissions', ['name' => 'permissions.view']);
    $this->assertDatabaseMissing('permissions', ['name' => 'permissions.create']);

    foreach (array_keys(AccessRegistry::roles()) as $role) {
        $this->assertDatabaseHas('roles', [
            'name' => $role,
            'guard_name' => 'web',
        ]);
    }
});

test('sync does not update admin permissions by default', function (): void {
    Permission::query()->create([
        'name' => 'legacy.extra',
        'guard_name' => 'web',
    ]);

    $admin = Role::query()->create([
        'name' => AccessRegistry::ADMIN_ROLE,
        'guard_name' => 'web',
    ]);
    $admin->givePermissionTo('legacy.extra');

    $exitCode = Artisan::call('app:sync-access', ['--force' => true]);

    $this->assertSame(0, $exitCode);
    $this->assertTrue($admin->refresh()->hasPermissionTo('legacy.extra'));
    $this->assertFalse($admin->hasPermissionTo('users.view'));
});

test('sync role defaults explicitly updates admin permissions', function (): void {
    Permission::query()->create([
        'name' => 'legacy.extra',
        'guard_name' => 'web',
    ]);

    $admin = Role::query()->create([
        'name' => AccessRegistry::ADMIN_ROLE,
        'guard_name' => 'web',
    ]);
    $admin->givePermissionTo('legacy.extra');

    $exitCode = Artisan::call('app:sync-access', [
        '--force' => true,
        '--sync-role-defaults' => true,
    ]);

    $this->assertSame(0, $exitCode);
    $this->assertTrue($admin->refresh()->hasAllPermissions(AccessRegistry::permissions()));
    $this->assertFalse($admin->hasPermissionTo('legacy.extra'));
});

test('sync does not alter custom role permissions', function (): void {
    Permission::query()->create([
        'name' => 'legacy.extra',
        'guard_name' => 'web',
    ]);

    $role = Role::query()->create([
        'name' => 'HR Manager',
        'guard_name' => 'web',
    ]);
    $role->givePermissionTo('legacy.extra');

    $exitCode = Artisan::call('app:sync-access', ['--force' => true]);

    $this->assertSame(0, $exitCode);
    $this->assertTrue($role->refresh()->hasPermissionTo('legacy.extra'));
    $this->assertFalse($role->hasPermissionTo('users.view'));
});

test('dry run reports changes without writing data', function (): void {
    $this->artisan('app:sync-access --dry-run')
        ->expectsOutputToContain('users.view')
        ->expectsOutputToContain(AccessRegistry::ADMIN_ROLE)
        ->assertExitCode(0);

    $this->assertSame(0, Permission::query()->count());
    $this->assertSame(0, Role::query()->count());
});

test('default sync creates access records without syncing admin permissions', function (): void {
    $exitCode = Artisan::call('app:sync-access', ['--force' => true]);

    $this->assertSame(0, $exitCode);

    $admin = Role::query()
        ->where('name', AccessRegistry::ADMIN_ROLE)
        ->firstOrFail();

    $this->assertSame(0, $admin->permissions()->count());
});

test('role option syncs only the selected system role', function (): void {
    Role::query()->create([
        'name' => AccessRegistry::SUPER_ADMIN_ROLE,
        'guard_name' => 'web',
    ]);

    $exitCode = Artisan::call('app:sync-access', [
        '--force' => true,
        '--role' => AccessRegistry::ADMIN_ROLE,
    ]);

    $this->assertSame(0, $exitCode);

    $this->assertDatabaseHas('roles', [
        'name' => AccessRegistry::ADMIN_ROLE,
        'guard_name' => 'web',
    ]);
    $this->assertSame(2, Role::query()->count());
});
