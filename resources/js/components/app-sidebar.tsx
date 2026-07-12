import { Link, usePage } from '@inertiajs/react';
import { BookOpen, LifeBuoy, Search } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { PageSearch } from '@/components/page-search';
import { Button } from '@/components/ui/button';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboardNavItem, visibleNavGroups } from '@/lib/navigation';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const footerNavItems: NavItem[] = [
    {
        title: 'Help center',
        href: '/help',
        icon: LifeBuoy,
    },
    {
        title: 'Updates',
        href: '/updates',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { auth } = usePage<{ auth: Auth }>().props;
    const navGroups = visibleNavGroups(auth.isSuperAdmin);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <PageSearch
                    trigger={
                        <Button
                            variant="ghost"
                            className="h-8 justify-start gap-2 px-2 group-data-[collapsible=icon]:size-8 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:p-0"
                        >
                            <Search />
                            <span className="group-data-[collapsible=icon]:hidden">
                                Search
                            </span>
                            <kbd className="ml-auto rounded border bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground group-data-[collapsible=icon]:hidden">
                                /
                            </kbd>
                        </Button>
                    }
                />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={[dashboardNavItem]} groups={navGroups} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
