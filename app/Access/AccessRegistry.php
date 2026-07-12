<?php

namespace App\Access;

final class AccessRegistry
{
    public const SUPER_ADMIN_ROLE = 'Super Admin';

    public const ADMIN_ROLE = 'Admin';

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',
            'designations.view',
            'designations.create',
            'designations.update',
            'designations.delete',
        ];
    }

    /**
     * @return array<string, array{controlled: bool, protected: bool, initial_permissions: list<string>}>
     */
    public static function roles(): array
    {
        return [
            self::SUPER_ADMIN_ROLE => [
                'controlled' => false,
                'protected' => true,
                'initial_permissions' => [],
            ],
            self::ADMIN_ROLE => [
                'controlled' => true,
                'protected' => true,
                'initial_permissions' => self::permissions(),
            ],
        ];
    }

    public static function isSystemRole(string $role): bool
    {
        return array_key_exists($role, self::roles());
    }

    public static function isProtectedRole(string $role): bool
    {
        return self::roles()[$role]['protected'] ?? false;
    }

    public static function isControlledRole(string $role): bool
    {
        return self::roles()[$role]['controlled'] ?? false;
    }

    /**
     * @return list<string>
     */
    public static function initialPermissionsForRole(string $role): array
    {
        return self::roles()[$role]['initial_permissions'] ?? [];
    }

    public static function isSystemPermission(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }
}
