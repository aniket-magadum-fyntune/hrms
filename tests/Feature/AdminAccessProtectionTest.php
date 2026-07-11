<?php

namespace Tests\Feature;

use App\Access\AccessRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_role_cannot_be_updated_or_deleted_from_the_portal(): void
    {
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
    }

    public function test_admin_role_permissions_can_only_be_changed_by_super_admin(): void
    {
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
    }

    public function test_system_permissions_cannot_be_updated_or_deleted_from_the_portal(): void
    {
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
    }

    public function test_permissions_page_is_only_visible_to_super_admin(): void
    {
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
    }

    public function test_role_management_still_lists_code_defined_permissions_for_assignment(): void
    {
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
    }

    public function test_admin_does_not_see_super_admin_role_in_role_management(): void
    {
        Artisan::call('app:setup', ['--force' => true]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('roles.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('roles/index')
                ->where('roles.0.name', AccessRegistry::ADMIN_ROLE)
            );
    }
}
