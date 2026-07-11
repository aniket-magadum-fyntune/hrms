<?php

namespace Tests\Feature;

use App\Access\AccessRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppSyncAccessCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_creates_registry_permissions_and_system_roles(): void
    {
        $exitCode = Artisan::call('app:sync-access', ['--force' => true]);

        $this->assertSame(0, $exitCode);

        foreach (AccessRegistry::permissions() as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

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
    }

    public function test_sync_does_not_update_admin_permissions_by_default(): void
    {
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
    }

    public function test_sync_role_defaults_explicitly_updates_admin_permissions(): void
    {
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
    }

    public function test_sync_does_not_alter_custom_role_permissions(): void
    {
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
    }

    public function test_dry_run_reports_changes_without_writing_data(): void
    {
        $this->artisan('app:sync-access --dry-run')
            ->expectsOutputToContain('users.view')
            ->expectsOutputToContain(AccessRegistry::ADMIN_ROLE)
            ->assertExitCode(0);

        $this->assertSame(0, Permission::query()->count());
        $this->assertSame(0, Role::query()->count());
    }

    public function test_default_sync_creates_access_records_without_syncing_admin_permissions(): void
    {
        $exitCode = Artisan::call('app:sync-access', ['--force' => true]);

        $this->assertSame(0, $exitCode);

        $admin = Role::query()
            ->where('name', AccessRegistry::ADMIN_ROLE)
            ->firstOrFail();

        $this->assertSame(0, $admin->permissions()->count());
    }

    public function test_role_option_syncs_only_the_selected_system_role(): void
    {
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
    }
}
