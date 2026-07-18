<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Download, RotateCcw, Trash2 } from '@lucide/vue';

const toast = useToast();
const creating = ref(false);

interface Backup {
    filename: string;
    size: number;
    created_at: number;
}

defineProps<{ backups: Backup[] }>();

function createBackup() {
    creating.value = true;
    router.post('/backup', {}, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Backup created'); creating.value = false; },
        onError: () => { toast.error('Backup failed'); creating.value = false; },
    });
}

function restoreBackup(filename: string) {
    if (confirm(`Restore from ${filename}? This will replace the current database.`)) {
        router.post(`/backups/${filename}/restore`, {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Database restored'),
        });
    }
}

function downloadBackup(filename: string) {
    window.location.href = `/backups/${filename}/download`;
}

function formatSize(bytes: number): string {
    const units = ['B', 'KB', 'MB', 'GB'];
    let i = 0;
    while (bytes >= 1024 && i < units.length - 1) { bytes /= 1024; i++; }
    return `${Math.round(bytes)} ${units[i]}`;
}

function formatDate(timestamp: number): string {
    return new Date(timestamp * 1000).toLocaleString();
}
</script>

<template>
    <Head title="Backup" />
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold">Database Backup</h2>
                <p class="text-sm text-muted-foreground">Create and manage SQLite database backups</p>
            </div>
            <Button @click="createBackup" :disabled="creating">
                <Download class="mr-2 h-4 w-4" />
                {{ creating ? 'Creating...' : 'Create Backup' }}
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Backups</CardTitle>
                <CardDescription>{{ backups.length }} backup(s) available</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="backups.length === 0" class="text-center py-8 text-muted-foreground">
                    No backups yet. Create your first backup above.
                </div>
                <div v-else class="space-y-3">
                    <div v-for="backup in backups" :key="backup.filename"
                        class="flex items-center justify-between rounded-lg border p-4">
                        <div>
                            <p class="text-sm font-medium">{{ backup.filename }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ formatSize(backup.size) }} — {{ formatDate(backup.created_at) }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Button variant="outline" size="sm" @click="downloadBackup(backup.filename)">
                                <Download class="h-4 w-4" />
                            </Button>
                            <Button variant="outline" size="sm" @click="restoreBackup(backup.filename)">
                                <RotateCcw class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
