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
            <CardContent class="flex gap-3">
                <Button variant="outline" @click="exportData('json')"
                    ><Download class="mr-2 h-4 w-4" />JSON</Button
                >
                <Button variant="outline" @click="exportData('csv')"
                    ><Download class="mr-2 h-4 w-4" />CSV</Button
                >
                <Button variant="outline" @click="exportData('markdown')"
                    ><Download class="mr-2 h-4 w-4" />Markdown</Button
                >
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>{{ t('settings.export.import_title') }}</CardTitle>
                <CardDescription>{{
                    t('settings.export.import_description')
                }}</CardDescription>
            </CardHeader>
            <CardContent class="flex gap-3">
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input
                        type="file"
                        accept=".json"
                        class="hidden"
                        @change="handleImport($event, 'json')"
                    />
                    <Button variant="outline"
                        ><Upload class="mr-2 h-4 w-4" />{{
                            t('settings.export.import_json')
                        }}</Button
                    >
                </label>
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input
                        type="file"
                        accept=".csv"
                        class="hidden"
                        @change="handleImport($event, 'csv')"
                    />
                    <Button variant="outline"
                        ><Upload class="mr-2 h-4 w-4" />{{
                            t('settings.export.import_csv')
                        }}</Button
                    >
                </label>
            </CardContent>
        </Card>
    </div>
</template>
