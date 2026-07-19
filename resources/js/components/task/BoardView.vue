<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Todo, TodoStatus } from '@/types/models';

const props = defineProps<{ todos: Todo[] }>();
const emit = defineEmits<{ select: [todo: Todo] }>();

const columns: Array<{ status: TodoStatus; label: string; color: string }> = [
    { status: 'pending', label: 'To Do', color: '#6b7280' },
    { status: 'in_progress', label: 'In Progress', color: '#3b82f6' },
    { status: 'completed', label: 'Done', color: '#22c55e' },
];

function getTodosByStatus(status: TodoStatus): Todo[] {
    return props.todos.filter((t) => t.status === status);
}

function priorityColor(priority: string): string {
    return (
        {
            urgent: 'bg-red-500',
            high: 'bg-orange-500',
            medium: 'bg-yellow-500',
            low: 'bg-blue-500',
            none: 'bg-gray-300',
        }[priority] ?? 'bg-gray-300'
    );
}

function formatDate(date: string | null): string {
    if (!date) {
return '';
}

    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
}

function onDragStart(event: DragEvent, todo: Todo) {
    event.dataTransfer?.setData('todo-id', todo.id);
}

function onDrop(event: DragEvent, status: TodoStatus) {
    const todoId = event.dataTransfer?.getData('todo-id');

    if (todoId) {
        router.post(
            route('todos.complete', todoId),
            {},
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <div class="grid min-h-[500px] grid-cols-3 gap-4">
        <div
            v-for="column in columns"
            :key="column.status"
            class="rounded-lg bg-muted/30 p-3"
            @dragover.prevent
            @drop="onDrop($event, column.status)"
        >
            <div class="mb-3 flex items-center gap-2">
                <div
                    class="h-2 w-2 rounded-full"
                    :style="{ backgroundColor: column.color }"
                />
                <h3 class="text-sm font-medium">{{ column.label }}</h3>
                <Badge variant="secondary" class="ml-auto">{{
                    getTodosByStatus(column.status).length
                }}</Badge>
            </div>
            <div class="space-y-2">
                <Card
                    v-for="todo in getTodosByStatus(column.status)"
                    :key="todo.id"
                    class="cursor-pointer transition-shadow hover:shadow-md"
                    draggable="true"
                    @dragstart="onDragStart($event, todo)"
                    @click="emit('select', todo)"
                >
                    <CardContent class="p-3">
                        <div class="flex items-start gap-2">
                            <div
                                :class="[
                                    'mt-1.5 h-2 w-2 shrink-0 rounded-full',
                                    priorityColor(todo.priority),
                                ]"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ todo.title }}
                                </p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        v-if="todo.due_date"
                                        class="text-xs text-muted-foreground"
                                        >{{ formatDate(todo.due_date) }}</span
                                    >
                                    <span
                                        v-if="todo.labels?.length"
                                        class="flex gap-1"
                                    >
                                        <span
                                            v-for="label in todo.labels.slice(
                                                0,
                                                2,
                                            )"
                                            :key="label.id"
                                            class="h-2 w-2 rounded-full"
                                            :style="{
                                                backgroundColor: label.color,
                                            }"
                                        />
                                    </span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
