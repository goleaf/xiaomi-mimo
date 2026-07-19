<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    User,
    Shield,
    Palette,
    Bell,
    Download,
    Users,
    Database,
    Globe,
} from '@lucide/vue';
import { computed } from 'vue';
import { Card } from '@/components/ui/card';

const page = usePage();
const currentUrl = computed(() => page.url);

const navItems = [
    { label: 'Profile', href: '/settings/profile', icon: User },
    { label: 'Security', href: '/settings/security', icon: Shield },
    { label: 'Appearance', href: '/settings/appearance', icon: Palette },
    { label: 'Preferences', href: '/settings/preferences', icon: Globe },
    { label: 'Notifications', href: '/settings/notifications', icon: Bell },
    { label: 'Members', href: '/settings/members', icon: Users },
    { label: 'Export / Import', href: '/settings/export', icon: Download },
    { label: 'Backup', href: '/settings/backup', icon: Database },
];
</script>

<template>
    <div class="flex gap-8">
        <nav class="w-48 shrink-0 space-y-1">
            <Link
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                :class="[
                    'flex items-center gap-3 rounded-md px-3 py-2 text-sm transition-colors',
                    currentUrl.startsWith(item.href)
                        ? 'bg-muted font-medium text-foreground'
                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                ]"
            >
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.label }}
            </Link>
        </nav>
        <div class="min-w-0 flex-1">
            <slot />
        </div>
    </div>
</template>
