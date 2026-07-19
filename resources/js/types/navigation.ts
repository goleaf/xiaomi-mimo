import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';
import type { Project, Workspace } from '@/types/models';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

export type SidebarNavigationLabels = {
    platform: string;
    workspace: string;
    selectWorkspace: string;
    dashboard: string;
    tasks: string;
    projects: string;
    calendar: string;
    activity: string;
    notifications: string;
    settings: string;
    manageWorkspaces: string;
    recentProjects: string;
    noProjects: string;
    switchingFailed: string;
};

export type SidebarNavigation = {
    workspaces: Workspace[];
    currentWorkspace: Workspace | null;
    projects: Project[];
    labels: SidebarNavigationLabels;
};
