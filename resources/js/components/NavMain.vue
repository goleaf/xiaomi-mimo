<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label: string;
}>();

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="
                        item.isActive ?? isCurrentOrParentUrl(item.href)
                    "
                    :tooltip="item.title"
                    class="data-[active=true]:bg-orange-500/10 data-[active=true]:text-orange-700 data-[active=true]:shadow-[inset_3px_0_0_0_var(--color-orange-500)] dark:data-[active=true]:text-orange-300"
                >
                    <Link
                        :href="item.href"
                        prefetch
                        view-transition
                        class="data-loading:opacity-60"
                    >
                        <component :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
