<?php

namespace App\Http\Controllers\Admin;

use App\Access\AccessRegistry;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): Response
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

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::notIn(array_keys(AccessRegistry::roles())), Rule::unique('roles', 'name')->where('guard_name', 'web')],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::query()->create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('roles.index');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $isSuperAdmin = $request->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;

        abort_if($role->name === AccessRegistry::SUPER_ADMIN_ROLE, 403, 'The Super Admin role is protected.');
        abort_if(AccessRegistry::isControlledRole($role->name) && ! $isSuperAdmin, 403, 'Controlled roles can only be updated by Super Admin.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', AccessRegistry::isSystemRole($role->name) ? Rule::in([$role->name]) : Rule::notIn(array_keys(AccessRegistry::roles())), Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role->id)],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role->update(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return to_route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if(AccessRegistry::isProtectedRole($role->name), 403, 'Protected roles cannot be deleted.');

        $role->delete();

        return to_route('roles.index');
    }
}
