<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { Download, Upload } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { exportMethod, importMethod } from '@/routes';
import type { SettingsLayoutProps } from '@/types';
import type { Workspace } from '@/types/models';

const props = defineProps<{ workspace: Workspace }>();
const toast = useToast();
const { t } = useUi();

setLayoutProps<SettingsLayoutProps>({
    settingsEyebrow: t('account.menu.settings'),
    settingsTitle: t('settings.export.title'),
    settingsDescription: t('settings.export.description'),
});

function exportData(format: string) {
    window.location.href = exportMethod([props.workspace.id, format]).url;
    toast.success(
        t('settings.export.exporting', { format: format.toUpperCase() }),
    );
}

function handleImport(event: Event, format: string) {
    const input = event.target as HTMLInputElement;

    if (!input.files?.length) {
        return;
    }

    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('format', format);

    router.post(importMethod(props.workspace.id).url, formData, {
        onSuccess: () => toast.success(t('settings.export.import_success')),
        preserveScroll: true,
    });
    input.value = '';
}
</script>

<template>
    <Head :title="t('settings.export.title')" />
    <div class="space-y-6">
        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.export.export_title') }}</CardTitle>
                <CardDescription>{{
                    t('settings.export.export_description')
                }}</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-3 sm:grid-cols-3">
                <Button
                    v-for="format in ['json', 'csv', 'markdown']"
                    :key="format"
                    variant="outline"
                    class="min-h-20 justify-start rounded-2xl bg-muted/20 px-4 hover:border-orange-500/25 hover:bg-orange-500/[0.05]"
                    @click="exportData(format)"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <Download class="size-4" aria-hidden="true" />
                    </span>
                    <span class="font-semibold uppercase">{{ format }}</span>
                </Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.export.import_title') }}</CardTitle>
                <CardDescription>{{
                    t('settings.export.import_description')
                }}</CardDescription>
            </CardHeader>
            <CardContent class="grid gap-3 sm:grid-cols-2">
                <label
                    class="inline-flex min-h-20 cursor-pointer items-center gap-3 rounded-2xl border border-border bg-muted/20 px-4 text-sm font-medium transition-colors focus-within:ring-2 focus-within:ring-orange-500 hover:border-orange-500/25 hover:bg-orange-500/[0.05]"
                >
                    <input
                        type="file"
                        accept=".json"
                        class="hidden"
                        @change="handleImport($event, 'json')"
                    />
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <Upload class="size-4" aria-hidden="true" />
                    </span>
                    {{ t('settings.export.import_json') }}
                </label>
                <label
                    class="inline-flex min-h-20 cursor-pointer items-center gap-3 rounded-2xl border border-border bg-muted/20 px-4 text-sm font-medium transition-colors focus-within:ring-2 focus-within:ring-orange-500 hover:border-orange-500/25 hover:bg-orange-500/[0.05]"
                >
                    <input
                        type="file"
                        accept=".csv"
                        class="hidden"
                        @change="handleImport($event, 'csv')"
                    />
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <Upload class="size-4" aria-hidden="true" />
                    </span>
                    {{ t('settings.export.import_csv') }}
                </label>
            </CardContent>
        </Card>
    </div>
</template>
