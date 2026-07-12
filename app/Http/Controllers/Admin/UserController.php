<?php

namespace App\Http\Controllers\Admin;

use App\Access\AccessRegistry;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DestroyUserRequest;
use App\Http\Requests\Admin\IndexUsersRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(IndexUsersRequest $request): Response
    {
        $isSuperAdmin = Auth::user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;

        $users = User::query()
            ->with(['department:id,name', 'designation:id,name', 'roles:id,name'])
            ->when(! $isSuperAdmin, fn ($query) => $query->whereDoesntHave('roles', fn ($roles) => $roles->where('name', AccessRegistry::SUPER_ADMIN_ROLE)))
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department_id' => $user->department_id,
                'department' => $user->department?->name,
                'designation_id' => $user->designation_id,
                'designation' => $user->designation?->name,
                'roles' => $user->roles->pluck('name')->values(),
                'created_at' => $user->created_at?->toFormattedDateString(),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => Role::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('name', '!=', AccessRegistry::SUPER_ADMIN_ROLE))
                ->orderBy('name')
                ->pluck('name'),
            'departments' => Department::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'designations' => Designation::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'currentUserId' => Auth::id(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->authorizeRoleAssignment($request, $validated['roles'] ?? []);
        $this->ensureDesignationHasCapacity($validated['designation_id'] ?? null);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'department_id' => $validated['department_id'] ?? null,
            'designation_id' => $validated['designation_id'] ?? null,
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return to_route('users.index');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) && ! ($request->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false), 403, 'Super Admin users are protected.');

        $validated = $request->validated();

        $this->authorizeRoleAssignment($request, $validated['roles'] ?? []);
        $this->ensureDesignationHasCapacity($validated['designation_id'] ?? null, $user);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'department_id' => $validated['department_id'] ?? null,
            'designation_id' => $validated['designation_id'] ?? null,
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles($validated['roles'] ?? []);

        return to_route('users.index');
    }

    public function destroy(DestroyUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->is(Auth::user()), 422, 'You cannot delete your own account.');
        abort_if($user->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) && ! (Auth::user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false), 403, 'Super Admin users are protected.');

        $user->delete();

        return to_route('users.index');
    }

    /**
     * @param  list<string>  $roles
     */
    private function authorizeRoleAssignment(Request $request, array $roles): void
    {
        abort_if(
            in_array(AccessRegistry::SUPER_ADMIN_ROLE, $roles, true)
            && ! ($request->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false),
            403,
            'Only Super Admin can assign the Super Admin role.',
        );
    }

    private function ensureDesignationHasCapacity(?int $designationId, ?User $user = null): void
    {
        if ($designationId === null || $user?->designation_id === $designationId) {
            return;
        }

        $designation = Designation::query()
            ->withCount('users')
            ->find($designationId);

        if ($designation?->max_users === null) {
            return;
        }

        if ($designation->users_count >= $designation->max_users) {
            throw ValidationException::withMessages([
                'designation_id' => "The {$designation->name} designation has reached its assignment limit.",
            ]);
        }
    }
}
