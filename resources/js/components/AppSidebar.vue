<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Workspace, Project } from '@/types/models';
import WorkspaceSwitcher from '@/components/workspace/WorkspaceSwitcher.vue';
import { LayoutDashboard, CheckSquare, Folder, Calendar, Bell, Settings, Activity } from '@lucide/vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const workspaces = computed(() => (page.props as Record<string, unknown>).workspaces as Workspace[] ?? []);
const currentWorkspace = computed(() => (page.props as Record<string, unknown>).currentWorkspace as Workspace | null);
const projects = computed(() => (page.props as Record<string, unknown>).projects as Project[] ?? []);

const navItems = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
    { label: 'Tasks', href: '/tasks', icon: CheckSquare },
    { label: 'Projects', href: '/projects', icon: Folder },
    { label: 'Calendar', href: '/calendar', icon: Calendar },
    { label: 'Activity', href: '/activity', icon: Activity },
    { label: 'Notifications', href: '/notifications', icon: Bell },
    { label: 'Settings', href: '/settings/profile', icon: Settings },
];
</script>

<template>
    <aside class="w-64 border-r bg-background flex flex-col h-screen">
        <div class="p-4 border-b">
            <WorkspaceSwitcher :workspaces="workspaces" :current-workspace="currentWorkspace" />
        </div>
        <nav class="flex-1 p-3 space-y-1 overflow-y-auto">
            <Link v-for="item in navItems" :key="item.href" :href="item.href"
                :class="[
                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                    currentUrl.startsWith(item.href)
                        ? 'bg-muted font-medium text-foreground'
                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                ]">
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.label }}
            </Link>
        </nav>
        <div class="p-3 border-t">
            <p class="text-xs text-muted-foreground px-3">Projects</p>
            <div class="mt-2 space-y-1">
                <Link v-for="project in projects.slice(0, 5)" :key="project.id"
                    :href="route('projects.show', [currentWorkspace?.id, project.id])"
                    class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm text-muted-foreground hover:bg-muted/50 hover:text-foreground">
                    <div class="h-2 w-2 rounded-full" :style="{ backgroundColor: project.color }" />
                    <span class="truncate">{{ project.name }}</span>
                </Link>
            </div>
        </div>
    </aside>
</template>
