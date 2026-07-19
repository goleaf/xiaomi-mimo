<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Users, Folder, CheckSquare } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { store } from '@/routes/workspaces';
import type { Workspace } from '@/types/models';

defineProps<{ workspaces: { data: Workspace[] } }>();

const toast = useToast();
const { formatNumber, t } = useUi();
const showCreateDialog = ref(false);
const form = ref({ name: '', description: '' });

function createWorkspace() {
    if (!form.value.name.trim()) {
        return;
    }

    router.post(store().url, form.value, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('workspaces.created'));
            showCreateDialog.value = false;
            form.value = { name: '', description: '' };
        },
    });
}
</script>

<template>
    <Head :title="t('workspaces.title')" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">{{ t('workspaces.title') }}</h1>
                <p class="text-muted-foreground">
                    {{
                        t('workspaces.count', {
                            count: formatNumber(workspaces.data.length),
                        })
                    }}
                </p>
            </div>
            <Button @click="showCreateDialog = true"
                ><Plus class="mr-2 h-4 w-4" />{{ t('workspaces.new') }}</Button
            >
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            <Card
                v-for="workspace in workspaces.data"
                :key="workspace.id"
                class="cursor-pointer transition-shadow hover:shadow-md"
            >
                <CardHeader>
                    <CardTitle>{{ workspace.name }}</CardTitle>
                    <p class="text-sm text-muted-foreground">
                        {{
                            workspace.description ??
                            t('workspaces.no_description')
                        }}
                    </p>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center gap-4 text-sm text-muted-foreground"
                    >
                        <span class="flex items-center gap-1"
                            ><Users class="h-4 w-4" />{{
                                formatNumber(workspace.members_count ?? 0)
                            }}</span
                        >
                        <span class="flex items-center gap-1"
                            ><Folder class="h-4 w-4" />{{
                                formatNumber(workspace.projects_count ?? 0)
                            }}</span
                        >
                        <span class="flex items-center gap-1"
                            ><CheckSquare class="h-4 w-4" />{{
                                formatNumber(workspace.todos_count ?? 0)
                            }}</span
                        >
                    </div>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="workspaces.data.length === 0"
            class="flex flex-col items-center justify-center py-12 text-muted-foreground"
        >
            <p class="text-lg">{{ t('workspaces.empty') }}</p>
            <Button class="mt-4" @click="showCreateDialog = true"
                ><Plus class="mr-2 h-4 w-4" />{{
                    t('workspaces.create')
                }}</Button
            >
        </div>
    </div>

    <Dialog :open="showCreateDialog" @update:open="showCreateDialog = false">
        <DialogContent class="sm:max-w-md">
            <DialogHeader
                ><DialogTitle>{{
                    t('workspaces.new')
                }}</DialogTitle></DialogHeader
            >
            <form @submit.prevent="createWorkspace" class="space-y-4">
                <div class="space-y-2">
                    <Label for="ws-name">{{ t('workspaces.name') }}</Label>
                    <Input
                        id="ws-name"
                        v-model="form.name"
                        :placeholder="t('workspaces.name_placeholder')"
                        autofocus
                    />
                </div>
                <div class="space-y-2">
                    <Label for="ws-desc">{{
                        t('workspaces.description')
                    }}</Label>
                    <Input
                        id="ws-desc"
                        v-model="form.description"
                        :placeholder="t('workspaces.description_placeholder')"
                    />
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="showCreateDialog = false"
                        >{{ t('common.actions.cancel') }}</Button
                    >
                    <Button type="submit">{{
                        t('common.actions.create')
                    }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
