<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Clock3,
    ListChecks,
    Plus,
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useBulkSelect } from '@/composables/useBulkSelect';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import {
    complete,
    destroy,
    index as tasksIndex,
    show,
    uncomplete,
} from '@/routes/todos';
import type { PaginatedResponse } from '@/types/api';
import type { Project, Todo } from '@/types/models';

const props = defineProps<{
    todos: PaginatedResponse<Todo> & { meta?: { total: number } };
    filters: Record<string, string>;
    projects: { data: Project[] };
    workspace: { id: string };
}>();

const bulkSelect = useBulkSelect<Todo>();
const toast = useToast();
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();
const searchQuery = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');
const priorityFilter = ref(props.filters.priority ?? 'all');
const selectedTodo = ref<Todo | null>(null);
const showCreateDialog = ref(false);
const todoToDelete = ref<Todo | null>(null);
const deletingTodo = ref(false);

const allTodos = computed(() => props.todos.data);
const totalCount = computed(
    () => props.todos.meta?.total ?? props.todos.total ?? allTodos.value.length,
);
const pendingCount = computed(
    () => allTodos.value.filter((todo) => todo.status === 'pending').length,
);
const completedCount = computed(
    () => allTodos.value.filter((todo) => todo.status === 'completed').length,
);

function applyFilters(): void {
    router.get(
        tasksIndex.url(),
        {
            search: searchQuery.value || undefined,
            status:
                statusFilter.value === 'all' ? undefined : statusFilter.value,
            priority:
                priorityFilter.value === 'all'
                    ? undefined
                    : priorityFilter.value,
        },
        { preserveState: true, replace: true },
    );
}

function toggleComplete(todo: Todo): void {
    const target =
        todo.status === 'completed' ? uncomplete(todo) : complete(todo);

    router.post(target.url, {}, { preserveScroll: true });
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
            toast.success(t('tasks.index.deleted'));
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
): 'destructive' | 'outline' | 'secondary' {
    if (priority === 'urgent' || priority === 'high') {
        return 'destructive';
    }

    if (priority === 'medium') {
        return 'secondary';
    }

    return 'outline';
}

