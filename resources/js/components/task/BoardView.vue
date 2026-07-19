<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Todo, TodoStatus } from '@/types/models';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { useToast } from '@/composables/useToast';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';

const props = defineProps<{ todos: Todo[] }>();
const emit = defineEmits<{ select: [todo: Todo] }>();
const toast = useToast();

const columns: Array<{ status: TodoStatus; label: string; color: string }> = [
    { status: 'pending', label: 'To Do', color: '#6b7280' },
    { status: 'in_progress', label: 'In Progress', color: '#3b82f6' },
    { status: 'completed', label: 'Done', color: '#22c55e' },
];

function getTodosByStatus(status: TodoStatus): Todo[] {
    return props.todos.filter((t) => t.status === status);
}

function priorityColor(priority: string): string {
    return { urgent: 'bg-red-500', high: 'bg-orange-500', medium: 'bg-yellow-500', low: 'bg-blue-500', none: 'bg-gray-300' }[priority] ?? 'bg-gray-300';
}

function formatDate(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

function moveTodo(todoId: string, newStatus: TodoStatus) {
    const todo = props.todos.find((t) => t.id === todoId);
    if (!todo || todo.status === newStatus) return;

    if (newStatus === 'completed') {
        router.post(route('todos.complete', todoId), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Task completed'),
        });
    } else if (newStatus === 'pending' || newStatus === 'in_progress') {
        router.put(route('todos.update', todoId), { status: newStatus }, {
            preserveScroll: true,
        });
    }
}

function onDragStart(event: DragEvent, todo: Todo) {
    event.dataTransfer?.setData('todo-id', todo.id);
    event.dataTransfer?.setData('todo-status', todo.status);
    (event.target as HTMLElement).style.opacity = '0.5';
}

function onDragEnd(event: DragEvent) {
    (event.target as HTMLElement).style.opacity = '1';
}

function onDragOver(event: DragEvent) {
    event.preventDefault();
}

function onDrop(event: DragEvent, targetStatus: TodoStatus) {
    event.preventDefault();
    const todoId = event.dataTransfer?.getData('todo-id');
    const sourceStatus = event.dataTransfer?.getData('todo-status');
    if (todoId && sourceStatus !== targetStatus) {
        moveTodo(todoId, targetStatus);
    }
}

function onDragEnter(event: DragEvent) {
    event.preventDefault();
    (event.currentTarget as HTMLElement)?.classList?.add('ring-2', 'ring-primary/50');
}

function onDragLeave(event: DragEvent) {
    (event.currentTarget as HTMLElement)?.classList?.remove('ring-2', 'ring-primary/50');
}
</script>

<template>
    <div class="grid grid-cols-3 gap-4 min-h-[500px]">
        <div
            v-for="column in columns"
            :key="column.status"
            class="bg-muted/30 rounded-lg p-3 transition-all"
            @dragover="onDragOver"
            @drop="onDrop($event, column.status)"
            @dragenter="onDragEnter"
            @dragleave="onDragLeave"
        >
            <div class="flex items-center gap-2 mb-3">
                <div class="h-2 w-2 rounded-full" :style="{ backgroundColor: column.color }" />
                <h3 class="text-sm font-medium">{{ column.label }}</h3>
                <Badge variant="secondary" class="ml-auto text-xs">{{ getTodosByStatus(column.status).length }}</Badge>
            </div>
            <div class="space-y-2">
                <Card
                    v-for="todo in getTodosByStatus(column.status)"
                    :key="todo.id"
                    class="cursor-grab active:cursor-grabbing hover:shadow-md transition-all"
                    draggable="true"
                    @dragstart="onDragStart($event, todo)"
                    @dragend="onDragEnd"
                    @click="emit('select', todo)"
                >
                    <CardContent class="p-3">
                        <div class="flex items-start gap-2">
                            <div :class="['h-2 w-2 rounded-full mt-1.5 shrink-0', priorityColor(todo.priority)]" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ todo.title }}</p>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span v-if="todo.due_date" class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</span>
                                    <span v-if="todo.project" class="text-xs px-1.5 py-0.5 rounded bg-muted">{{ todo.project.name }}</span>
                                    <span v-for="label in (todo.labels ?? []).slice(0, 2)" :key="label.id"
                                        class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: label.color }" />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
