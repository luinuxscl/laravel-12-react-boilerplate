import React from 'react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem, SidebarMenuSub, SidebarMenuSubButton, SidebarMenuBadge, useSidebar } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Cog, Users, Shield, Bell } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#react',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const page = usePage();
    const [unreadCount, setUnreadCount] = React.useState<number>(0);
    const { state: sidebarState, toggleSidebar } = useSidebar();
    const [adminOpen, setAdminOpen] = React.useState<boolean>(page.url.startsWith('/admin/'));
    const loadUnread = React.useCallback(async () => {
        try {
            const res = await fetch('/notifications', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) return;
            const json = await res.json();
            setUnreadCount(Array.isArray(json.unread) ? json.unread.length : 0);
        } catch {}
    }, []);

    React.useEffect(() => {
        let mounted = true;
        // initial load
        loadUnread();
        // poll
        const id = setInterval(loadUnread, 30000);
        // refresh on focus
        const onFocus = () => loadUnread();
        window.addEventListener('focus', onFocus);
        // refresh on custom events from notifications page
        const onUpdated = () => loadUnread();
        window.addEventListener('notifications:updated', onUpdated as EventListener);
        return () => {
            mounted = false;
            clearInterval(id);
            window.removeEventListener('focus', onFocus);
            window.removeEventListener('notifications:updated', onUpdated as EventListener);
        };
    }, [loadUnread]);
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />

                {/* Notifications */}
                <SidebarGroup className="px-2 py-0">
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <SidebarMenuButton asChild isActive={page.url.startsWith('/notifications')} tooltip={{ children: 'Notifications' }}>
                                <Link href="/notifications-ui" prefetch>
                                    <Bell />
                                    <span>Notifications</span>
                                </Link>
                            </SidebarMenuButton>
                            {unreadCount > 0 && (
                                <>
                                    {/* Expanded sidebar badge */}
                                    <SidebarMenuBadge>{unreadCount}</SidebarMenuBadge>
                                    {/* Collapsed-only badge (visible in icon mode) */}
                                    <div
                                        className="absolute -top-1.5 -right-1.5 hidden h-5 min-w-5 items-center justify-center rounded-full bg-destructive px-1 text-[10px] font-medium text-destructive-foreground group-data-[collapsible=icon]:flex"
                                        aria-hidden
                                    >
                                        {unreadCount}
                                    </div>
                                </>
                            )}
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>

                {/* Admin Group */}
                <SidebarGroup className="px-2 py-0">
                    <SidebarGroupLabel>Admin</SidebarGroupLabel>
                    <SidebarMenu>
                        <SidebarMenuItem>
                            <Collapsible open={adminOpen} onOpenChange={setAdminOpen}>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        tooltip={{ children: 'Admin' }}
                                        onClick={(e) => {
                                            if (sidebarState === 'collapsed') {
                                                e.preventDefault();
                                                e.stopPropagation();
                                                toggleSidebar();
                                                setAdminOpen(true);
                                            }
                                        }}
                                    >
                                        <Cog />
                                        <span>Admin</span>
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <li>
                                            <SidebarMenuSubButton asChild isActive={page.url.startsWith('/admin/users')}>
                                                <Link href="/admin/users-ui" prefetch>
                                                    <Users />
                                                    <span>Users</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </li>
                                        <li>
                                            <SidebarMenuSubButton asChild isActive={page.url.startsWith('/admin/roles')}>
                                                <Link href="/admin/roles-ui" prefetch>
                                                    <Shield />
                                                    <span>Roles</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </li>
                                        <li>
                                            <SidebarMenuSubButton asChild isActive={page.url.startsWith('/admin/settings')}>
                                                <Link href="/admin/settings-ui" prefetch>
                                                    <Cog />
                                                    <span>Settings</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </li>
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </Collapsible>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroup>
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
