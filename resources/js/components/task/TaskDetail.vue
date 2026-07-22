<script setup lang="ts">
import TaskDetailContent from '@/components/task/TaskDetailContent.vue';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useUi } from '@/composables/useUi';
import type { TaskDefinitionCatalog, Todo } from '@/types/models';

defineProps<{
    todo: Todo;
    open: boolean;
    taskDefinitions: TaskDefinitionCatalog;
}>();
const emit = defineEmits<{
    close: [];
    deleted: [];
    refresh: [];
    updated: [todo: Todo];
}>();
const { t } = useUi();

function deleted(): void {
    emit('close');
    emit('deleted');
}
</script>

<template>
    <Sheet :open="open" @update:open="!$event && emit('close')">
        <SheetContent
            side="right"
            :close-label="t('common.actions.close')"
            class="w-full max-w-none gap-0 overflow-y-auto border-border/80 p-0 sm:max-w-2xl"
        >
            <SheetHeader
                class="relative overflow-hidden border-b border-border/70 bg-muted/30 px-6 py-6 text-left sm:px-8"
            >
                <span
                    class="absolute inset-y-0 left-0 w-1.5 bg-orange-500"
                    aria-hidden="true"
                />
                <span
                    class="absolute -right-9 -bottom-16 size-36 rounded-full border-[18px] border-orange-500/20 bg-orange-500/[0.05]"
                    aria-hidden="true"
                />
                <SheetTitle class="relative text-xl tracking-tight">
                    {{ t('tasks.detail.title') }}
                </SheetTitle>
                <SheetDescription class="relative">
                    {{ t('tasks.detail.drawer_description') }}
                </SheetDescription>
            </SheetHeader>

            <div class="bg-muted/20 p-4 sm:p-6">
                <TaskDetailContent
                    :todo="todo"
                    :task-definitions="taskDefinitions"
                    @deleted="deleted"
                    @refresh="emit('refresh')"
                    @updated="emit('updated', $event)"
                />
            </div>
        </SheetContent>
    </Sheet>
</template>
