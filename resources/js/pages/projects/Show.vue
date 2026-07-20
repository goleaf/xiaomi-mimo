<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Archive,
    ArrowLeft,
    CheckCircle2,
    Clock3,
    ListChecks,
    Plus,
    RotateCcw,
    Search,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import TaskCreateDialog from '@/components/task/TaskCreateDialog.vue';
import TaskDetail from '@/components/task/TaskDetail.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { safeDefinitionColor } from '@/composables/useTaskDefinitions';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { projects } from '@/routes';
import { archive, duplicate, restore } from '@/routes/projects';
import { complete, destroy, show, uncomplete } from '@/routes/todos';
import type { Project, TaskDefinitionCatalog, Todo } from '@/types/models';

type ProjectHeaderAction = 'duplicate' | 'archive' | 'restore';

const props = defineProps<{
    project: { data: Project };
    todos: Todo[];
    workspace: { id: string };
    taskDefinitions: TaskDefinitionCatalog;
}>();

const toast = useToast();
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();
const project = computed(() => props.project.data);
const searchQuery = ref('');
const selectedTodo = ref<Todo | null>(null);
const showCreateDialog = ref(false);
const todoToDelete = ref<Todo | null>(null);
const deletingTodo = ref(false);
const processingProjectAction = ref<ProjectHeaderAction | null>(null);

const filteredTodos = computed(() => {
    if (!searchQuery.value) {
        return props.todos;
    }

    return props.todos.filter((todo) =>
        todo.title.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});

const openTodos = computed(() =>
    filteredTodos.value.filter((todo) => !todo.is_completed),
);
const completedTodos = computed(() =>
    filteredTodos.value.filter((todo) => todo.is_completed),
);

function toggleComplete(todo: Todo): void {
    const target = todo.is_completed ? uncomplete : complete;

    router.post(target(todo).url, {}, { preserveScroll: true });
}

function deleteTodo(): void {
    if (!todoToDelete.value) {
        return;
    }

    const todo = todoToDelete.value;
    deletingTodo.value = true;
    router.delete(destroy(todo).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('tasks.detail.deleted'));
            todoToDelete.value = null;

            if (selectedTodo.value?.id === todo.id) {
                selectedTodo.value = null;
            }
        },
        onFinish: () => {
            deletingTodo.value = false;
        },
    });
}

function archiveProject(): void {
    submitProjectAction(
        'archive',
        archive([props.workspace.id, project.value.id]).url,
        t('projects.show.archived'),
    );
}

function restoreProject(): void {
    submitProjectAction(
        'restore',
        restore([props.workspace.id, project.value.id]).url,
        t('projects.show.restored'),
    );
}

function duplicateProject(): void {
    submitProjectAction(
        'duplicate',
        duplicate([props.workspace.id, project.value.id]).url,
        t('projects.show.duplicated'),
    );
}

function submitProjectAction(
    action: ProjectHeaderAction,
    url: string,
    successMessage: string,
): void {
    if (processingProjectAction.value) {
        return;
    }

    processingProjectAction.value = action;
    router.post(
        url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(successMessage),
            onFinish: () => {
                processingProjectAction.value = null;
            },
        },
    );
}

function selectTodo(todo: Todo): void {
    router.get(
        show(todo).url,
        {},
        {
            preserveState: true,
            only: ['todo'],
            onSuccess: (page) => {
                selectedTodo.value = (page.props as Record<string, unknown>)
                    .todo as Todo;
            },
        },
    );
}

function updateSelectedTodo(todo: Todo): void {
    if (selectedTodo.value?.id === todo.id) {
        selectedTodo.value = { ...selectedTodo.value, ...todo };
    }

    router.reload({ only: ['todos'] });
}

function refreshSelectedTodo(): void {
    if (selectedTodo.value) {
        selectTodo(selectedTodo.value);
    }
}

function formatDate(date: string | null): string {
    return date
        ? formatLocalizedDate(date, { month: 'short', day: 'numeric' })
        : '';
}

