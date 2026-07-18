<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Project } from '@/types/models';

defineProps<{
    project: { data: Project };
    workspace: { id: string; name: string };
}>();
</script>

<template>
    <Head :title="project.data.name" />
    <div class="space-y-6 p-6">
        <div class="flex items-center gap-3">
            <div class="h-8 w-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: project.data.color + '20' }">
                <Folder class="h-4 w-4" :style="{ color: project.data.color }" />
            </div>
            <div>
                <h1 class="text-2xl font-bold">{{ project.data.name }}</h1>
                <p class="text-muted-foreground">{{ project.data.description ?? 'No description' }}</p>
            </div>
        </div>
    </div>
</template>
