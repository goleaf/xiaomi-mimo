<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, Trash2, User } from '@lucide/vue';
import { ref } from 'vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import TaskAttachmentsPanel from '@/components/task/TaskAttachmentsPanel.vue';
import TaskChecklistPanel from '@/components/task/TaskChecklistPanel.vue';
import TaskCommentsPanel from '@/components/task/TaskCommentsPanel.vue';
import TaskOverviewPanel from '@/components/task/TaskOverviewPanel.vue';
import TaskRemindersPanel from '@/components/task/TaskRemindersPanel.vue';
import TaskTaxonomyPanel from '@/components/task/TaskTaxonomyPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { safeDefinitionColor } from '@/composables/useTaskDefinitions';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { complete, destroy, uncomplete } from '@/routes/api/v1/tasks';
import type { TaskDefinitionCatalog, Todo } from '@/types/models';

const props = defineProps<{
    todo: Todo;
    taskDefinitions: TaskDefinitionCatalog;
}>();
const emit = defineEmits<{
    deleted: [];
    refresh: [];
    updated: [todo: Todo];
}>();
const toast = useToast();
const { formatDate, t } = useUi();
const showDeleteDialog = ref(false);
const completionRequest = useHttp<Record<string, never>, { data: Todo }>({});
const deleteRequest = useHttp<Record<string, never>, undefined>({});

async function toggleComplete(): Promise<void> {
    if (completionRequest.processing) {
        return;
    }

    const target = props.todo.is_completed ? uncomplete : complete;

    try {
        const response = await completionRequest.post(
            target([props.todo.workspace_id, props.todo]).url,
        );
        emit('updated', response.data);
    } catch {
        toast.error(t('common.errors.generic'));
    }
}

async function deleteTodo(): Promise<void> {
    if (deleteRequest.processing) {
        return;
    }

    try {
        await deleteRequest.delete(
            destroy([props.todo.workspace_id, props.todo]).url,
        );
        showDeleteDialog.value = false;
        toast.success(t('tasks.detail.deleted'));
        emit('deleted');
    } catch {
        toast.error(t('common.errors.generic'));
    }
}
</script>

<template>
    <div class="space-y-5">
        <section
            class="rounded-[1.5rem] border border-border/80 bg-card p-5 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)]"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="outline"
                            :style="{
                                borderColor: safeDefinitionColor(
                                    todo.status_definition?.color,
                                ),
                            }"
                        >
                            {{ todo.status_definition?.name ?? todo.status }}
                        </Badge>
                        <Badge
                            variant="outline"
                            :style="{
                                borderColor: safeDefinitionColor(
                                    todo.priority_definition?.color,
                                ),
                            }"
                        >
                            {{
                                todo.priority_definition?.name ?? todo.priority
                            }}
                        </Badge>
                        <Badge v-if="todo.is_recurring" variant="secondary">
                            {{ t('tasks.detail.recurring') }}
                        </Badge>
                    </div>
                    <h1 class="mt-3 text-xl font-semibold tracking-tight">
                        {{ todo.title }}
                    </h1>
                    <div
                        class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground"
                    >
                        <span class="flex items-center gap-2">
                            <Calendar class="size-4" aria-hidden="true" />
                            {{
                                todo.due_date
                                    ? formatDate(todo.due_date, {
                                          dateStyle: 'medium',
                                      })
                                    : t('common.states.not_set')
                            }}
                        </span>
                        <span class="flex items-center gap-2">
                            <User class="size-4" aria-hidden="true" />
                            {{
                                todo.assignee?.name ??
                                t('common.states.unassigned')
                            }}
                        </span>
                    </div>
                </div>
                <Button
                    size="lg"
                    :variant="todo.is_completed ? 'outline' : 'default'"
                    :disabled="completionRequest.processing"
                    @click="toggleComplete"
                >
                    <Spinner v-if="completionRequest.processing" />
                    <CheckCircle2 v-else class="size-4" aria-hidden="true" />
                    {{
                        todo.is_completed
                            ? t('common.actions.reopen')
                            : t('common.actions.complete')
                    }}
                </Button>
            </div>
        </section>

        <TaskOverviewPanel
            :todo="todo"
            :task-definitions="taskDefinitions"
            @updated="emit('updated', $event)"
        />
        <TaskTaxonomyPanel
            :todo="todo"
            :available-labels="todo.available_labels ?? []"
            :available-tags="todo.available_tags ?? []"
            @refresh="emit('refresh')"
        />
        <TaskChecklistPanel
            :todo-id="todo.id"
            :checklists="todo.checklists ?? []"
            @refresh="emit('refresh')"
        />
        <TaskCommentsPanel
            :todo-id="todo.id"
            :initial-comments="todo.comments ?? []"
            :total="todo.comments_count ?? todo.comments?.length ?? 0"
        />
        <TaskRemindersPanel
            :todo-id="todo.id"
            :initial-reminders="todo.reminders ?? []"
        />
        <TaskAttachmentsPanel
            :todo-id="todo.id"
            :initial-attachments="todo.attachments ?? []"
        />

        <section
            class="rounded-[1.5rem] border border-destructive/20 bg-destructive/[0.04] p-5"
        >
            <Button
                variant="destructive"
                size="lg"
                :disabled="deleteRequest.processing"
                @click="showDeleteDialog = true"
            >
                <Trash2 class="size-4" aria-hidden="true" />
                {{ t('tasks.detail.delete') }}
            </Button>
        </section>
    </div>

    <WorkspaceConfirmDialog
        :open="showDeleteDialog"
        :title="t('tasks.detail.delete_confirm_title')"
        :description="t('tasks.detail.delete_confirm_description')"
        :confirm-label="t('tasks.detail.delete')"
        :cancel-label="t('common.actions.cancel')"
        :processing="deleteRequest.processing"
        @update:open="showDeleteDialog = $event"
        @confirm="deleteTodo"
    >
        <template #icon>
            <Trash2 class="size-5" aria-hidden="true" />
        </template>
    </WorkspaceConfirmDialog>
</template>
