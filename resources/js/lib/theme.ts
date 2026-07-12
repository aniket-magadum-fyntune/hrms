import type { CSSProperties } from 'react';

type ThemeSource = {
    primary_color: string;
    sidebar_color: string;
};

export type ThemeVariables = Record<`--${string}`, string>;

export function isHexColor(value: string) {
    return /^#[0-9A-Fa-f]{6}$/.test(value);
}

function contrastColor(hex: string): '#000000' | '#ffffff' {
    const color = hex.replace('#', '');
    const red = Number.parseInt(color.slice(0, 2), 16);
    const green = Number.parseInt(color.slice(2, 4), 16);
    const blue = Number.parseInt(color.slice(4, 6), 16);
    const luminance = (red * 299 + green * 587 + blue * 114) / 1000;

    return luminance > 150 ? '#000000' : '#ffffff';
}

export function organizationThemeVariables(
    organization: ThemeSource,
): ThemeVariables {
    const primaryForeground = contrastColor(organization.primary_color);
    const sidebarForeground = contrastColor(organization.sidebar_color);

    return {
        '--primary': organization.primary_color,
        '--primary-foreground': primaryForeground,
        '--ring': organization.primary_color,
        '--sidebar': organization.sidebar_color,
        '--sidebar-foreground': sidebarForeground,
        '--sidebar-primary': organization.primary_color,
        '--sidebar-primary-foreground': primaryForeground,
        '--sidebar-accent': `color-mix(in srgb, ${sidebarForeground} 12%, transparent)`,
        '--sidebar-accent-foreground': sidebarForeground,
        '--sidebar-border': `color-mix(in srgb, ${sidebarForeground} 18%, transparent)`,
        '--sidebar-ring': organization.primary_color,
    };
}

export function themeVariablesStyle(variables: ThemeVariables): CSSProperties {
    return variables as CSSProperties;
}

export function applyThemeVariables(variables: ThemeVariables) {
    if (typeof document === 'undefined') {
        return () => {};
    }

    const root = document.documentElement;
    const previousValues = new Map(
        Object.keys(variables).map((key) => [
            key,
            root.style.getPropertyValue(key),
        ]),
    );

    Object.entries(variables).forEach(([key, value]) => {
        root.style.setProperty(key, value);
    });

    return () => {
        previousValues.forEach((value, key) => {
            if (value) {
                root.style.setProperty(key, value);

                return;
            }

            root.style.removeProperty(key);
        });
    };
}

export function removeThemeVariables(variables: ThemeVariables) {
    if (typeof document === 'undefined') {
        return;
    }

    Object.keys(variables).forEach((key) => {
        document.documentElement.style.removeProperty(key);
    });
}
