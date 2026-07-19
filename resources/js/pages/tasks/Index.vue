<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus, Search, Trash2 } from '@lucide/vue';
import { ref, computed } from 'vue';
import TaskCreateDialog from '@/components/task/TaskCreateDialog.vue';
import TaskDetail from '@/components/task/TaskDetail.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import type { Todo, Project } from '@/types/models';

const props = defineProps<{
    todos: PaginatedResponse<Todo>;
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

const allTodos = computed(() => props.todos.data);

function applyFilters() {
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

function toggleComplete(todo: Todo) {
    const target =
        todo.status === 'completed' ? uncomplete(todo) : complete(todo);

    router.post(target.url, {}, { preserveScroll: true });
}

function deleteTodo(todo: Todo) {
    router.delete(destroy(todo).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('tasks.index.deleted'));

            if (selectedTodo.value?.id === todo.id) {
                selectedTodo.value = null;
            }
        },
    });
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
    <Head :title="t('tasks.index.title')" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">
                    {{ t('tasks.index.title') }}
                </h1>
                <p class="text-muted-foreground">
                    {{
                        t('tasks.index.count', {
                            count: formatNumber(todos.total),
                        })
                    }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <Button @click="showCreateDialog = true"
                    ><Plus class="mr-2 h-4 w-4" />{{
                        t('tasks.create.new_task')
                    }}</Button
                >
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    :placeholder="t('tasks.filters.search')"
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-[150px]"
                    ><SelectValue :placeholder="t('tasks.filters.status')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_statuses')
                    }}</SelectItem>
                    <SelectItem value="pending">{{
                        t('tasks.statuses.pending')
                    }}</SelectItem>
                    <SelectItem value="in_progress">{{
                        t('tasks.statuses.in_progress')
                    }}</SelectItem>
                    <SelectItem value="completed">{{
                        t('tasks.statuses.completed')
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="priorityFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-[150px]"
                    ><SelectValue :placeholder="t('tasks.filters.priority')"
                /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">{{
                        t('tasks.filters.all_priorities')
                    }}</SelectItem>
                    <SelectItem value="urgent">{{
                        t('tasks.priorities.urgent')
                    }}</SelectItem>
                    <SelectItem value="high">{{
                        t('tasks.priorities.high')
                    }}</SelectItem>
                    <SelectItem value="medium">{{
                        t('tasks.priorities.medium')
                    }}</SelectItem>
                    <SelectItem value="low">{{
                        t('tasks.priorities.low')
                    }}</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div
            v-if="bulkSelect.hasSelection.value"
            class="flex items-center gap-4 rounded-lg border bg-muted p-3"
        >
            <span class="text-sm">{{
                t('common.states.selected', {
                    count: formatNumber(bulkSelect.selectedCount.value),
                })
            }}</span>
            <Button
                variant="outline"
                size="sm"
                @click="bulkSelect.clearSelection"
                >{{ t('common.actions.cancel') }}</Button
            >
        </div>

        <div class="space-y-2">
            <div
                v-for="todo in allTodos"
                :key="todo.id"
                class="flex cursor-pointer items-center gap-4 rounded-lg border p-4 transition-colors hover:bg-muted/50"
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
                    <div class="mt-1 flex items-center gap-2">
                        <span
                            v-if="todo.project"
                            class="text-xs text-muted-foreground"
                            >{{ todo.project.name }}</span
                        >
                        <span
                            v-if="todo.due_date"
                            class="text-xs text-muted-foreground"
                            >{{ formatDate(todo.due_date) }}</span
                        >
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="priorityBadge(todo.priority)">{{
                        t(`tasks.priorities.${todo.priority}`)
                    }}</Badge>
                    <div class="flex gap-1">
                        <span
                            v-for="label in (todo.labels ?? []).slice(0, 2)"
                            :key="label.id"
                            class="h-2 w-2 rounded-full"
                            :style="{ backgroundColor: label.color }"
                        />
                    </div>
                </div>
                <Button variant="ghost" size="sm" @click.stop="deleteTodo(todo)"
                    ><Trash2 class="h-4 w-4"
                /></Button>
            </div>
        </div>

        <div
            v-if="allTodos.length === 0"
            class="flex flex-col items-center justify-center py-12 text-muted-foreground"
        >
            <p class="text-lg">{{ t('tasks.index.empty_title') }}</p>
            <p class="text-sm">{{ t('tasks.index.empty_description') }}</p>
        </div>
    </div>

    <!-- Task Detail Drawer -->
    <TaskDetail
        v-if="selectedTodo"
        :key="selectedTodo.id"
        :todo="selectedTodo"
        :open="!!selectedTodo"
        @close="selectedTodo = null"
    />

    <!-- Create Task Dialog -->
    <TaskCreateDialog
        :open="showCreateDialog"
        :workspace-id="workspace.id"
        @close="showCreateDialog = false"
        @created="applyFilters"
    />
</template>
