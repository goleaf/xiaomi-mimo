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
import { useUi } from '@/composables/useUi';
import { edit as editBackup } from '@/routes/backup';
import { edit as editExport } from '@/routes/export';
import { edit as editMembers } from '@/routes/members';
import { edit as editNotifications } from '@/routes/notifications';
import { edit as editPreferences } from '@/routes/preferences';
import { edit as editSecurity } from '@/routes/security';

const page = usePage();
const currentUrl = computed(() => page.url);
const { t } = useUi();

defineProps<{ navigationLabel?: string }>();

const navItems = computed(() => [
    {
        label: t('settings.navigation.profile'),
        href: editProfile.url(),
        icon: User,
    },
    {
        label: t('settings.navigation.security'),
        href: editSecurity.url(),
        icon: Shield,
    },
    {
        label: t('settings.navigation.preferences'),
        href: editPreferences.url(),
        icon: Globe,
    },
    {
        label: t('settings.navigation.notifications'),
        href: editNotifications.url(),
        icon: Bell,
    },
    {
        label: t('settings.navigation.members'),
        href: editMembers.url(),
        icon: Users,
    },
    {
        label: t('settings.navigation.export'),
        href: editExport.url(),
        icon: Download,
    },
    {
        label: t('settings.navigation.backup'),
        href: editBackup.url(),
        icon: Database,
    },
]);
</script>

<template>
    <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
        <div class="mx-auto max-w-[1480px]">
            <div
                class="flex flex-col gap-6 rounded-[1.5rem] border border-border/80 bg-card p-4 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-6 lg:flex-row lg:gap-8"
            >
                <nav
                    :aria-label="navigationLabel ?? t('account.menu.settings')"
                    class="-mx-1 flex gap-1 overflow-x-auto rounded-xl bg-muted/55 p-1 lg:mx-0 lg:w-52 lg:shrink-0 lg:flex-col lg:self-start lg:overflow-visible"
                >
                    <Link
                        v-for="item in navItems"
                        :key="item.href"
                        :href="item.href"
                        :aria-current="
                            currentUrl.startsWith(item.href)
                                ? 'page'
                                : undefined
                        "
                        :class="[
                            'flex min-h-10 shrink-0 items-center gap-3 rounded-lg px-3 py-2 text-sm whitespace-nowrap transition-all focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none',
                            currentUrl.startsWith(item.href)
                                ? 'bg-card font-medium text-orange-800 shadow-sm dark:text-orange-200'
                                : 'text-muted-foreground hover:bg-card/70 hover:text-foreground',
                        ]"
                    >
                        <component
                            :is="item.icon"
                            class="size-4"
                            aria-hidden="true"
                        />
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="settings-page min-w-0 flex-1">
                    <slot />
                </div>
            </div>
        </div>
    </main>
</template>
