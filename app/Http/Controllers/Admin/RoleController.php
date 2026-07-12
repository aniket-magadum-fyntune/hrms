<?php

namespace App\Http\Controllers\Admin;

use App\Access\AccessRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyRoleRequest;
use App\Http\Requests\Admin\IndexRolesRequest;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(IndexRolesRequest $request): Response
    {
        $isSuperAdmin = request()->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;

        $roles = Role::query()
            ->with(['permissions:id,name'])
            ->withCount('users')
            ->when(! $isSuperAdmin, fn ($query) => $query->where('name', '!=', AccessRegistry::SUPER_ADMIN_ROLE))
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->values(),
                'users_count' => $role->users_count,
                'is_system' => AccessRegistry::isSystemRole($role->name),
                'is_protected' => AccessRegistry::isProtectedRole($role->name),
                'is_controlled' => AccessRegistry::isControlledRole($role->name),
                'can_update' => ! AccessRegistry::isSystemRole($role->name) || ($isSuperAdmin && AccessRegistry::isControlledRole($role->name)),
                'can_delete' => ! AccessRegistry::isProtectedRole($role->name),
            ]);

        return Inertia::render('roles/index', [
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('roles.index');
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $isSuperAdmin = $request->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;

        abort_if($role->name === AccessRegistry::SUPER_ADMIN_ROLE, 403, 'The Super Admin role is protected.');
        abort_if(AccessRegistry::isControlledRole($role->name) && ! $isSuperAdmin, 403, 'Controlled roles can only be updated by Super Admin.');

        $validated = $request->validated();

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('roles.index');
    }

    public function destroy(DestroyRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if(AccessRegistry::isProtectedRole($role->name), 403, 'Protected roles cannot be deleted.');

        $role->delete();

        return to_route('roles.index');
    }
}
