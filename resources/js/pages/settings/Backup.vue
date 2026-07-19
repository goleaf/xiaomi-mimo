<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Download, RotateCcw } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import {
    create as createBackupRoute,
    download,
    restore,
} from '@/routes/backup';
import type { SettingsLayoutProps } from '@/types';

const toast = useToast();
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();
const creating = ref(false);

setLayoutProps<SettingsLayoutProps>({
    settingsEyebrow: t('account.menu.settings'),
    settingsTitle: t('settings.backup.title'),
    settingsDescription: t('settings.backup.description'),
});

interface Backup {
    filename: string;
    size: number;
    created_at: number;
}

defineProps<{ backups: Backup[] }>();

function createBackup() {
    creating.value = true;
    router.post(
        createBackupRoute().url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('settings.backup.created'));
                creating.value = false;
            },
            onError: () => {
                toast.error(t('settings.backup.failed'));
                creating.value = false;
            },
        },
    );
}

function restoreBackup(filename: string) {
    if (confirm(t('settings.backup.restore_confirm', { filename }))) {
        router.post(
            restore(filename).url,
            {},
            {
                preserveScroll: true,
                onSuccess: () => toast.success(t('settings.backup.restored')),
            },
        );
    }
}

function downloadBackup(filename: string) {
    window.location.href = download(filename).url;
}

function formatSize(bytes: number): string {
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;

    while (bytes >= 1024 && i < units.length - 1) {
        bytes /= 1024;
        i++;
    }

    return `${formatNumber(Math.round(bytes))} ${units[i]}`;
}

function formatDate(timestamp: number): string {
    return formatLocalizedDate(timestamp * 1000, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head :title="t('settings.navigation.backup')" />
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.backup.list_title') }}</CardTitle>
                <CardDescription>{{
                    t('settings.backup.available', {
                        count: formatNumber(backups.length),
                    })
                }}</CardDescription>
                <CardAction>
                    <Button @click="createBackup" :disabled="creating">
                        <Download class="size-4" aria-hidden="true" />
                        {{
                            creating
                                ? t('settings.backup.creating')
                                : t('settings.backup.create')
                        }}
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <div
                    v-if="backups.length === 0"
                    class="py-8 text-center text-muted-foreground"
                >
                    {{ t('settings.backup.empty') }}
                </div>
                <div v-else class="space-y-3">
                    <div
                        v-for="backup in backups"
                        :key="backup.filename"
                        class="flex items-center justify-between rounded-lg border p-4"
                    >
                        <div>
                            <p class="text-sm font-medium">
                                {{ backup.filename }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatSize(backup.size) }} —
                                {{ formatDate(backup.created_at) }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                :aria-label="t('settings.backup.download')"
                                @click="downloadBackup(backup.filename)"
                            >
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                :aria-label="t('settings.backup.restore')"
                                @click="restoreBackup(backup.filename)"
                            >
                                <RotateCcw class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
