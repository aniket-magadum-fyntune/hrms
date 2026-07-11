<?php

namespace App\Console\Commands;

use App\Access\AccessSynchronizer;
use App\Access\AccessSyncResult;
use Illuminate\Console\Command;

class AppSyncAccessCommand extends Command
{
    protected $signature = 'app:sync-access
        {--force : Force the operation to run in production}
        {--dry-run : Show changes without writing them}
        {--role= : Sync only one system role}
        {--sync-role-defaults : Apply code-defined initial permissions to controlled default roles}';

    protected $description = 'Sync code-defined permissions and controlled default roles.';

    public function handle(AccessSynchronizer $access): int
    {
        if ($this->laravel->isProduction() && ! $this->option('force') && ! $this->option('dry-run')) {
            $this->components->warn('This command is running in production.');

            if (! $this->confirm('Do you want to continue?')) {
                $this->components->info('Access sync cancelled.');

                return self::SUCCESS;
            }
        }

        try {
            $result = $access->sync(
                dryRun: (bool) $this->option('dry-run'),
                role: $this->option('role') ?: null,
                syncRoles: (bool) $this->option('sync-role-defaults'),
            );
        } catch (\Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->displayResult($result);

        $this->components->info($this->option('dry-run')
            ? 'Access sync dry run completed.'
            : 'Access sync completed.');

        return self::SUCCESS;
    }

    private function displayResult(AccessSyncResult $result): void
    {
        if (! $result->hasChanges()) {
            $this->line('No access changes detected.');

            return;
        }

        if ($result->createdPermissions !== []) {
            $this->components->twoColumnDetail('Permissions to create', implode(', ', $result->createdPermissions));
        }

        if ($result->createdRoles !== []) {
            $this->components->twoColumnDetail('Roles to create', implode(', ', $result->createdRoles));
        }

        foreach ($result->roleChanges as $role => $changes) {
            if ($changes['added'] !== []) {
                $this->components->twoColumnDetail("{$role} permissions to add", implode(', ', $changes['added']));
            }

            if ($changes['removed'] !== []) {
                $this->components->twoColumnDetail("{$role} permissions to remove", implode(', ', $changes['removed']));
            }
        }
    }
}
