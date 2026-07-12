import {
    BriefcaseBusiness,
    Building2,
    KeyRound,
    LayoutGrid,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

export const dashboardNavItem: NavItem = {
    title: 'Dashboard',
    href: dashboard(),
    icon: LayoutGrid,
};

export const appNavGroups: NavGroup[] = [
    {
        title: 'People',
        icon: Users,
        items: [
            {
                title: 'Users',
                href: '/users',
                icon: Users,
            },
            {
                title: 'Departments',
                href: '/departments',
                icon: Building2,
            },
            {
                title: 'Designations',
                href: '/designations',
                icon: BriefcaseBusiness,
            },
        ],
    },
    {
        title: 'Access',
        icon: ShieldCheck,
        items: [
            {
                title: 'Roles',
                href: '/roles',
                icon: ShieldCheck,
            },
            {
                title: 'Permissions',
                href: '/permissions',
                icon: KeyRound,
            },
        ],
    },
];

export function visibleNavGroups(isSuperAdmin: boolean): NavGroup[] {
    return appNavGroups
        .map((group) => ({
            ...group,
            items: group.items.filter(
                (item) => isSuperAdmin || item.href !== '/permissions',
            ),
        }))
        .filter((group) => group.items.length > 0);
}

export function searchableNavItems(isSuperAdmin: boolean): NavItem[] {
    return [
        dashboardNavItem,
        ...visibleNavGroups(isSuperAdmin).flatMap((group) => group.items),
    ];
}
