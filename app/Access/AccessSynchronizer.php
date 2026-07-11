<?php

namespace App\Access;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessSynchronizer
{
    public function sync(bool $dryRun = false, ?string $role = null, bool $syncRoles = false): AccessSyncResult
    {
        $this->ensureRoleCanBeSynced($role);

        $createdPermissions = $this->missingPermissions();
        $createdRoles = $this->missingRoles($role);
        $roleChanges = $syncRoles ? $this->roleChanges($role) : [];

        if (! $dryRun) {
            DB::transaction(function () use ($createdPermissions, $createdRoles, $roleChanges): void {
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                foreach ($createdPermissions as $permission) {
                    Permission::query()->create([
                        'name' => $permission,
                        'guard_name' => 'web',
                    ]);
                }

                foreach ($createdRoles as $role) {
                    Role::query()->create([
                        'name' => $role,
                        'guard_name' => 'web',
                    ]);
                }

                foreach (array_keys($roleChanges) as $role) {
                    Role::query()
                        ->where('name', $role)
                        ->where('guard_name', 'web')
                        ->firstOrFail()
                        ->syncPermissions(AccessRegistry::initialPermissionsForRole($role));
                }
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        return new AccessSyncResult($createdPermissions, $createdRoles, $roleChanges);
    }

    private function ensureRoleCanBeSynced(?string $role): void
    {
        if ($role === null) {
            return;
        }

        throw_unless(
            AccessRegistry::isSystemRole($role),
            "Role [{$role}] is not a system role.",
        );
    }

    /**
     * @return list<string>
     */
    private function missingPermissions(): array
    {
        $existing = Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', AccessRegistry::permissions())
            ->pluck('name')
            ->all();

        return array_values(array_diff(AccessRegistry::permissions(), $existing));
    }

    /**
     * @return list<string>
     */
    private function missingRoles(?string $role): array
    {
        $registryRoles = $role === null ? array_keys(AccessRegistry::roles()) : [$role];

        $existing = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', $registryRoles)
            ->pluck('name')
            ->all();

        return array_values(array_diff($registryRoles, $existing));
    }

    /**
     * @return array<string, array{added: list<string>, removed: list<string>}>
     */
    private function roleChanges(?string $role): array
    {
        $registryRoles = AccessRegistry::roles();
        $roles = $role === null ? array_keys($registryRoles) : [$role];
        $changes = [];

        foreach ($roles as $roleName) {
            if (! ($registryRoles[$roleName]['controlled'] ?? false)) {
                continue;
            }

            $current = Role::query()
                ->with('permissions:id,name')
                ->where('name', $roleName)
                ->where('guard_name', 'web')
                ->first()
                ?->permissions
                ->pluck('name')
                ->all() ?? [];

            $target = $registryRoles[$roleName]['initial_permissions'];
            $added = array_values(array_diff($target, $current));
            $removed = array_values(array_diff($current, $target));

            if ($added !== [] || $removed !== []) {
                $changes[$roleName] = [
                    'added' => $added,
                    'removed' => $removed,
                ];
            }
        }

        return $changes;
    }
}
