<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import type { Workspace } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Plus, Users, Folder, CheckSquare } from '@lucide/vue';

defineProps<{ workspaces: { data: Workspace[] } }>();

const toast = useToast();
const showCreateDialog = ref(false);
const form = ref({ name: '', description: '' });

function createWorkspace() {
    if (!form.value.name.trim()) return;
    router.post(route('workspaces.store'), form.value, {
        preserveScroll: true,
        onSuccess: () => { toast.success('Workspace created'); showCreateDialog.value = false; form.value = { name: '', description: '' }; },
    });
}
</script>

<template>
    <Head title="Workspaces" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Workspaces</h1>
                <p class="text-muted-foreground">{{ workspaces.data.length }} workspace(s)</p>
            </div>
            <Button @click="showCreateDialog = true"><Plus class="mr-2 h-4 w-4" />New Workspace</Button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card v-for="workspace in workspaces.data" :key="workspace.id"
                class="hover:shadow-md transition-shadow cursor-pointer">
                <CardHeader>
                    <CardTitle>{{ workspace.name }}</CardTitle>
                    <p class="text-sm text-muted-foreground">{{ workspace.description ?? 'No description' }}</p>
                </CardHeader>
                <CardContent>
                    <div class="flex items-center gap-4 text-sm text-muted-foreground">
                        <span class="flex items-center gap-1"><Users class="h-4 w-4" />{{ workspace.members_count ?? 0 }}</span>
                        <span class="flex items-center gap-1"><Folder class="h-4 w-4" />{{ workspace.projects_count ?? 0 }}</span>
                        <span class="flex items-center gap-1"><CheckSquare class="h-4 w-4" />{{ workspace.todos_count ?? 0 }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-if="workspaces.data.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <p class="text-lg">No workspaces yet</p>
            <Button class="mt-4" @click="showCreateDialog = true"><Plus class="mr-2 h-4 w-4" />Create Workspace</Button>
        </div>
    </div>

    <Dialog :open="showCreateDialog" @update:open="showCreateDialog = false">
        <DialogContent class="sm:max-w-md">
            <DialogHeader><DialogTitle>New Workspace</DialogTitle></DialogHeader>
            <form @submit.prevent="createWorkspace" class="space-y-4">
                <div class="space-y-2">
                    <Label for="ws-name">Name</Label>
                    <Input id="ws-name" v-model="form.name" placeholder="Workspace name" autofocus />
                </div>
                <div class="space-y-2">
                    <Label for="ws-desc">Description</Label>
                    <Input id="ws-desc" v-model="form.description" placeholder="Optional description" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="showCreateDialog = false">Cancel</Button>
                    <Button type="submit">Create</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
