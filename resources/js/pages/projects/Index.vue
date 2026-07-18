<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import type { Project } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Plus, Folder } from '@lucide/vue';

defineProps<{
    projects: { data: Project[] };
    workspace: { id: string; name: string };
}>();
</script>

<template>
    <Head title="Projects" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Projects</h1>
                <p class="text-muted-foreground">{{ workspace.name }}</p>
            </div>
            <Button><Plus class="mr-2 h-4 w-4" />New Project</Button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link v-for="project in projects.data" :key="project.id" :href="route('projects.show', [workspace.id, project.id])">
                <Card class="hover:shadow-md transition-shadow cursor-pointer h-full">
                    <CardHeader class="flex flex-row items-center gap-3">
                        <div class="h-8 w-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: project.color + '20' }">
                            <Folder class="h-4 w-4" :style="{ color: project.color }" />
                        </div>
                        <div class="flex-1">
                            <CardTitle class="text-base">{{ project.name }}</CardTitle>
                            <p class="text-xs text-muted-foreground">{{ project.description ?? 'No description' }}</p>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <Badge variant="secondary">{{ project.todos_count ?? 0 }} tasks</Badge>
                            <Badge v-if="project.is_archived" variant="outline">Archived</Badge>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>
    </div>
</template>
