<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class OrganizationSettings
{
    private const GROUP = 'organization';

    /**
     * @return array<string, string|null>
     */
    public static function defaults(): array
    {
        return [
            'name' => config('app.name', 'HRMS'),
            'legal_name' => null,
            'email' => null,
            'phone' => null,
            'website' => null,
            'address' => null,
            'primary_color' => '#18181b',
            'sidebar_color' => '#fafafa',
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('settings')) {
            return self::defaults();
        }

        $stored = DB::table('settings')
            ->where('group', self::GROUP)
            ->pluck('payload', 'name')
            ->map(fn (string $payload): mixed => json_decode($payload, true))
            ->all();

        return array_replace(self::defaults(), $stored);
    }

    /**
     * @param  array<string, string|null>  $values
     */
    public static function save(array $values): void
    {
        $now = now();

        foreach (array_intersect_key($values, self::defaults()) as $name => $value) {
            DB::table('settings')->updateOrInsert(
                [
                    'group' => self::GROUP,
                    'name' => $name,
                ],
                [
                    'locked' => false,
                    'payload' => json_encode($value),
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public static function seedDefaults(): void
    {
        self::save(self::defaults());
    }

    /**
     * @return array<string, string>
     */
    public static function themeVariables(?array $settings = null): array
    {
        $settings ??= self::all();
        $primaryColor = self::hexColor($settings['primary_color'] ?? null, '#18181b');
        $sidebarColor = self::hexColor($settings['sidebar_color'] ?? null, '#fafafa');
        $primaryForeground = self::contrastColor($primaryColor);
        $sidebarForeground = self::contrastColor($sidebarColor);

        return [
            '--primary' => $primaryColor,
            '--primary-foreground' => $primaryForeground,
            '--ring' => $primaryColor,
            '--sidebar' => $sidebarColor,
            '--sidebar-foreground' => $sidebarForeground,
            '--sidebar-primary' => $primaryColor,
            '--sidebar-primary-foreground' => $primaryForeground,
            '--sidebar-accent' => "color-mix(in srgb, {$sidebarForeground} 12%, transparent)",
            '--sidebar-accent-foreground' => $sidebarForeground,
            '--sidebar-border' => "color-mix(in srgb, {$sidebarForeground} 18%, transparent)",
            '--sidebar-ring' => $primaryColor,
        ];
    }

    public static function themeStyle(?array $settings = null): string
    {
        return collect(self::themeVariables($settings))
            ->map(fn (string $value, string $key): string => "{$key}: {$value}")
            ->implode('; ');
    }

    private static function hexColor(mixed $value, string $fallback): string
    {
        if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', $value) === 1) {
            return $value;
        }

        return $fallback;
    }

    private static function contrastColor(string $hex): string
    {
        $color = ltrim($hex, '#');
        $red = hexdec(substr($color, 0, 2));
        $green = hexdec(substr($color, 2, 2));
        $blue = hexdec(substr($color, 4, 2));
        $luminance = ($red * 299 + $green * 587 + $blue * 114) / 1000;

        return $luminance > 150 ? '#000000' : '#ffffff';
    }
}
