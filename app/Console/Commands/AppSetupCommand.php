<?php

namespace App\Console\Commands;

use App\Access\AccessRegistry;
use App\Access\AccessSynchronizer;
use App\Models\User;
use App\Notifications\SetupPasswordNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppSetupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:setup
        {--force : Force the operation to run in production}
        {--super-admin-name=Super Admin : Name for the Super Admin user}
        {--super-admin-email=super@example.com : Email for the Super Admin user}
        {--super-admin-password=password : Password for the Super Admin user}
        {--admin-name=Admin : Name for the Admin user}
        {--admin-email=admin@example.com : Email for the Admin user}
        {--admin-password=password : Password for the Admin user}
        {--generate-passwords : Generate random passwords instead of using password options}
        {--mail-passwords : Email the final passwords instead of printing them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prepare the application database and required bootstrap data.';

    private const SETTINGS_GROUP = 'application';

    private const INSTALLED_AT_SETTING = 'installed_at';

    /**
     * Execute the console command.
     */
    public function handle(AccessSynchronizer $access): int
    {
        try {
            $input = $this->validatedInput();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->components->error($message);
                }
            }

            return self::FAILURE;
        }

        if ($this->laravel->isProduction() && ! $this->option('force')) {
            $this->components->warn('This command is running in production.');

            if (! $this->confirm('Do you want to continue?')) {
                $this->components->info('Setup cancelled.');

                return self::SUCCESS;
            }
        }

        if (! $this->requiredTablesExist()) {
            $this->components->error('Required tables are missing. Run php artisan migrate before php artisan app:setup.');

            return self::FAILURE;
        }

        if ($this->applicationIsInstalled()) {
            $this->components->error('Application setup has already been completed.');

            return self::FAILURE;
        }

        if (! $this->ensureSetupCanRun($input['super_admin_email'], $input['admin_email'])) {
            return self::FAILURE;
        }

        $passwords = [
            'super_admin' => $this->option('generate-passwords') ? Str::password(24) : $input['super_admin_password'],
            'admin' => $this->option('generate-passwords') ? Str::password(24) : $input['admin_password'],
        ];

        $access->sync(syncRoles: true);
        $users = $this->provisionUsers($input, $passwords);

        if ($this->option('mail-passwords')) {
            if (! $this->mailPasswords($users, $passwords)) {
                return self::FAILURE;
            }

            $this->components->info('Generated passwords were emailed to the setup users.');
        } else {
            $this->displayCredentials($users, $passwords);
        }

        $this->components->info('Application setup completed.');

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     super_admin_name: string,
     *     super_admin_email: string,
     *     super_admin_password: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string
     * }
     *
     * @throws ValidationException
     */
    private function validatedInput(): array
    {
        $passwordRules = $this->option('generate-passwords')
            ? ['nullable', 'string']
            : ['required', 'string', 'min:8'];

        $validator = Validator::make([
            'super_admin_name' => $this->option('super-admin-name'),
            'super_admin_email' => $this->option('super-admin-email'),
            'super_admin_password' => $this->option('super-admin-password'),
            'admin_name' => $this->option('admin-name'),
            'admin_email' => $this->option('admin-email'),
            'admin_password' => $this->option('admin-password'),
        ], [
            'super_admin_name' => ['required', 'string', 'max:255'],
            'super_admin_email' => ['required', 'email', 'max:255'],
            'super_admin_password' => $passwordRules,
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
            'admin_password' => $passwordRules,
        ]);

        $validated = $validator->validate();

        /** @var array{
         *     super_admin_name: string,
         *     super_admin_email: string,
         *     super_admin_password: string,
         *     admin_name: string,
         *     admin_email: string,
         *     admin_password: string
         * } $validated
         */
        if (Str::lower($validated['super_admin_email']) === Str::lower($validated['admin_email'])) {
            throw ValidationException::withMessages([
                'admin_email' => 'The Admin email must be different from the Super Admin email.',
            ]);
        }

        return $validated;
    }

    private function applicationIsInstalled(): bool
    {
        return DB::table('settings')
            ->where('group', self::SETTINGS_GROUP)
            ->where('name', self::INSTALLED_AT_SETTING)
            ->exists();
    }

    private function requiredTablesExist(): bool
    {
        return Schema::hasTable('settings')
            && Schema::hasTable('users')
            && Schema::hasTable(config('permission.table_names.permissions'))
            && Schema::hasTable(config('permission.table_names.roles'));
    }

    private function ensureSetupCanRun(string $superAdminEmail, string $adminEmail): bool
    {
        $existingEmails = User::query()
            ->whereIn('email', [$superAdminEmail, $adminEmail])
            ->pluck('email')
            ->all();

        if ($existingEmails !== []) {
            $this->components->error('Setup users already exist: '.implode(', ', $existingEmails));

            return false;
        }

        return true;
    }

    /**
     * @param  array{
     *     super_admin_name: string,
     *     super_admin_email: string,
     *     admin_name: string,
     *     admin_email: string
     * }  $input
     * @param  array{super_admin: string, admin: string}  $passwords
     * @return array{super_admin: User, admin: User}
     */
    private function provisionUsers(array $input, array $passwords): array
    {
        return DB::transaction(function () use ($input, $passwords): array {
            /** @var User $superAdmin */
            $superAdmin = User::query()->create([
                'name' => $input['super_admin_name'],
                'email' => $input['super_admin_email'],
                'password' => $passwords['super_admin'],
                'email_verified_at' => Carbon::now(),
            ]);

            /** @var User $admin */
            $admin = User::query()->create([
                'name' => $input['admin_name'],
                'email' => $input['admin_email'],
                'password' => $passwords['admin'],
                'email_verified_at' => Carbon::now(),
            ]);

            $superAdmin->assignRole(AccessRegistry::SUPER_ADMIN_ROLE);
            $admin->assignRole(AccessRegistry::ADMIN_ROLE);

            DB::table('settings')->insert([
                'group' => self::SETTINGS_GROUP,
                'name' => self::INSTALLED_AT_SETTING,
                'locked' => true,
                'payload' => json_encode(['installed_at' => Carbon::now()->toIso8601String()]),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            return [
                'super_admin' => $superAdmin,
                'admin' => $admin,
            ];
        });
    }

    /**
     * @param  array{super_admin: User, admin: User}  $users
     * @param  array{super_admin: string, admin: string}  $passwords
     */
    private function mailPasswords(array $users, array $passwords): bool
    {
        try {
            Notification::send($users['super_admin'], new SetupPasswordNotification($passwords['super_admin']));
            Notification::send($users['admin'], new SetupPasswordNotification($passwords['admin']));
        } catch (\Throwable $exception) {
            $this->components->error('Application setup completed, but credential delivery failed: '.$exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param  array{super_admin: User, admin: User}  $users
     * @param  array{super_admin: string, admin: string}  $passwords
     */
    private function displayCredentials(array $users, array $passwords): void
    {
        $this->table(['Account', 'Email', 'Password'], [
            ['Super Admin', $users['super_admin']->email, $passwords['super_admin']],
            ['Admin', $users['admin']->email, $passwords['admin']],
        ]);
    }
}
