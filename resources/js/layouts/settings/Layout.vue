<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Card } from '@/components/ui/card';
import { User, Shield, Palette, Bell, Download, Upload, Users, Database, Globe } from '@lucide/vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const navItems = [
    { label: 'Profile', href: route('profile.edit'), icon: User },
    { label: 'Security', href: route('security.edit'), icon: Shield },
    { label: 'Appearance', href: route('appearance.edit'), icon: Palette },
    { label: 'Preferences', href: route('preferences.edit'), icon: Globe },
    { label: 'Notifications', href: route('notifications.edit'), icon: Bell },
    { label: 'Members', href: route('members.edit'), icon: Users },
    { label: 'Export / Import', href: route('export.edit', '1'), icon: Download },
    { label: 'Backup', href: route('backup.edit'), icon: Database },
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
                    currentUrl === item.href
                        ? 'bg-muted font-medium text-foreground'
                        : 'text-muted-foreground hover:bg-muted/50 hover:text-foreground',
                ]"
            >
                <component :is="item.icon" class="h-4 w-4" />
                {{ item.label }}
            </Link>
        </nav>
        <div class="flex-1 min-w-0">
            <slot />
        </div>
    </div>
</template>
