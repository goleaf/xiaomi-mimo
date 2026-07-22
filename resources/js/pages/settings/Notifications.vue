<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import { BellRing, Mail, Monitor } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { update } from '@/routes/preferences';
import type { SettingsLayoutProps } from '@/types';
import type { UserPreference } from '@/types/models';

const props = defineProps<{ preferences: UserPreference }>();
const toast = useToast();
const { t } = useUi();

setLayoutProps<SettingsLayoutProps>({
    settingsEyebrow: t('account.menu.settings'),
    settingsTitle: t('settings.notifications.title'),
    settingsDescription: t('settings.notifications.description'),
});

const form = useForm({
    notification_email: props.preferences.notification_email,
    notification_browser: props.preferences.notification_browser,
    notification_in_app: props.preferences.notification_in_app,
});
type BrowserPermissionState = NotificationPermission | 'unsupported';
const browserPermission = ref<BrowserPermissionState>('unsupported');

onMounted(() => {
    browserPermission.value =
        'Notification' in window
            ? window.Notification.permission
            : 'unsupported';
});

const browserPermissionLabel = computed(() =>
    t(`settings.notifications.browser_permission_${browserPermission.value}`),
);

const notificationOptions = computed(() => [
    {
        key: 'notification_email' as const,
        label: t('settings.notifications.email'),
        description: t('settings.notifications.email_description'),
        icon: Mail,
    },
    {
        key: 'notification_browser' as const,
        label: t('settings.notifications.browser'),
        description: t('settings.notifications.browser_description'),
        icon: Monitor,
    },
    {
        key: 'notification_in_app' as const,
        label: t('settings.notifications.in_app'),
        description: t('settings.notifications.in_app_description'),
        icon: BellRing,
    },
]);

function submit() {
    form.put(update.url(), {
        onSuccess: () => toast.success(t('settings.notifications.saved')),
    });
}

async function requestBrowserPermission(): Promise<void> {
    if (!('Notification' in window)) {
        return;
    }

    browserPermission.value = await window.Notification.requestPermission();
    toast.success(t('settings.notifications.browser_permission_updated'));
}
</script>

<template>
    <Head :title="t('settings.notifications.title')" />
    <div class="space-y-6">
        <form @submit.prevent="submit">
            <Card>
                <CardHeader>
                    <CardTitle>{{
                        t('settings.notifications.channels_title')
                    }}</CardTitle>
                    <CardDescription>
                        {{ t('settings.notifications.channels_description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="option in notificationOptions"
                        :key="option.key"
                        class="flex min-h-20 items-center gap-4 rounded-2xl border border-border/70 bg-muted/20 p-4 transition-colors hover:bg-muted/35"
                    >
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-2xl border border-orange-500/15 bg-orange-500/[0.08] text-orange-700 dark:text-orange-300"
                        >
                            <component
                                :is="option.icon"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>
                        <Label
                            :for="option.key"
                            class="min-w-0 flex-1 cursor-pointer flex-col items-start gap-0"
                        >
                            <span class="block font-medium text-foreground">
                                {{ option.label }}
                            </span>
                            <span
                                class="mt-1 block text-sm leading-5 font-normal text-muted-foreground"
                            >
                                {{ option.description }}
                            </span>
                        </Label>
                        <Checkbox
                            :id="option.key"
                            :model-value="form[option.key]"
                            class="size-5"
                            :disabled="form.processing"
                            @update:model-value="
                                form[option.key] = Boolean($event)
                            "
                        />
                    </div>

                    <div
                        class="rounded-2xl border border-border/70 bg-muted/20 p-4"
                    >
                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div>
                                <p class="text-sm font-medium">
                                    {{
                                        t(
                                            'settings.notifications.browser_permission_title',
                                        )
                                    }}
                                </p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    {{ browserPermissionLabel }}
                                </p>
                            </div>
                            <Button
                                v-if="browserPermission === 'default'"
                                type="button"
                                variant="outline"
                                @click="requestBrowserPermission"
                            >
                                {{
                                    t(
                                        'settings.notifications.browser_request_permission',
                                    )
                                }}
                            </Button>
                        </div>
                        <p class="mt-3 text-xs leading-5 text-muted-foreground">
                            {{ t('settings.notifications.browser_live_only') }}
                        </p>
                    </div>
                </CardContent>
            </Card>
            <div class="mt-4 flex justify-end">
                <Button type="submit" size="lg" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{ t('common.actions.save') }}
                </Button>
            </div>
        </form>
    </div>
</template>
