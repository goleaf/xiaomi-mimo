<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useBulkSelect } from '@/composables/useBulkSelect';
import { useToast } from '@/composables/useToast';
import type { Todo, Project } from '@/types/models';
import type { PaginatedResponse } from '@/types/api';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import TaskDetail from '@/components/task/TaskDetail.vue';
import TaskCreateDialog from '@/components/task/TaskCreateDialog.vue';
import { Plus, Search, Trash2, LayoutGrid, List, Calendar } from '@lucide/vue';

const props = defineProps<{
    todos: PaginatedResponse<Todo>;
    filters: Record<string, string>;
    projects: { data: Project[] };
    workspace: { id: string };
}>();

const bulkSelect = useBulkSelect<Todo>();
const toast = useToast();
const searchQuery = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const priorityFilter = ref(props.filters.priority ?? '');
const viewMode = ref<'list' | 'board'>('list');
const selectedTodo = ref<Todo | null>(null);
const showCreateDialog = ref(false);

const allTodos = computed(() => props.todos.data);

function applyFilters() {
    router.get(route('todos.index'), {
        search: searchQuery.value || undefined,
        status: statusFilter.value || undefined,
        priority: priorityFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function toggleComplete(todo: Todo) {
    const routeName = todo.status === 'completed' ? 'todos.uncomplete' : 'todos.complete';
    router.post(route(routeName, todo.id), {}, { preserveScroll: true });
}

function deleteTodo(todo: Todo) {
    router.delete(route('todos.destroy', todo.id), {
        preserveScroll: true,
        onSuccess: () => { toast.success('Task deleted'); if (selectedTodo.value?.id === todo.id) selectedTodo.value = null; },
    });
}

function selectTodo(todo: Todo) {
    router.get(route('todos.show', todo.id), {}, {
        preserveState: true,
        only: ['todo'],
        onSuccess: (page) => { selectedTodo.value = (page.props as Record<string, unknown>).todo as Todo; },
    });
}

function priorityBadge(priority: string) {
    return { urgent: 'destructive', high: 'destructive', medium: 'secondary', low: 'outline', none: 'outline' }[priority] ?? 'outline';
}

function priorityColor(priority: string): string {
    return { urgent: '#ef4444', high: '#f97316', medium: '#eab308', low: '#3b82f6', none: '#9ca3af' }[priority] ?? '#9ca3af';
}

function formatDate(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Tasks" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Tasks</h1>
                <p class="text-muted-foreground">{{ todos.total }} tasks</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="flex border rounded-md">
                    <button :class="['px-2 py-1', viewMode === 'list' ? 'bg-muted' : '']" @click="viewMode = 'list'"><List class="h-4 w-4" /></button>
                    <button :class="['px-2 py-1', viewMode === 'board' ? 'bg-muted' : '']" @click="viewMode = 'board'"><LayoutGrid class="h-4 w-4" /></button>
                </div>
                <Button @click="showCreateDialog = true"><Plus class="mr-2 h-4 w-4" />New Task</Button>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <div class="relative flex-1 max-w-sm">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="searchQuery" placeholder="Search tasks..." class="pl-9" @keyup.enter="applyFilters" />
            </div>
            <Select v-model="statusFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-[150px]"><SelectValue placeholder="Status" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All Status</SelectItem>
                    <SelectItem value="pending">Pending</SelectItem>
                    <SelectItem value="in_progress">In Progress</SelectItem>
                    <SelectItem value="completed">Completed</SelectItem>
                </SelectContent>
            </Select>
            <Select v-model="priorityFilter" @update:model-value="applyFilters">
                <SelectTrigger class="w-[150px]"><SelectValue placeholder="Priority" /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All Priority</SelectItem>
                    <SelectItem value="urgent">Urgent</SelectItem>
                    <SelectItem value="high">High</SelectItem>
                    <SelectItem value="medium">Medium</SelectItem>
                    <SelectItem value="low">Low</SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="bulkSelect.hasSelection.value" class="flex items-center gap-4 rounded-lg border bg-muted p-3">
            <span class="text-sm">{{ bulkSelect.selectedCount.value }} selected</span>
            <Button variant="outline" size="sm" @click="bulkSelect.clearSelection">Cancel</Button>
        </div>

        <!-- List View -->
        <div v-if="viewMode === 'list'" class="space-y-2">
            <div v-for="todo in allTodos" :key="todo.id"
                class="flex items-center gap-4 rounded-lg border p-4 hover:bg-muted/50 transition-colors cursor-pointer"
                @click="selectTodo(todo)">
                <input type="checkbox" :checked="todo.status === 'completed'" class="h-4 w-4 rounded border-gray-300" @change.stop="toggleComplete(todo)" />
                <div class="flex-1 min-w-0">
                    <p :class="['text-sm font-medium', todo.status === 'completed' ? 'line-through text-muted-foreground' : '']">{{ todo.title }}</p>
                    <div class="flex items-center gap-2 mt-1">
                        <span v-if="todo.project" class="text-xs text-muted-foreground">{{ todo.project.name }}</span>
                        <span v-if="todo.due_date" class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Badge :variant="priorityBadge(todo.priority)">{{ todo.priority }}</Badge>
                    <div class="flex gap-1">
                        <span v-for="label in (todo.labels ?? []).slice(0, 2)" :key="label.id" class="h-2 w-2 rounded-full" :style="{ backgroundColor: label.color }" />
                    </div>
                </div>
                <Button variant="ghost" size="sm" @click.stop="deleteTodo(todo)"><Trash2 class="h-4 w-4" /></Button>
            </div>
        </div>

        <div v-if="allTodos.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <p class="text-lg">No tasks found</p>
            <p class="text-sm">Create a new task to get started</p>
        </div>
    </div>

    <!-- Task Detail Drawer -->
    <TaskDetail v-if="selectedTodo" :todo="selectedTodo" :open="!!selectedTodo" @close="selectedTodo = null" />

    <!-- Create Task Dialog -->
    <TaskCreateDialog :open="showCreateDialog" :workspace-id="workspace.id" @close="showCreateDialog = false" @created="applyFilters" />
</template>
