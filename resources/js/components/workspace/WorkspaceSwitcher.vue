<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown, Check, Plus, Building2 } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Workspace } from '@/types/models';

const props = defineProps<{
    workspaces: Workspace[];
    currentWorkspace: Workspace | null;
}>();

const switching = ref(false);

function switchWorkspace(workspace: Workspace) {
    if (workspace.id === props.currentWorkspace?.id) {
return;
}

    switching.value = true;
    router.post(
        route('workspaces.switch', workspace.id),
        {},
        {
            onFinish: () => {
                switching.value = false;
            },
        },
    );
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                variant="ghost"
                class="h-9 w-full justify-between px-2"
                :disabled="switching"
            >
                <div class="flex min-w-0 items-center gap-2">
                    <Building2 class="h-4 w-4 shrink-0 text-muted-foreground" />
                    <span class="truncate text-sm">{{
                        currentWorkspace?.name ?? 'Select workspace'
                    }}</span>
                </div>
                <ChevronDown class="h-4 w-4 shrink-0 text-muted-foreground" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="w-56" align="start">
            <DropdownMenuItem
                v-for="ws in workspaces"
                :key="ws.id"
                class="cursor-pointer"
                @click="switchWorkspace(ws)"
            >
                <Check
                    v-if="ws.id === currentWorkspace?.id"
                    class="mr-2 h-4 w-4"
                />
                <span v-else class="mr-2 h-4 w-4" />
                {{ ws.name }}
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
