<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexPermissionsRequest;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(IndexPermissionsRequest $request): Response
    {
        $permissions = Permission::query()
            ->withCount(['roles', 'users'])
            ->orderBy('name')
            ->get()
            ->map(fn (Permission $permission): array => [
                'id' => $permission->id,
                'name' => $permission->name,
                'roles_count' => $permission->roles_count,
                'users_count' => $permission->users_count,
            ]);

        return Inertia::render('permissions/index', [
            'permissions' => $permissions,
        ]);
    }
}
