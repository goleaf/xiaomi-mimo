<script setup lang="ts">
import { GripVertical } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { safeDefinitionColor } from '@/composables/useTaskDefinitions';
import { useUi } from '@/composables/useUi';
import type {
    TaskDefinitionCatalog,
    TaskStatusDefinition,
    Todo,
} from '@/types/models';

const props = defineProps<{
    todos: Todo[];
    taskDefinitions: TaskDefinitionCatalog;
    busyTodoId: string | null;
}>();
const emit = defineEmits<{
    move: [todo: Todo, status: TaskStatusDefinition];
    select: [todo: Todo];
}>();
const { formatDate, t } = useUi();
const draggedTodoId = ref<string | null>(null);
const columns = computed(() =>
    props.taskDefinitions.statuses.filter((status) => !status.is_archived),
);

function columnTodos(status: TaskStatusDefinition): Todo[] {
    return props.todos.filter(
        (todo) => todo.status_id === status.id || todo.status === status.key,
    );
}

function move(todo: Todo, statusId: string): void {
    const status = columns.value.find((item) => item.id === statusId);

    if (status && todo.status_id !== status.id && props.busyTodoId === null) {
        emit('move', todo, status);
    }
}

function drop(status: TaskStatusDefinition): void {
    const todo = props.todos.find((item) => item.id === draggedTodoId.value);

    if (todo) {
        move(todo, status.id);
    }

    draggedTodoId.value = null;
}

function openWithKeyboard(event: KeyboardEvent, todo: Todo): void {
    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        emit('select', todo);
    }
}
</script>

<template>
    <div class="overflow-x-auto pb-2" :aria-label="t('tasks.board.label')">
        <div
            class="flex min-w-max gap-4 xl:grid xl:min-w-0"
            :style="{
                gridTemplateColumns: `repeat(${Math.max(columns.length, 1)}, minmax(16rem, 1fr))`,
            }"
        >
            <section
                v-for="column in columns"
                :key="column.id"
                class="w-[min(82vw,20rem)] rounded-2xl border border-border/75 bg-muted/20 p-3 xl:w-auto"
                :aria-labelledby="`task-column-${column.id}`"
                @dragover.prevent
                @drop="drop(column)"
            >
                <header class="mb-3 flex items-center gap-2 px-1">
                    <span
                        class="size-2.5 rounded-full"
                        :style="{
                            backgroundColor: safeDefinitionColor(column.color),
                        }"
                        aria-hidden="true"
                    />
                    <h2
                        :id="`task-column-${column.id}`"
                        class="text-sm font-semibold"
                    >
                        {{ column.name }}
                    </h2>
                    <Badge variant="secondary" class="ml-auto tabular-nums">
                        {{ columnTodos(column).length }}
                    </Badge>
                </header>

                <div class="min-h-28 space-y-2.5">
                    <article
                        v-for="todo in columnTodos(column)"
                        :key="todo.id"
                        class="group rounded-xl border border-border/80 bg-background p-3.5 shadow-sm transition hover:border-orange-500/25 hover:shadow-md focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none"
                        :class="
                            busyTodoId === todo.id
                                ? 'pointer-events-none opacity-60'
                                : ''
                        "
                        draggable="true"
                        tabindex="0"
                        :aria-label="todo.title"
                        :aria-busy="busyTodoId === todo.id"
                        @click="emit('select', todo)"
                        @keydown="openWithKeyboard($event, todo)"
                        @dragstart="draggedTodoId = todo.id"
                        @dragend="draggedTodoId = null"
                    >
                        <div class="flex items-start gap-2">
                            <GripVertical
                                class="mt-0.5 size-4 shrink-0 text-muted-foreground/60"
                                aria-hidden="true"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="line-clamp-2 text-sm font-medium">
                                    {{ todo.title }}
                                </p>
                                <div
                                    class="mt-2 flex flex-wrap items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span v-if="todo.project">{{
                                        todo.project.name
                                    }}</span>
                                    <span v-if="todo.due_date">
                                        {{
                                            formatDate(todo.due_date, {
                                                month: 'short',
                                                day: 'numeric',
                                            })
                                        }}
                                    </span>
                                </div>
                                <Select
                                    :model-value="todo.status_id"
                                    :disabled="busyTodoId !== null"
                                    @click.stop
                                    @update:model-value="
                                        move(todo, String($event))
                                    "
                                >
                                    <SelectTrigger
                                        class="mt-3 h-8 w-full text-xs"
                                        :aria-label="
                                            t('tasks.board.move', {
                                                title: todo.title,
                                            })
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="status in columns"
                                            :key="status.id"
                                            :value="status.id"
                                        >
                                            {{ status.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </article>

                    <p
                        v-if="columnTodos(column).length === 0"
                        class="rounded-xl border border-dashed border-border/80 px-3 py-8 text-center text-xs text-muted-foreground"
                    >
                        {{ t('tasks.board.empty') }}
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
