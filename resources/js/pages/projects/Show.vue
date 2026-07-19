<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2, Archive, RotateCcw } from '@lucide/vue';
import { ref, computed } from 'vue';
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

    return props.todos.filter((t) =>
        t.title.toLowerCase().includes(searchQuery.value.toLowerCase()),
    );
});

const pendingTodos = computed(() =>
    filteredTodos.value.filter((t) => t.status === 'pending'),
);
const inProgressTodos = computed(() =>
    filteredTodos.value.filter((t) => t.status === 'in_progress'),
);
const completedTodos = computed(() =>
    filteredTodos.value.filter((t) => t.status === 'completed'),
);

function toggleComplete(todo: Todo) {
    const target = todo.status === 'completed' ? uncomplete : complete;

    router.post(target(todo).url, {}, { preserveScroll: true });
}

function deleteTodo(todo: Todo) {
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

function archiveProject() {
    router.post(
        archive([props.workspace.id, project.value.id]).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('projects.show.archived')),
        },
    );
}

function restoreProject() {
    router.post(
        restore([props.workspace.id, project.value.id]).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('projects.show.restored')),
        },
    );
}

function duplicateProject() {
    router.post(
        duplicate([props.workspace.id, project.value.id]).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => toast.success(t('projects.show.duplicated')),
        },
    );
}

function selectTodo(todo: Todo) {
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

const priorityBadge = (
    p: string,
): 'default' | 'destructive' | 'outline' | 'secondary' =>
    ({
        urgent: 'destructive',
        high: 'destructive',
        medium: 'secondary',
        low: 'outline',
        none: 'outline',
    })[p] ?? 'outline';
const formatDate = (date: string | null) =>
    date ? formatLocalizedDate(date, { month: 'short', day: 'numeric' }) : '';

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
    <Head :title="project.name" />
    <div class="space-y-6 p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" @click="router.visit(projects())">
                <ArrowLeft class="mr-1 h-4 w-4" />{{ t('common.actions.back') }}
            </Button>
            <div
                class="flex h-8 w-8 items-center justify-center rounded-lg"
                :style="{ backgroundColor: project.color + '20' }"
            >
                <div
                    class="h-4 w-4 rounded"
                    :style="{ backgroundColor: project.color }"
                />
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold">{{ project.name }}</h1>
                <p class="text-sm text-muted-foreground">
                    {{
                        project.description ?? t('projects.show.no_description')
                    }}
                </p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="duplicateProject">{{
                    t('common.actions.duplicate')
                }}</Button>
                <Button
                    v-if="!project.is_archived"
                    variant="outline"
                    size="sm"
                    @click="archiveProject"
                >
                    <Archive class="mr-1 h-4 w-4" />{{
                        t('common.actions.archive')
                    }}
                </Button>
                <Button
                    v-else
                    variant="outline"
                    size="sm"
                    @click="restoreProject"
                >
                    <RotateCcw class="mr-1 h-4 w-4" />{{
                        t('common.actions.restore')
                    }}
                </Button>
                <Button @click="showCreateDialog = true"
                    ><Plus class="mr-1 h-4 w-4" />{{
                        t('projects.show.task')
                    }}</Button
                >
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Input
                v-model="searchQuery"
                :placeholder="t('projects.show.search')"
                class="max-w-sm"
            />
            <span class="text-sm text-muted-foreground">{{
                t('projects.show.task_count', {
                    count: formatNumber(todos.length),
                })
            }}</span>
        </div>

        <!-- Tasks grouped by status -->
        <div class="space-y-6">
            <div v-for="group in taskGroups" :key="group.key">
                <div v-if="group.todos.length > 0">
                    <h3 class="mb-3 text-sm font-medium text-muted-foreground">
                        {{ group.label }} ({{
                            formatNumber(group.todos.length)
                        }})
                    </h3>
                    <div class="space-y-2">
                        <div
                            v-for="todo in group.todos"
                            :key="todo.id"
                            class="flex cursor-pointer items-center gap-4 rounded-lg border p-3 transition-colors hover:bg-muted/50"
                            @click="selectTodo(todo)"
                        >
                            <input
                                type="checkbox"
                                :checked="todo.status === 'completed'"
                                class="h-4 w-4 rounded border-gray-300"
                                @change.stop="toggleComplete(todo)"
                            />
                            <div class="min-w-0 flex-1">
                                <p
                                    :class="[
                                        'text-sm font-medium',
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
                                    >{{ formatDate(todo.due_date) }}</span
                                >
                            </div>
                            <Badge :variant="priorityBadge(todo.priority)">{{
                                t(`tasks.priorities.${todo.priority}`)
                            }}</Badge>
                            <Button
                                variant="ghost"
                                size="sm"
                                @click.stop="deleteTodo(todo)"
                                ><Trash2 class="h-4 w-4"
                            /></Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="todos.length === 0"
            class="flex flex-col items-center justify-center py-12 text-muted-foreground"
        >
            <p>{{ t('projects.show.empty') }}</p>
        </div>
    </div>

    <TaskDetail
        v-if="selectedTodo"
        :key="selectedTodo.id"
        :todo="selectedTodo"
        :open="!!selectedTodo"
        @close="selectedTodo = null"
    />
    <TaskCreateDialog
        :open="showCreateDialog"
        :workspace-id="workspace.id"
        :project-id="project.id"
        @close="showCreateDialog = false"
        @created="() => {}"
    />
</template>
