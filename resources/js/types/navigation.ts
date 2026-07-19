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

export type SettingsHeaderMetric = {
    label: string;
    value: string | number;
    icon: 'shield' | 'users';
    tone?: 'orange' | 'blue' | 'emerald' | 'slate';
};

export type SettingsLayoutProps = {
    navigationLabel?: string;
    settingsEyebrow?: string;
    settingsTitle?: string;
    settingsDescription?: string;
    settingsMetrics?: SettingsHeaderMetric[];
};
