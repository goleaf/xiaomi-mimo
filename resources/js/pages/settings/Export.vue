<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import type { Workspace } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Download, Upload } from '@lucide/vue';

const props = defineProps<{ workspace: Workspace }>();
const toast = useToast();

function exportData(format: string) {
    window.location.href = route('export', [props.workspace.id, format]);
    toast.success(`Exporting as ${format.toUpperCase()}...`);
}

function handleImport(event: Event, format: string) {
    const input = event.target as HTMLInputElement;
    if (!input.files?.length) return;

    const formData = new FormData();
    formData.append('file', input.files[0]);
    formData.append('format', format);

    router.post(route('import', props.workspace.id), formData, {
        onSuccess: () => toast.success('Import completed'),
        preserveScroll: true,
    });
    input.value = '';
}
</script>

<template>
    <Head title="Export & Import" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">Export & Import</h2>
            <p class="text-sm text-muted-foreground">Export your data or import from a file</p>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Export</CardTitle>
                <CardDescription>Download your workspace data</CardDescription>
            </CardHeader>
            <CardContent class="flex gap-3">
                <Button variant="outline" @click="exportData('json')"><Download class="mr-2 h-4 w-4" />JSON</Button>
                <Button variant="outline" @click="exportData('csv')"><Download class="mr-2 h-4 w-4" />CSV</Button>
                <Button variant="outline" @click="exportData('markdown')"><Download class="mr-2 h-4 w-4" />Markdown</Button>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Import</CardTitle>
                <CardDescription>Import tasks from a JSON or CSV file</CardDescription>
            </CardHeader>
            <CardContent class="flex gap-3">
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="file" accept=".json" class="hidden" @change="handleImport($event, 'json')" />
                    <Button variant="outline"><Upload class="mr-2 h-4 w-4" />Import JSON</Button>
                </label>
                <label class="inline-flex items-center gap-2 cursor-pointer">
                    <input type="file" accept=".csv" class="hidden" @change="handleImport($event, 'csv')" />
                    <Button variant="outline"><Upload class="mr-2 h-4 w-4" />Import CSV</Button>
                </label>
            </CardContent>
        </Card>
    </div>
</template>
