import { reactive, ref, toValue, watch } from 'vue';
import type { MaybeRefOrGetter } from 'vue';

interface TaskDetailSource {
    id: string;
    title: string;
    description?: null | string;
    status: string;
    priority: string;
    due_date?: null | string;
}

export function useTaskDetailState(task: MaybeRefOrGetter<TaskDetailSource>) {
    const editingTitle = ref(false);
    const comment = ref('');
    const checklistName = ref('');
    const checklistItemDrafts = reactive<Record<string, string>>({});
    const form = reactive({
        title: '',
        description: '',
        status: '',
        priority: '',
        dueDate: '',
    });

    function reset(): void {
        const selectedTask = toValue(task);

        form.title = selectedTask.title;
        form.description = selectedTask.description ?? '';
        form.status = selectedTask.status;
        form.priority = selectedTask.priority;
        form.dueDate = selectedTask.due_date ?? '';
        editingTitle.value = false;
        comment.value = '';
        checklistName.value = '';

        for (const checklistId of Object.keys(checklistItemDrafts)) {
            delete checklistItemDrafts[checklistId];
        }
    }

    watch(() => toValue(task).id, reset, { flush: 'sync', immediate: true });

    return {
        checklistItemDrafts,
        checklistName,
        comment,
        editingTitle,
        form,
        reset,
    };
}
