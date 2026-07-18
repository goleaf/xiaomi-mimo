<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import type { Workspace } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Plus, Users } from '@lucide/vue';

defineProps<{
    workspaces: { data: Workspace[] };
}>();
</script>

<template>
    <Head title="Workspaces" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Workspaces</h1>
                <p class="text-muted-foreground">Manage your workspaces</p>
            </div>
            <Button><Plus class="mr-2 h-4 w-4" />New Workspace</Button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card v-for="workspace in workspaces.data" :key="workspace.id" class="hover:shadow-md transition-shadow cursor-pointer">
                <CardHeader>
                    <CardTitle>{{ workspace.name }}</CardTitle>
                    <p class="text-sm text-muted-foreground">{{ workspace.description ?? 'No description' }}</p>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <Users class="h-4 w-4" />
                        {{ workspace.members_count ?? 0 }} members
                        <span class="mx-1">·</span>
                        {{ workspace.projects_count ?? 0 }} projects
                        <span class="mx-1">·</span>
                        {{ workspace.todos_count ?? 0 }} tasks
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
