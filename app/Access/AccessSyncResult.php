<?php

namespace App\Access;

final class AccessSyncResult
{
    /**
     * @param  list<string>  $createdPermissions
     * @param  list<string>  $createdRoles
     * @param  array<string, array{added: list<string>, removed: list<string>}>  $roleChanges
     */
    public function __construct(
        public readonly array $createdPermissions,
        public readonly array $createdRoles,
        public readonly array $roleChanges,
    ) {}

    public function hasChanges(): bool
    {
        if ($this->createdPermissions !== [] || $this->createdRoles !== []) {
            return true;
        }

        foreach ($this->roleChanges as $changes) {
            if ($changes['added'] !== [] || $changes['removed'] !== []) {
                return true;
            }
        }

        return false;
    }
}
