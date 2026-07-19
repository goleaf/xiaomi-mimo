<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    User,
    Shield,
    Bell,
    Download,
    Users,
    Database,
    Globe,
} from '@lucide/vue';
import { computed } from 'vue';
import { edit as editProfile } from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit as editPreferences } from '@/routes/preferences';

const page = usePage();
const currentUrl = computed(() => page.url);

const navItems = [
    { label: 'Profile', href: editProfile.url(), icon: User },
    { label: 'Security', href: '/settings/security', icon: Shield },
    { label: 'Preferences', href: editPreferences.url(), icon: Globe },
    { label: 'Notifications', href: '/settings/notifications', icon: Bell },
    { label: 'Members', href: '/settings/members', icon: Users },
    { label: 'Export / Import', href: '/settings/export', icon: Download },
    { label: 'Backup', href: '/settings/backup', icon: Database },
];
</script>

<template>
    <div class="flex flex-col gap-6 lg:flex-row lg:gap-8">
        <nav
            class="-mx-1 flex gap-1 overflow-x-auto px-1 pb-1 lg:mx-0 lg:w-48 lg:shrink-0 lg:flex-col lg:overflow-visible lg:px-0 lg:pb-0"
        >
            <Link
                v-for="item in navItems"
                :key="item.href"
                :href="item.href"
                :aria-current="
                    currentUrl.startsWith(item.href) ? 'page' : undefined
                "
                :class="[
                    'flex shrink-0 items-center gap-3 rounded-md px-3 py-2 text-sm whitespace-nowrap transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
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
