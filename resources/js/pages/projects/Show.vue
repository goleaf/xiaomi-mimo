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
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import TaskCreateDialog from '@/components/task/TaskCreateDialog.vue';
import TaskDetail from '@/components/task/TaskDetail.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { projects } from '@/routes';
import { archive, duplicate, restore } from '@/routes/projects';
import { complete, destroy, show, uncomplete } from '@/routes/todos';
import type { Project, Todo } from '@/types/models';

const props = defineProps<{
    project: { data: Project };
    todos: Todo[];
    workspace: { id: string };
}>();

const toast = useToast();
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();
const project = computed(() => props.project.data);
const searchQuery = ref('');
const selectedTodo = ref<Todo | null>(null);
const showCreateDialog = ref(false);

const filteredTodos = computed(() => {
    if (!searchQuery.value) {
        return props.todos;
    }

    return props.todos.filter((todo) =>
        todo.title.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});

const pendingTodos = computed(() =>
    filteredTodos.value.filter((todo) => todo.status === 'pending'),
);
const inProgressTodos = computed(() =>
    filteredTodos.value.filter((todo) => todo.status === 'in_progress'),
);
const completedTodos = computed(() =>
    filteredTodos.value.filter((todo) => todo.status === 'completed'),
);

function toggleComplete(todo: Todo): void {
    const target = todo.status === 'completed' ? uncomplete : complete;

    router.post(target(todo).url, {}, { preserveScroll: true });
}

function deleteTodo(todo: Todo): void {
    router.delete(destroy(todo).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('tasks.detail.deleted'));

            if (selectedTodo.value?.id === todo.id) {
                selectedTodo.value = null;
            }
        },
    });
}

function archiveProject(): void {
    router.post(
        archive([props.workspace.id, project.value.id]).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('projects.show.archived')),
        },
    );
}

function restoreProject(): void {
    router.post(
        restore([props.workspace.id, project.value.id]).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('projects.show.restored')),
        },
    );
}

function duplicateProject(): void {
    router.post(
        duplicate([props.workspace.id, project.value.id]).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('projects.show.duplicated')),
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

function priorityBadge(
    priority: string,
): 'default' | 'destructive' | 'outline' | 'secondary' {
    const variants: Record<
        string,
        'default' | 'destructive' | 'outline' | 'secondary'
    > = {
        urgent: 'destructive',
        high: 'destructive',
        medium: 'secondary',
        low: 'outline',
        none: 'outline',
    };

    return variants[priority] ?? 'outline';
}

function formatDate(date: string | null): string {
    return date
        ? formatLocalizedDate(date, { month: 'short', day: 'numeric' })
        : '';
}

const taskGroups = computed(() => [
    {
        key: 'in_progress',
        label: t('tasks.statuses.in_progress'),
        todos: inProgressTodos.value,
    },
    {
        key: 'pending',
        label: t('tasks.board.to_do'),
        todos: pendingTodos.value,
    },
    {
        key: 'completed',
        label: t('tasks.board.done'),
        todos: completedTodos.value,
    },
]);
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
                            @click="router.visit(projects())"
                        >
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            {{ t('common.actions.back') }}
                        </Button>
                        <Button variant="outline" @click="duplicateProject">
                            {{ t('common.actions.duplicate') }}
                        </Button>
                        <Button
                            v-if="!project.is_archived"
                            variant="outline"
                            @click="archiveProject"
                        >
                            <Archive class="size-4" aria-hidden="true" />
                            {{ t('common.actions.archive') }}
                        </Button>
                        <Button
                            v-else
                            variant="outline"
                            @click="restoreProject"
                        >
                            <RotateCcw class="size-4" aria-hidden="true" />
                            {{ t('common.actions.restore') }}
                        </Button>
                        <Button @click="showCreateDialog = true">
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
                            :label="t('tasks.stats.in_progress')"
                            :value="formatNumber(inProgressTodos.length)"
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
                                    class="size-2 rounded-full bg-orange-500"
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
                                    class="group grid cursor-pointer grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-xl border border-border/80 bg-background p-3.5 transition-[border-color,box-shadow,transform] hover:-translate-y-px hover:border-orange-500/25 hover:shadow-[0_16px_36px_-30px_rgba(234,88,12,0.55)] motion-reduce:transform-none sm:gap-4"
                                    @click="selectTodo(todo)"
                                >
                                    <input
                                        type="checkbox"
                                        :checked="todo.status === 'completed'"
                                        class="size-4 rounded border-gray-300 accent-orange-600"
                                        :aria-label="todo.title"
                                        @change.stop="toggleComplete(todo)"
                                    />
                                    <div class="min-w-0">
                                        <p
                                            :class="[
                                                'truncate text-sm font-medium',
                                                todo.status === 'completed'
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
                                    <div class="flex items-center gap-1.5">
                                        <Badge
                                            class="hidden sm:inline-flex"
                                            :variant="
                                                priorityBadge(todo.priority)
                                            "
                                        >
                                            {{
                                                t(
                                                    `tasks.priorities.${todo.priority}`,
                                                )
                                            }}
                                        </Badge>
                                        <Button
                                            variant="ghost"
                                            size="icon-sm"
                                            class="text-muted-foreground opacity-70 hover:text-destructive sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100"
                                            :aria-label="
                                                t('common.actions.delete')
                                            "
                                            @click.stop="deleteTodo(todo)"
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

                    <div
                        v-else
                        class="flex min-h-72 flex-col items-center justify-center px-6 text-center"
                    >
                        <div
                            class="flex size-16 items-center justify-center rounded-2xl bg-orange-500/[0.08] text-orange-700 dark:text-orange-300"
                        >
                            <ListChecks class="size-7" aria-hidden="true" />
                        </div>
                        <p class="mt-5 text-lg font-semibold">
                            {{ t('projects.show.empty') }}
                        </p>
                    </div>
                </section>
            </div>
        </main>

        <TaskDetail
            v-if="selectedTodo"
            :key="selectedTodo.id"
            :todo="selectedTodo"
            :open="Boolean(selectedTodo)"
            @close="selectedTodo = null"
        />
        <TaskCreateDialog
            :open="showCreateDialog"
            :workspace-id="workspace.id"
            :project-id="project.id"
            @close="showCreateDialog = false"
            @created="showCreateDialog = false"
        />
    </div>
</template>
