<?php

use App\Access\AccessRegistry;
use App\Models\Department;
use App\Models\Designation;
use App\Models\User;
use App\Notifications\SetupPasswordNotification;
use App\Support\OrganizationSettings;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('setup creates default access records', function (): void {
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

});

test('setup creates default organization masters', function (): void {
    $this->artisan('app:setup --force')
        ->assertExitCode(0);

    $this->assertSame(11, Department::query()->count());
    $this->assertSame(15, Designation::query()->count());
    $this->assertSame(1, Designation::query()->where('name', 'Chief Executive Officer')->value('max_users'));
    $this->assertNull(Designation::query()->where('name', 'Software Engineer')->value('max_users'));

    foreach ([
        'Administration',
        'Human Resources',
        'Finance',
        'Operations',
        'Sales',
        'Marketing',
        'Customer Support',
        'Engineering',
        'Information Technology',
        'Legal',
        'Procurement',
    ] as $department) {
        $this->assertDatabaseHas('departments', [
            'name' => $department,
        ]);
    }

    foreach ([
        'Chief Executive Officer',
        'General Manager',
        'Department Manager',
        'Team Lead',
        'Human Resources Manager',
        'Human Resources Executive',
        'Finance Manager',
        'Accountant',
        'Operations Manager',
        'Sales Manager',
        'Sales Executive',
        'Marketing Manager',
        'Software Engineer',
        'System Administrator',
        'Customer Support Executive',
    ] as $designation) {
        $this->assertDatabaseHas('designations', [
            'name' => $designation,
        ]);
    }
});

test('setup creates default organization settings', function (): void {
    $this->artisan('app:setup --force')
        ->assertExitCode(0);

    $settings = OrganizationSettings::all();

    $this->assertSame(config('app.name'), $settings['name']);
    $this->assertSame('#111827', $settings['primary_color']);
    $this->assertSame('#111827', $settings['sidebar_color']);
});

test('setup uses custom options', function (): void {
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
});

test('generate passwords ignores default passwords', function (): void {
    $this->artisan('app:setup --force --generate-passwords')
        ->expectsOutputToContain('super@example.com')
        ->expectsOutputToContain('admin@example.com')
        ->assertExitCode(0);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->assertFalse(Hash::check('password', $superAdmin->password));
    $this->assertFalse(Hash::check('password', $admin->password));
});

test('generate passwords ignores short password options', function (): void {
    $exitCode = Artisan::call('app:setup', [
        '--force' => true,
        '--generate-passwords' => true,
        '--super-admin-password' => 'short',
        '--admin-password' => 'short',
    ]);

    $this->assertSame(0, $exitCode);
    $this->assertDatabaseHas('users', ['email' => 'super@example.com']);
    $this->assertDatabaseHas('users', ['email' => 'admin@example.com']);
});

test('mail passwords sends notifications instead of printing plaintext passwords', function (): void {
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
});

test('setup fails when it has already run', function (): void {
    $this->assertSame(0, Artisan::call('app:setup', ['--force' => true]));

    $exitCode = Artisan::call('app:setup', ['--force' => true]);

    $this->assertSame(1, $exitCode);
    $this->assertSame(2, User::query()->count());
});

test('setup fails when emails match', function (): void {
    $exitCode = Artisan::call('app:setup', [
        '--force' => true,
        '--super-admin-email' => 'same@example.com',
        '--admin-email' => 'same@example.com',
    ]);

    $this->assertSame(1, $exitCode);
    $this->assertDatabaseMissing('users', ['email' => 'same@example.com']);
});

test('setup fails when setup user email already exists', function (): void {
    User::factory()->create(['email' => 'super@example.com']);

    $exitCode = Artisan::call('app:setup', ['--force' => true]);

    $this->assertSame(1, $exitCode);
    $this->assertDatabaseMissing('users', ['email' => 'admin@example.com']);
});

test('database seeder does not create system access data', function (): void {
    $this->seed(DatabaseSeeder::class);

    $this->assertSame(0, User::query()->count());
    $this->assertSame(0, Role::query()->count());
    $this->assertSame(0, Permission::query()->count());
});
