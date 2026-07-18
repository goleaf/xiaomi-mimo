<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Project, Todo } from '@/types/models';
import { useToast } from '@/composables/useToast';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import TaskDetail from '@/components/task/TaskDetail.vue';
import TaskCreateDialog from '@/components/task/TaskCreateDialog.vue';
import { ArrowLeft, Plus, Trash2, Archive, RotateCcw } from '@lucide/vue';

const props = defineProps<{
    project: { data: Project };
    todos: Todo[];
    workspace: { id: string };
}>();

const toast = useToast();
const project = computed(() => props.project.data);
const searchQuery = ref('');
const selectedTodo = ref<Todo | null>(null);
const showCreateDialog = ref(false);

const filteredTodos = computed(() => {
    if (!searchQuery.value) return props.todos;
    return props.todos.filter(t => t.title.toLowerCase().includes(searchQuery.value.toLowerCase()));
});

const pendingTodos = computed(() => filteredTodos.value.filter(t => t.status === 'pending'));
const inProgressTodos = computed(() => filteredTodos.value.filter(t => t.status === 'in_progress'));
const completedTodos = computed(() => filteredTodos.value.filter(t => t.status === 'completed'));

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

function archiveProject() {
    router.post(route('projects.archive', [props.workspace.id, project.value.id]), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Project archived'),
    });
}

function restoreProject() {
    router.post(route('projects.restore', [props.workspace.id, project.value.id]), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Project restored'),
    });
}

function duplicateProject() {
    router.post(route('projects.duplicate', [props.workspace.id, project.value.id]), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('Project duplicated'),
    });
}

function selectTodo(todo: Todo) {
    router.get(route('todos.show', todo.id), {}, {
        preserveState: true, only: ['todo'],
        onSuccess: (page) => { selectedTodo.value = (page.props as Record<string, unknown>).todo as Todo; },
    });
}

const priorityColor = (p: string) => ({ urgent: 'bg-red-500', high: 'bg-orange-500', medium: 'bg-yellow-500', low: 'bg-blue-500', none: 'bg-gray-300' }[p] ?? 'bg-gray-300');
const priorityBadge = (p: string) => ({ urgent: 'destructive', high: 'destructive', medium: 'secondary', low: 'outline', none: 'outline' }[p] ?? 'outline');
const formatDate = (d: string | null) => d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : '';
</script>

<template>
    <Head :title="project.name" />
    <div class="space-y-6 p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" @click="router.visit(route('projects.index', workspace.id))">
                <ArrowLeft class="h-4 w-4 mr-1" />Back
            </Button>
            <div class="h-8 w-8 rounded-lg flex items-center justify-center" :style="{ backgroundColor: project.color + '20' }">
                <div class="h-4 w-4 rounded" :style="{ backgroundColor: project.color }" />
            </div>
            <div class="flex-1">
                <h1 class="text-2xl font-bold">{{ project.name }}</h1>
                <p class="text-sm text-muted-foreground">{{ project.description ?? 'No description' }}</p>
            </div>
            <div class="flex gap-2">
                <Button variant="outline" size="sm" @click="duplicateProject">Duplicate</Button>
                <Button v-if="!project.is_archived" variant="outline" size="sm" @click="archiveProject">
                    <Archive class="h-4 w-4 mr-1" />Archive
                </Button>
                <Button v-else variant="outline" size="sm" @click="restoreProject">
                    <RotateCcw class="h-4 w-4 mr-1" />Restore
                </Button>
                <Button @click="showCreateDialog = true"><Plus class="h-4 w-4 mr-1" />Task</Button>
            </div>
        </div>

        <div class="flex items-center gap-4">
            <Input v-model="searchQuery" placeholder="Search tasks in this project..." class="max-w-sm" />
            <span class="text-sm text-muted-foreground">{{ todos.length }} tasks</span>
        </div>

        <!-- Tasks grouped by status -->
        <div class="space-y-6">
            <div v-for="(group, label) in [{ key: 'in_progress', label: 'In Progress', todos: inProgressTodos }, { key: 'pending', label: 'To Do', todos: pendingTodos }, { key: 'completed', label: 'Done', todos: completedTodos }]" :key="group.key">
                <div v-if="group.todos.length > 0">
                    <h3 class="text-sm font-medium text-muted-foreground mb-3">{{ group.label }} ({{ group.todos.length }})</h3>
                    <div class="space-y-2">
                        <div v-for="todo in group.todos" :key="todo.id"
                            class="flex items-center gap-4 rounded-lg border p-3 hover:bg-muted/50 transition-colors cursor-pointer"
                            @click="selectTodo(todo)">
                            <input type="checkbox" :checked="todo.status === 'completed'" class="h-4 w-4 rounded border-gray-300" @change.stop="toggleComplete(todo)" />
                            <div class="flex-1 min-w-0">
                                <p :class="['text-sm font-medium', todo.status === 'completed' ? 'line-through text-muted-foreground' : '']">{{ todo.title }}</p>
                                <span v-if="todo.due_date" class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</span>
                            </div>
                            <Badge :variant="priorityBadge(todo.priority)">{{ todo.priority }}</Badge>
                            <Button variant="ghost" size="sm" @click.stop="deleteTodo(todo)"><Trash2 class="h-4 w-4" /></Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="todos.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <p>No tasks in this project yet</p>
        </div>
    </div>

    <TaskDetail v-if="selectedTodo" :todo="selectedTodo" :open="!!selectedTodo" @close="selectedTodo = null" />
    <TaskCreateDialog :open="showCreateDialog" :workspace-id="workspace.id" :project-id="project.id" @close="showCreateDialog = false" @created="() => {}" />
</template>
