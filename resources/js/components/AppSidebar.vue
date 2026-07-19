<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Bell,
    CalendarDays,
    CheckSquare2,
    FolderKanban,
    LayoutDashboard,
    Settings,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarRail,
} from '@/components/ui/sidebar';
import WorkspaceSwitcher from '@/components/workspace/WorkspaceSwitcher.vue';
import { activity, calendar, dashboard, projects } from '@/routes';
import { index as notificationsIndex } from '@/routes/notifications';
import { edit as profileEdit } from '@/routes/profile';
import { show as projectShow } from '@/routes/projects';
import { index as tasksIndex } from '@/routes/todos';
import type { NavItem } from '@/types';
import type { Project } from '@/types/models';

const page = usePage();
const navigation = computed(() => page.props.navigation);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: navigation.value.labels.dashboard,
        href: dashboard(),
        icon: LayoutDashboard,
        isActive: page.component === 'Dashboard',
    },
    {
        title: navigation.value.labels.tasks,
        href: tasksIndex(),
        icon: CheckSquare2,
        isActive: page.component.startsWith('tasks/'),
    },
    {
        title: navigation.value.labels.projects,
        href: projects(),
        icon: FolderKanban,
        isActive: page.component.startsWith('projects/'),
    },
    {
        title: navigation.value.labels.calendar,
        href: calendar(),
        icon: CalendarDays,
        isActive: page.component.startsWith('calendar/'),
    },
    {
        title: navigation.value.labels.activity,
        href: activity(),
        icon: Activity,
        isActive: page.component.startsWith('activity/'),
    },
    {
        title: navigation.value.labels.notifications,
        href: notificationsIndex(),
        icon: Bell,
        isActive: page.component.startsWith('notifications/'),
    },
    {
        title: navigation.value.labels.settings,
        href: profileEdit(),
        icon: Settings,
        isActive: page.component.startsWith('settings/'),
    },
]);

function projectHref(project: Project) {
    const workspace = navigation.value.currentWorkspace;

    return workspace ? projectShow({ workspace, project }) : projects();
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="gap-2 border-b border-sidebar-border/80 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child tooltip="Xiaomi Mimo">
                        <Link :href="dashboard()" prefetch>
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <WorkspaceSwitcher
                :workspaces="navigation.workspaces"
                :current-workspace="navigation.currentWorkspace"
                :labels="navigation.labels"
            />
        </SidebarHeader>

        <SidebarContent class="py-3">
            <NavMain
                :items="mainNavItems"
                :label="navigation.labels.platform"
            />

            <SidebarGroup class="px-2 py-2">
                <SidebarGroupLabel>
                    {{ navigation.labels.recentProjects }}
                </SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="project in navigation.projects"
                        :key="project.id"
                    >
                        <SidebarMenuButton
                            as-child
                            :tooltip="project.name"
                            :is-active="
                                page.component === 'projects/Show' &&
                                page.url.includes(project.id)
                            "
                        >
                            <Link :href="projectHref(project)" prefetch>
                                <span
                                    class="size-2.5 shrink-0 rounded-full border border-black/10 shadow-sm dark:border-white/20"
                                    :style="{
                                        backgroundColor: project.color,
                                    }"
                                />
                                <span>{{ project.name }}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <p
                    v-if="navigation.projects.length === 0"
                    class="px-2 py-2 text-xs text-sidebar-foreground/55 group-data-[collapsible=icon]:hidden"
                >
                    {{ navigation.labels.noProjects }}
                </p>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/80 pt-3">
            <NavUser />
        </SidebarFooter>

        <SidebarRail />
    </Sidebar>
</template>
