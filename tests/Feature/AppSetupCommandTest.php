<?php

namespace Tests\Feature;

use App\Access\AccessRegistry;
use App\Models\User;
use App\Notifications\SetupPasswordNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppSetupCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_setup_creates_default_access_records(): void
    {
        $this->artisan('app:setup --force')
            ->expectsOutputToContain('super@example.com')
            ->expectsOutputToContain('admin@example.com')
            ->expectsOutputToContain('Password')
            ->assertExitCode(0);

        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $superAdmin->password));
        $this->assertTrue(Hash::check('password', $admin->password));
        $this->assertTrue($superAdmin->hasRole(AccessRegistry::SUPER_ADMIN_ROLE));
        $this->assertTrue($admin->hasRole(AccessRegistry::ADMIN_ROLE));
        $this->assertTrue($admin->hasAllPermissions(AccessRegistry::permissions()));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('anything.on.the.portal'));

        foreach (AccessRegistry::permissions() as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->assertDatabaseHas('settings', [
            'group' => 'application',
            'name' => 'installed_at',
            'locked' => true,
        ]);

    }

    public function test_setup_uses_custom_options(): void
    {
        $exitCode = Artisan::call('app:setup', [
            '--force' => true,
            '--super-admin-name' => 'Portal Owner',
            '--super-admin-email' => 'owner@example.com',
            '--super-admin-password' => 'owner-secret',
            '--admin-name' => 'Portal Admin',
            '--admin-email' => 'portal-admin@example.com',
            '--admin-password' => 'admin-secret',
        ]);

        $this->assertSame(0, $exitCode);

        $superAdmin = User::query()->where('email', 'owner@example.com')->firstOrFail();
        $admin = User::query()->where('email', 'portal-admin@example.com')->firstOrFail();

        $this->assertSame('Portal Owner', $superAdmin->name);
        $this->assertSame('Portal Admin', $admin->name);
        $this->assertTrue(Hash::check('owner-secret', $superAdmin->password));
        $this->assertTrue(Hash::check('admin-secret', $admin->password));
    }

    public function test_generate_passwords_ignores_default_passwords(): void
    {
        $this->artisan('app:setup --force --generate-passwords')
            ->expectsOutputToContain('super@example.com')
            ->expectsOutputToContain('admin@example.com')
            ->assertExitCode(0);

        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        $this->assertFalse(Hash::check('password', $superAdmin->password));
        $this->assertFalse(Hash::check('password', $admin->password));
    }

    public function test_generate_passwords_ignores_short_password_options(): void
    {
        $exitCode = Artisan::call('app:setup', [
            '--force' => true,
            '--generate-passwords' => true,
            '--super-admin-password' => 'short',
            '--admin-password' => 'short',
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseHas('users', ['email' => 'super@example.com']);
        $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
    }

    public function test_mail_passwords_sends_notifications_instead_of_printing_plaintext_passwords(): void
    {
        Notification::fake();

        $exitCode = Artisan::call('app:setup', [
            '--force' => true,
            '--super-admin-password' => 'owner-secret',
            '--admin-password' => 'admin-secret',
            '--mail-passwords' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

        Notification::assertSentTo($superAdmin, SetupPasswordNotification::class);
        Notification::assertSentTo($admin, SetupPasswordNotification::class);

        $this->assertStringNotContainsString('owner-secret', Artisan::output());
        $this->assertStringNotContainsString('admin-secret', Artisan::output());
    }

    public function test_setup_fails_when_it_has_already_run(): void
    {
        $this->assertSame(0, Artisan::call('app:setup', ['--force' => true]));

        $exitCode = Artisan::call('app:setup', ['--force' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertSame(2, User::query()->count());
    }

    public function test_setup_fails_when_emails_match(): void
    {
        $exitCode = Artisan::call('app:setup', [
            '--force' => true,
            '--super-admin-email' => 'same@example.com',
            '--admin-email' => 'same@example.com',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'same@example.com']);
    }

    public function test_setup_fails_when_setup_user_email_already_exists(): void
    {
        User::factory()->create(['email' => 'super@example.com']);

        $exitCode = Artisan::call('app:setup', ['--force' => true]);

        $this->assertSame(1, $exitCode);
        $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
    }

    public function test_database_seeder_does_not_create_system_access_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(0, User::query()->count());
        $this->assertSame(0, Role::query()->count());
        $this->assertSame(0, Permission::query()->count());
    }
}
