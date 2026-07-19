<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Download, RotateCcw } from '@lucide/vue';
import { ref } from 'vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
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
const restoring = ref(false);
const selectedBackup = ref<string | null>(null);

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
            },
            onError: () => {
                toast.error(t('settings.backup.failed'));
            },
            onFinish: () => {
                creating.value = false;
            },
        },
    );
}

function restoreBackup(filename: string): void {
    selectedBackup.value = filename;
}

function confirmRestore(): void {
    if (!selectedBackup.value) {
        return;
    }

    restoring.value = true;
    router.post(
        restore(selectedBackup.value).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(t('settings.backup.restored'));
                selectedBackup.value = null;
            },
            onError: () => toast.error(t('settings.backup.restore_failed')),
            onFinish: () => {
                restoring.value = false;
            },
        },
    );
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
                    <Button
                        size="lg"
                        :disabled="creating"
                        @click="createBackup"
                    >
                        <Spinner v-if="creating" />
                        <Download v-else class="size-4" aria-hidden="true" />
                        {{
                            creating
                                ? t('settings.backup.creating')
                                : t('settings.backup.create')
                        }}
                    </Button>
                </CardAction>
            </CardHeader>
            <CardContent>
                <EmptyState
                    v-if="backups.length === 0"
                    compact
                    :title="t('settings.backup.empty')"
                    :description="t('settings.backup.empty_description')"
                >
                    <template #icon>
                        <Download class="size-7" aria-hidden="true" />
                    </template>
                </EmptyState>
                <div v-else class="space-y-3">
                    <div
                        v-for="backup in backups"
                        :key="backup.filename"
                        class="flex flex-col gap-4 rounded-2xl border border-border/70 bg-background p-4 sm:flex-row sm:items-center sm:justify-between"
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

        <WorkspaceConfirmDialog
            :open="selectedBackup !== null"
            :title="t('settings.backup.restore_title')"
            :description="
                t('settings.backup.restore_confirm', {
                    filename: selectedBackup ?? '',
                })
            "
            :confirm-label="
                restoring
                    ? t('settings.backup.restoring')
                    : t('settings.backup.restore')
            "
            :cancel-label="t('common.actions.cancel')"
            :processing="restoring"
            @update:open="!$event && !restoring && (selectedBackup = null)"
            @confirm="confirmRestore"
        >
            <template #icon>
                <RotateCcw class="size-5" aria-hidden="true" />
            </template>
        </WorkspaceConfirmDialog>
    </div>
</template>
