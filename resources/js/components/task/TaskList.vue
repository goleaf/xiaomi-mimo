<script setup lang="ts">
import { Check, Circle, Trash2 } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { safeDefinitionColor } from '@/composables/useTaskDefinitions';
import { useUi } from '@/composables/useUi';
import type { Todo } from '@/types/models';

const props = defineProps<{
    todos: Todo[];
    selectedIds: string[];
    busyTodoId: string | null;
}>();
const emit = defineEmits<{
    delete: [todo: Todo];
    select: [todo: Todo];
    selectPage: [selected: boolean];
    toggleCompletion: [todo: Todo];
    toggleSelection: [todo: Todo];
}>();
const { formatDate, t } = useUi();
const selected = computed(() => new Set(props.selectedIds));
const allSelected = computed(
    () =>
        props.todos.length > 0 &&
        props.todos.every((todo) => selected.value.has(todo.id)),
);
</script>

<template>
    <div class="space-y-2.5">
        <div
            class="flex min-h-10 items-center gap-3 border-b border-border/60 px-1 pb-2"
        >
            <Checkbox
                :model-value="allSelected"
                :aria-label="t('tasks.index.select_page')"
                @update:model-value="emit('selectPage', Boolean($event))"
            />
            <span
                class="text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ t('tasks.index.select_page') }}
            </span>
        </div>

        <article
            v-for="todo in todos"
            :key="todo.id"
            class="group relative grid grid-cols-[auto_auto_minmax(0,1fr)_auto] items-center gap-2 rounded-xl border border-border/80 bg-background p-3 transition-[border-color,box-shadow,transform] hover:-translate-y-px hover:border-orange-500/25 hover:shadow-[0_16px_36px_-30px_rgba(234,88,12,0.55)] motion-reduce:transform-none sm:gap-3 sm:p-4"
            :class="
                selected.has(todo.id)
                    ? 'border-orange-500/30 bg-orange-500/[0.035]'
                    : ''
            "
            :aria-busy="busyTodoId === todo.id"
        >
            <button
                type="button"
                class="absolute inset-0 z-10 cursor-pointer rounded-xl focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                :aria-label="todo.title"
                @click="emit('select', todo)"
            ></button>
            <Checkbox
                class="pointer-events-auto relative z-20"
                :model-value="selected.has(todo.id)"
                :aria-label="
                    t('tasks.index.select_task', { title: todo.title })
                "
                :disabled="busyTodoId !== null"
                @update:model-value="emit('toggleSelection', todo)"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="pointer-events-auto relative z-20"
                :aria-label="
                    todo.is_completed
                        ? t('tasks.index.mark_pending', { title: todo.title })
                        : t('tasks.index.mark_complete', { title: todo.title })
                "
                :disabled="busyTodoId !== null"
                @click="emit('toggleCompletion', todo)"
            >
                <Check
                    v-if="todo.is_completed"
                    class="size-4 text-emerald-600"
                    aria-hidden="true"
                />
                <Circle
                    v-else
                    class="size-4 text-muted-foreground"
                    aria-hidden="true"
                />
            </Button>
            <div class="min-w-0">
                <span
                    :class="[
                        'block truncate text-sm font-medium',
                        todo.is_completed
                            ? 'text-muted-foreground line-through'
                            : '',
                    ]"
                >
                    {{ todo.title }}
                </span>
                <span
                    class="mt-1 flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1 text-xs text-muted-foreground"
                >
                    <span v-if="todo.project" class="truncate">{{
                        todo.project.name
                    }}</span>
                    <span v-if="todo.due_date">{{
                        formatDate(todo.due_date, {
                            month: 'short',
                            day: 'numeric',
                        })
                    }}</span>
                    <Badge
                        class="hidden sm:inline-flex"
                        variant="outline"
                        :style="{
                            borderColor: safeDefinitionColor(
                                todo.priority_definition?.color,
                            ),
                            color: safeDefinitionColor(
                                todo.priority_definition?.color,
                            ),
                        }"
                    >
                        {{ todo.priority_definition?.name ?? todo.priority }}
                    </Badge>
                </span>
            </div>
            <Button
                type="button"
                variant="ghost"
                size="icon-sm"
                class="pointer-events-auto relative z-20 text-muted-foreground hover:text-destructive sm:opacity-0 sm:group-focus-within:opacity-100 sm:group-hover:opacity-100"
                :aria-label="
                    t('tasks.index.delete_task', { title: todo.title })
                "
                :disabled="busyTodoId !== null"
                @click="emit('delete', todo)"
            >
                <Trash2 class="size-4" aria-hidden="true" />
            </Button>
        </article>
    </div>
</template>
