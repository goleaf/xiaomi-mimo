<script setup lang="ts">
import { computed } from 'vue';
import WorkspaceDefinitionCard from '@/components/workspace/WorkspaceDefinitionCard.vue';
import type {
    TaskPriorityDefinition,
    TaskStatusDefinition,
    Workspace,
    WorkspaceMetadataRouteUrls,
} from '@/types/models';

const props = defineProps<{
    workspace: Workspace;
    statuses: TaskStatusDefinition[];
    priorities: TaskPriorityDefinition[];
    search: string;
    locale: string;
    routes: WorkspaceMetadataRouteUrls;
}>();

const canManage = computed(
    () => props.workspace.permissions?.manage_task_configuration === true,
);
</script>

<template>
    <div class="grid items-start gap-6 xl:grid-cols-2">
        <WorkspaceDefinitionCard
            kind="status"
            :definitions="statuses"
            :search="search"
            :locale="locale"
            :can-manage="canManage"
            :store-url="routes.storeStatus"
            :update-url="routes.updateStatus"
            :manage-url="routes.manageStatus"
            :delete-url="routes.deleteStatus"
            :reorder-url="routes.reorderStatuses"
            :reload-props="['workspace', 'taskStatuses']"
        />
        <WorkspaceDefinitionCard
            kind="priority"
            :definitions="priorities"
            :search="search"
            :locale="locale"
            :can-manage="canManage"
            :store-url="routes.storePriority"
            :update-url="routes.updatePriority"
            :manage-url="routes.managePriority"
            :delete-url="routes.deletePriority"
            :reorder-url="routes.reorderPriorities"
            :reload-props="['workspace', 'taskPriorities']"
        />
    </div>
</template>
