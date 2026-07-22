<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { ref, watch } from 'vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import TaskDetailContent from '@/components/task/TaskDetailContent.vue';
import { Button } from '@/components/ui/button';
import { useUi } from '@/composables/useUi';
import { index as tasksIndex } from '@/routes/todos';
import type {
    Label as TaskLabel,
    TaskDefinitionCatalog,
    Todo,
} from '@/types/models';

const props = defineProps<{
    todo: { data: Todo };
    availableLabels: { data: TaskLabel[] };
    labels: Record<string, unknown>;
    taskDefinitions: TaskDefinitionCatalog;
}>();
const { t } = useUi();
const currentTodo = ref(props.todo.data);

watch(
    () => props.todo.data,
    (todo) => {
        currentTodo.value = todo;
    },
);

function refresh(): void {
    router.reload({ only: ['todo', 'taskDefinitions'] });
}

function updated(todo: Todo): void {
    currentTodo.value = { ...currentTodo.value, ...todo };
    refresh();
}

function deleted(): void {
    router.visit(tasksIndex.url());
}
</script>

<template>
    <div>
        <Head :title="currentTodo.title" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="t('tasks.detail.title')"
                    :title="currentTodo.title"
                    :description="t('tasks.detail.page_description')"
                >
                    <template #actions>
                        <Button as-child variant="outline" size="lg">
                            <Link :href="tasksIndex.url()">
                                <ArrowLeft class="size-4" aria-hidden="true" />
                                {{ t('common.actions.back') }}
                            </Link>
                        </Button>
                    </template>
                </WorkspacePageHeader>

                <TaskDetailContent
                    :todo="currentTodo"
                    :task-definitions="taskDefinitions"
                    @deleted="deleted"
                    @refresh="refresh"
                    @updated="updated"
                />
            </div>
        </main>
    </div>
</template>
