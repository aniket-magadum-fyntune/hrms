<?php

namespace App\Http\Controllers\Admin;

use App\Access\AccessRegistry;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(): Response
    {
        $isSuperAdmin = Auth::user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false;

        $users = User::query()
            ->with(['roles:id,name'])
            ->when(! $isSuperAdmin, fn ($query) => $query->whereDoesntHave('roles', fn ($roles) => $roles->where('name', AccessRegistry::SUPER_ADMIN_ROLE)))
            ->orderBy('name')
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->values(),
                'created_at' => $user->created_at?->toFormattedDateString(),
            ]);

        return Inertia::render('users/index', [
            'users' => $users,
            'roles' => Role::query()
                ->when(! $isSuperAdmin, fn ($query) => $query->where('name', '!=', AccessRegistry::SUPER_ADMIN_ROLE))
                ->orderBy('name')
                ->pluck('name'),
            'currentUserId' => Auth::id(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $this->authorizeRoleAssignment($request, $validated['roles'] ?? []);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles($validated['roles'] ?? []);

        return to_route('users.index');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($user->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) && ! ($request->user()?->hasRole(AccessRegistry::SUPER_ADMIN_ROLE) ?? false), 403, 'Super Admin users are protected.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $this->authorizeRoleAssignment($request, $validated['roles'] ?? []);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();
        $user->syncRoles($validated['roles'] ?? []);

        return to_route('users.index');
    }

    public function destroy(User $user): RedirectResponse
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
}
