<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { Project, Workspace } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import ProjectCreateDialog from '@/components/project/ProjectCreateDialog.vue';
import { Plus, Folder, Archive } from '@lucide/vue';

const props = defineProps<{
    projects: { data: Project[] };
    workspace: Workspace;
}>();

const showCreateDialog = ref(false);
</script>

<template>
    <Head title="Projects" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Projects</h1>
                <p class="text-muted-foreground">{{ projects.data.length }} projects in {{ workspace.name }}</p>
            </div>
            <Button @click="showCreateDialog = true"><Plus class="mr-2 h-4 w-4" />New Project</Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Link v-for="project in projects.data" :key="project.id"
                :href="route('projects.show', [workspace.id, project.id])">
                <Card class="hover:shadow-md transition-shadow cursor-pointer h-full group">
                    <CardHeader class="flex flex-row items-center gap-3">
                        <div class="h-10 w-10 rounded-lg flex items-center justify-center transition-transform group-hover:scale-105"
                            :style="{ backgroundColor: project.color + '20' }">
                            <Folder class="h-5 w-5" :style="{ color: project.color }" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <CardTitle class="text-base truncate">{{ project.name }}</CardTitle>
                            <p class="text-xs text-muted-foreground truncate">{{ project.description ?? 'No description' }}</p>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2">
                            <Badge variant="secondary">{{ project.todos_count ?? 0 }} tasks</Badge>
                            <Badge v-if="project.is_archived" variant="outline"><Archive class="h-3 w-3 mr-1" />Archived</Badge>
                        </div>
                    </CardContent>
                </Card>
            </Link>
        </div>

        <div v-if="projects.data.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <p class="text-lg">No projects yet</p>
            <p class="text-sm mb-4">Create your first project to organize tasks</p>
            <Button @click="showCreateDialog = true"><Plus class="mr-2 h-4 w-4" />Create Project</Button>
        </div>
    </div>

    <ProjectCreateDialog :open="showCreateDialog" :workspace-id="workspace.id"
        @close="showCreateDialog = false" @created="() => {}" />
</template>
