<?php

namespace Tests\Feature;

use App\Access\AccessRegistry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_assigns_roles_but_not_direct_permissions(): void
    {
        Artisan::call('app:setup', ['--force' => true]);

        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $role = Role::query()->create([
            'name' => 'HR Manager',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo('users.view');

        $this->actingAs($superAdmin)
            ->post(route('users.store'), [
                'name' => 'New User',
                'email' => 'new@example.com',
                'password' => 'password',
                'roles' => ['HR Manager'],
                'permissions' => ['users.delete'],
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('email', 'new@example.com')->firstOrFail();

        $this->assertTrue($user->hasRole('HR Manager'));
        $this->assertSame(0, $user->permissions()->count());
        $this->assertTrue($user->hasPermissionTo('users.view'));
        $this->assertFalse($user->hasDirectPermission('users.delete'));
    }

    public function test_user_update_assigns_roles_but_not_direct_permissions(): void
    {
        Artisan::call('app:setup', ['--force' => true]);

        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $user = User::factory()->create();

        $this->actingAs($superAdmin)
            ->put(route('users.update', $user), [
                'name' => $user->name,
                'email' => $user->email,
                'roles' => [AccessRegistry::ADMIN_ROLE],
                'permissions' => ['users.delete'],
            ])
            ->assertRedirect(route('users.index'));

        $user->refresh();

        $this->assertTrue($user->hasRole(AccessRegistry::ADMIN_ROLE));
        $this->assertSame(0, $user->permissions()->count());
        $this->assertFalse($user->hasDirectPermission('users.delete'));
    }

    public function test_admin_cannot_see_or_assign_super_admin_access(): void
    {
        Artisan::call('app:setup', ['--force' => true]);

        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();
        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('users/index')
                ->where('users.0.email', 'admin@example.com')
                ->where('roles.0', AccessRegistry::ADMIN_ROLE)
            );

        $this->actingAs($admin)
            ->put(route('users.update', $superAdmin), [
                'name' => $superAdmin->name,
                'email' => $superAdmin->email,
                'roles' => [AccessRegistry::SUPER_ADMIN_ROLE],
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Bad Actor',
                'email' => 'bad@example.com',
                'password' => 'password',
                'roles' => [AccessRegistry::SUPER_ADMIN_ROLE],
            ])
            ->assertForbidden();
    }
}
