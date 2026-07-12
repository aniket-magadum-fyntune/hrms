<?php

use App\Models\User;
use App\Support\OrganizationSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('organization settings are visible only to super admin', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();
    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($superAdmin)
        ->get(route('organization.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('settings/organization')
            ->where('organization.name', config('app.name'))
        );

    $this->actingAs($admin)
        ->get(route('organization.edit'))
        ->assertForbidden();
});

test('super admin can update organization settings', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();

    $this->actingAs($superAdmin)
        ->put(route('organization.update'), [
            'name' => 'Acme HR',
            'legal_name' => 'Acme Private Limited',
            'email' => 'hello@example.com',
            'phone' => '+1 555 100 2000',
            'website' => 'https://example.com',
            'address' => '100 Main Street',
            'primary_color' => '#0f766e',
            'sidebar_color' => '#1d4ed8',
        ])
        ->assertRedirect(route('organization.edit'));

    $settings = OrganizationSettings::all();

    $this->assertSame('Acme HR', $settings['name']);
    $this->assertSame('Acme Private Limited', $settings['legal_name']);
    $this->assertSame('hello@example.com', $settings['email']);
    $this->assertSame('#0f766e', $settings['primary_color']);
    $this->assertSame('#1d4ed8', $settings['sidebar_color']);
});

test('organization settings validate colors and contact details', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    $superAdmin = User::query()->where('email', 'super@example.com')->firstOrFail();

    $this->actingAs($superAdmin)
        ->put(route('organization.update'), [
            'name' => '',
            'email' => 'not-an-email',
            'website' => 'not-a-url',
            'primary_color' => 'blue',
            'sidebar_color' => '#12345',
        ])
        ->assertSessionHasErrors([
            'name',
            'email',
            'website',
            'primary_color',
            'sidebar_color',
        ]);
});

test('organization settings are shared with inertia responses', function (): void {
    Artisan::call('app:setup', ['--force' => true]);

    OrganizationSettings::save([
        'name' => 'Acme HR',
        'primary_color' => '#0f766e',
        'sidebar_color' => '#1d4ed8',
    ]);

    $admin = User::query()->where('email', 'admin@example.com')->firstOrFail();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('organization.name', 'Acme HR')
            ->where('organization.primary_color', '#0f766e')
            ->where('organization.sidebar_color', '#1d4ed8')
        );
});
