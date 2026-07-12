import { usePage } from '@inertiajs/react';
import { useEffect, useMemo } from 'react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import { useAppearance } from '@/hooks/use-appearance';
import {
    applyThemeVariables,
    organizationThemeVariables,
    removeThemeVariables,
    themeVariablesStyle,
} from '@/lib/theme';
import type { AppVariant, Organization } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

export function AppShell({ children, variant = 'sidebar' }: Props) {
    const { organization, sidebarOpen } = usePage<{
        organization: Organization;
        sidebarOpen: boolean;
    }>().props;
    const { resolvedAppearance } = useAppearance();
    const themeVariables = useMemo(
        () => organizationThemeVariables(organization),
        [organization],
    );
    const style =
        resolvedAppearance === 'light'
            ? themeVariablesStyle(themeVariables)
            : undefined;

    useEffect(() => {
        if (resolvedAppearance === 'dark') {
            removeThemeVariables(themeVariables);

            return;
        }

        return applyThemeVariables(themeVariables);
    }, [resolvedAppearance, themeVariables]);

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col" style={style}>
                {children}
            </div>
        );
    }

    return (
        <SidebarProvider defaultOpen={sidebarOpen} style={style}>
            {children}
        </SidebarProvider>
    );
}