const taskGroups = computed(() =>
    [...props.taskDefinitions.statuses]
        .sort((left, right) => left.position - right.position)
        .map((status) => ({
            key: status.key,
            label: status.name,
            color: status.color,
            todos: filteredTodos.value.filter(
                (todo) => todo.status === status.key,
            ),
        })),
);
</script>

<template>
    <div>
        <Head :title="project.name" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="
                        t('projects.show.task_count', {
                            count: formatNumber(todos.length),
                        })
                    "
                    :title="project.name"
                    :description="
                        project.description ?? t('projects.show.no_description')
                    "
                >
                    <template #actions>
                        <Button
                            variant="outline"
                            size="lg"
                            :disabled="Boolean(processingProjectAction)"
                            @click="router.visit(projects())"
                        >
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            {{ t('common.actions.back') }}
                        </Button>
                        <Button
                            variant="outline"
                            size="lg"
                            :disabled="Boolean(processingProjectAction)"
                            @click="duplicateProject"
                        >
                            <Spinner
                                v-if="processingProjectAction === 'duplicate'"
                            />
                            {{ t('common.actions.duplicate') }}
                        </Button>
                        <Button
                            v-if="!project.is_archived"
                            variant="outline"
                            size="lg"
                            :disabled="Boolean(processingProjectAction)"
                            @click="archiveProject"
                        >
                            <Spinner
                                v-if="processingProjectAction === 'archive'"
                            />
                            <Archive v-else class="size-4" aria-hidden="true" />
                            {{ t('common.actions.archive') }}
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            size="lg"
                            :disabled="Boolean(processingProjectAction)"
                            @click="restoreProject"
                        >
                            <Spinner
                                v-if="processingProjectAction === 'restore'"
                            />
                            <RotateCcw
                                v-else
                                class="size-4"
                                aria-hidden="true"
                            />
                            {{ t('common.actions.restore') }}
                        </Button>
                        <Button
                            size="lg"
                            :disabled="Boolean(processingProjectAction)"
                            @click="showCreateDialog = true"
                        >
                            <Plus class="size-4" aria-hidden="true" />
                            {{ t('projects.show.task') }}
                        </Button>
                    </template>

                    <template #metrics>
                        <WorkspaceMetric
                            :label="t('tasks.stats.total')"
                            :value="formatNumber(todos.length)"
                            :icon="ListChecks"
                            tone="orange"
                        />
                        <WorkspaceMetric
                            :label="t('tasks.stats.pending')"
                            :value="formatNumber(openTodos.length)"
                            :icon="Clock3"
                            tone="blue"
                        />
                        <WorkspaceMetric
                            :label="t('tasks.stats.completed')"
                            :value="formatNumber(completedTodos.length)"
                            :icon="CheckCircle2"
                            tone="emerald"
                        />
                    </template>
                </WorkspacePageHeader>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-4 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-6"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-border/70 pb-5 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div class="relative w-full max-w-md">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="searchQuery"
                                type="search"
                                :placeholder="t('projects.show.search')"
                                class="pl-10"
                            />
                        </div>
                        <span
                            class="text-sm text-muted-foreground tabular-nums"
                        >
                            {{
                                t('projects.show.task_count', {
                                    count: formatNumber(filteredTodos.length),
                                })
                            }}
                        </span>
                    </div>

                    <div v-if="filteredTodos.length" class="mt-6 space-y-7">
                        <section
                            v-for="group in taskGroups"
                            v-show="group.todos.length > 0"
                            :key="group.key"
                        >
                            <div class="mb-3 flex items-center gap-2">
                                <span
                                    class="size-2 rounded-full"
                                    :style="{
                                        backgroundColor: safeDefinitionColor(
                                            group.color,
                                        ),
                                    }"
                                    aria-hidden="true"
                                />
                                <h2
                                    class="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                >
                                    {{ group.label }} ·
                                    {{ formatNumber(group.todos.length) }}
                                </h2>
                            </div>
                            <div class="space-y-2.5">
                                <div
                                    v-for="todo in group.todos"
                                    :key="todo.id"
                                    class="group relative grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-xl border border-border/80 bg-background p-3.5 transition-[border-color,box-shadow,transform] hover:-translate-y-px hover:border-orange-500/25 hover:shadow-[0_16px_36px_-30px_rgba(234,88,12,0.55)] motion-reduce:transform-none sm:gap-4"
                                >
                                    <button
                                        type="button"
                                        class="absolute inset-0 z-10 cursor-pointer rounded-xl focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                                        :aria-label="todo.title"
                                        @click="selectTodo(todo)"
                                    ></button>
                                    <Checkbox
                                        :model-value="todo.is_completed"
                                        class="relative z-20 size-4.5 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                                        :aria-label="todo.title"
                                        @click.stop
                                        @update:model-value="
                                            toggleComplete(todo)
                                        "
                                    />
                                    <div
                                        class="pointer-events-none relative z-20 min-w-0"
                                    >
                                        <p
                                            :class="[
                                                'truncate text-sm font-medium',
                                                todo.is_completed
                                                    ? 'text-muted-foreground line-through'
                                                    : '',
                                            ]"
                                        >
                                            {{ todo.title }}
                                        </p>
                                        <span
                                            v-if="todo.due_date"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ formatDate(todo.due_date) }}
                                        </span>
                                    </div>
                                    <div
                                        class="pointer-events-none relative z-20 flex items-center gap-1.5"
                                    >
                                        <Badge
                                            class="hidden sm:inline-flex"
                                            variant="outline"
                                            :style="{
                                                borderColor:
                                                    safeDefinitionColor(
                                                        todo.priority_definition
                                                            ?.color,
                                                    ),
                                                color: safeDefinitionColor(
                                                    todo.priority_definition
                                                        ?.color,
                                                ),
                                            }"
                                        >
                                            {{
                                                todo.priority_definition
                                                    ?.name ?? todo.priority
                                            }}
                                        </Badge>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            class="pointer-events-auto text-muted-foreground opacity-70 hover:text-destructive sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100"
                                            :aria-label="
                                                t('common.actions.delete')
                                            "
                                            @click.stop="todoToDelete = todo"
                                        >
                                            <Trash2
                                                class="size-4"
                                                aria-hidden="true"
                                            />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <EmptyState
                        v-else
                        compact
                        :title="t('projects.show.empty')"
                        :description="t('projects.show.empty_description')"
                        :action-label="t('tasks.create.new_task')"
                        @action="showCreateDialog = true"
                    >
                        <template #icon>
                            <ListChecks class="size-7" aria-hidden="true" />
                        </template>
                    </EmptyState>
                </section>
            </div>
        </main>

        <TaskDetail
            v-if="selectedTodo"
            :key="selectedTodo.id"
            :todo="selectedTodo"
            :open="Boolean(selectedTodo)"
            :task-definitions="taskDefinitions"
            @close="selectedTodo = null"
            @refresh="refreshSelectedTodo"
            @updated="updateSelectedTodo"
        />
        <TaskCreateDialog
            :open="showCreateDialog"
            :workspace-id="workspace.id"
            :project-id="project.id"
            :task-definitions="taskDefinitions"
            @close="showCreateDialog = false"
            @created="showCreateDialog = false"
        />
        <WorkspaceConfirmDialog
            :open="todoToDelete !== null"
            :title="t('tasks.index.delete_confirm_title')"
            :description="
                t('tasks.index.delete_confirm_description', {
                    title: todoToDelete?.title ?? '',
                })
            "
            :confirm-label="t('common.actions.delete')"
            :cancel-label="t('common.actions.cancel')"
            :processing="deletingTodo"
            @update:open="!$event && (todoToDelete = null)"
            @confirm="deleteTodo"
        />
    </div>
</template>