function formatDate(date: string | null): string {
    if (!date) {
        return '';
    }

    return formatLocalizedDate(date, {
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <div>
        <Head :title="t('tasks.index.title')" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="t('tasks.board.to_do')"
                    :title="t('tasks.index.title')"
                    :description="
                        t('tasks.index.count', {
                            count: formatNumber(totalCount),
                        })
                    "
                >
                    <template #actions>
                        <Button size="lg" @click="showCreateDialog = true">
                            <Plus class="size-4" aria-hidden="true" />
                            {{ t('tasks.create.new_task') }}
                        </Button>
                    </template>

                    <template #metrics>
                        <WorkspaceMetric
                            :label="t('tasks.stats.total')"
                            :value="formatNumber(totalCount)"
                            :icon="ListChecks"
                            tone="orange"
                        />
                        <WorkspaceMetric
                            :label="t('tasks.stats.pending')"
                            :value="formatNumber(pendingCount)"
                            :icon="Clock3"
                            tone="blue"
                        />
                        <WorkspaceMetric
                            :label="t('tasks.stats.completed')"
                            :value="formatNumber(completedCount)"
                            :icon="CheckCircle2"
                            tone="emerald"
                        />
                    </template>
                </WorkspacePageHeader>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-4 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-6"
                >
                    <div
                        class="grid gap-3 border-b border-border/70 pb-5 sm:grid-cols-2 lg:grid-cols-[minmax(16rem,1fr)_11rem_11rem]"
                    >
                        <div class="relative sm:col-span-2 lg:col-span-1">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                v-model="searchQuery"
                                type="search"
                                :placeholder="t('tasks.filters.search')"
                                class="pl-10"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                        <Select
                            v-model="statusFilter"
                            @update:model-value="applyFilters"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="t('tasks.filters.status')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {{ t('tasks.filters.all_statuses') }}
                                </SelectItem>
                                <SelectItem value="pending">
                                    {{ t('tasks.statuses.pending') }}
                                </SelectItem>
                                <SelectItem value="in_progress">
                                    {{ t('tasks.statuses.in_progress') }}
                                </SelectItem>
                                <SelectItem value="completed">
                                    {{ t('tasks.statuses.completed') }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <Select
                            v-model="priorityFilter"
                            @update:model-value="applyFilters"
                        >
                            <SelectTrigger class="w-full">
                                <SelectValue
                                    :placeholder="t('tasks.filters.priority')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    {{ t('tasks.filters.all_priorities') }}
                                </SelectItem>
                                <SelectItem value="urgent">
                                    {{ t('tasks.priorities.urgent') }}
                                </SelectItem>
                                <SelectItem value="high">
                                    {{ t('tasks.priorities.high') }}
                                </SelectItem>
                                <SelectItem value="medium">
                                    {{ t('tasks.priorities.medium') }}
                                </SelectItem>
                                <SelectItem value="low">
                                    {{ t('tasks.priorities.low') }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div
                        v-if="bulkSelect.hasSelection.value"
                        class="mt-4 flex flex-wrap items-center gap-3 rounded-xl border border-orange-500/15 bg-orange-500/[0.06] p-3"
                    >
                        <span class="text-sm">
                            {{
                                t('common.states.selected', {
                                    count: formatNumber(
                                        bulkSelect.selectedCount.value,
                                    ),
                                })
                            }}
                        </span>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="bulkSelect.clearSelection"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                    </div>

                    <div v-if="allTodos.length" class="mt-5 space-y-2.5">
                        <div
                            v-for="todo in allTodos"
                            :key="todo.id"
                            class="group relative grid grid-cols-[auto_minmax(0,1fr)_auto] items-center gap-3 rounded-xl border border-border/80 bg-background p-3.5 transition-[border-color,box-shadow,transform] hover:-translate-y-px hover:border-orange-500/25 hover:shadow-[0_16px_36px_-30px_rgba(234,88,12,0.55)] motion-reduce:transform-none sm:gap-4 sm:p-4"
                        >
                            <button
                                type="button"
                                class="absolute inset-0 z-10 cursor-pointer rounded-xl focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                                :aria-label="todo.title"
                                @click="selectTodo(todo)"
                            ></button>
                            <Checkbox
                                :model-value="todo.status === 'completed'"
                                class="relative z-20 size-4.5 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                                :aria-label="todo.title"
                                @click.stop
                                @update:model-value="toggleComplete(todo)"
                            />
                            <div
                                class="pointer-events-none relative z-20 min-w-0"
                            >
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
                                <div
                                    class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1"
                                >
                                    <span
                                        v-if="todo.project"
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ todo.project.name }}
                                    </span>
                                    <span
                                        v-if="todo.due_date"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ formatDate(todo.due_date) }}
                                    </span>
                                </div>
                            </div>
                            <div
                                class="pointer-events-none relative z-20 flex items-center gap-1.5 sm:gap-2"
                            >
                                <Badge
                                    class="hidden sm:inline-flex"
                                    :variant="priorityBadge(todo.priority)"
                                >
                                    {{ t(`tasks.priorities.${todo.priority}`) }}
                                </Badge>
                                <div class="hidden gap-1 md:flex">
                                    <span
                                        v-for="label in (
                                            todo.labels ?? []
                                        ).slice(0, 2)"
                                        :key="label.id"
                                        class="size-2 rounded-full"
                                        :style="{
                                            backgroundColor: label.color,
                                        }"
                                    />
                                </div>
                                <Button
                                    variant="ghost"
                                    size="icon-sm"
                                    class="pointer-events-auto text-muted-foreground opacity-70 hover:text-destructive sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100"
                                    :aria-label="t('common.actions.delete')"
                                    @click.stop="todoToDelete = todo"
                                >
                                    <Trash2 class="size-4" aria-hidden="true" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    <EmptyState
                        v-else
                        compact
                        :title="t('tasks.index.empty_title')"
                        :description="t('tasks.index.empty_description')"
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
            @close="selectedTodo = null"
        />

        <TaskCreateDialog
            :open="showCreateDialog"
            :workspace-id="workspace.id"
            @close="showCreateDialog = false"
            @created="applyFilters"
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
