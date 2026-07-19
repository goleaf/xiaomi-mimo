import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import type { Workspace } from '@/types/models';

export const useWorkspaceStore = defineStore('workspace', () => {
    const workspaces = ref<Workspace[]>([]);
    const currentWorkspaceId = ref<string | null>(null);
    const loading = ref(false);

    const currentWorkspace = computed(
        () =>
            workspaces.value.find((w) => w.id === currentWorkspaceId.value) ??
            workspaces.value[0],
    );

    function setWorkspaces(data: Workspace[]) {
        workspaces.value = data;

        if (!currentWorkspaceId.value && data.length > 0) {
            currentWorkspaceId.value = data[0].id;
        }
    }

    function switchWorkspace(id: string) {
        currentWorkspaceId.value = id;
    }

    function addWorkspace(workspace: Workspace) {
        workspaces.value.push(workspace);
    }

    function updateWorkspace(id: string, data: Partial<Workspace>) {
        const index = workspaces.value.findIndex((w) => w.id === id);

        if (index !== -1) {
            workspaces.value[index] = { ...workspaces.value[index], ...data };
        }
    }

    function removeWorkspace(id: string) {
        workspaces.value = workspaces.value.filter((w) => w.id !== id);

        if (currentWorkspaceId.value === id) {
            currentWorkspaceId.value = workspaces.value[0]?.id ?? null;
        }
    }

    return {
        workspaces,
        currentWorkspaceId,
        currentWorkspace,
        loading,
        setWorkspaces,
        switchWorkspace,
        addWorkspace,
        updateWorkspace,
        removeWorkspace,
    };
});
