<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="sticky top-0 z-20 flex h-14 shrink-0 items-center gap-2 border-b border-sidebar-border/70 bg-background/90 px-4 backdrop-blur supports-[backdrop-filter]:bg-background/75 md:px-5"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
