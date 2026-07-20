<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useTaskDefinitions } from '@/composables/useTaskDefinitions';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { store } from '@/routes/todos';
import type { TaskDefinitionCatalog } from '@/types/models';

const props = defineProps<{
    open: boolean;
    workspaceId: string;
    projectId?: string;
    taskDefinitions: TaskDefinitionCatalog;
}>();
const emit = defineEmits<{ close: []; created: [] }>();
const toast = useToast();
const { t } = useUi();
const { statuses, priorities, defaultStatus, defaultPriority } =
    useTaskDefinitions(() => props.taskDefinitions);

const form = useHttp({
    title: '',
    description: '',
    status: defaultStatus.value?.key ?? 'pending',
    priority: defaultPriority.value?.key ?? 'none',
    due_date: '',
    project_id: props.projectId ?? '',
    is_recurring: false,
    recurring_rule: 'none',
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.resetAndClearErrors();
            form.project_id = props.projectId ?? '';
            form.status = defaultStatus.value?.key ?? '';
            form.priority = defaultPriority.value?.key ?? '';
        }
    },
);

async function submit(): Promise<void> {
    if (!form.title.trim()) {
        form.setError('title', t('tasks.create.title_required'));

        return;
    }

    try {
        await form.submit(store(props.workspaceId), {
            onSuccess: () => {
                toast.success(t('tasks.create.created'));
                emit('created');
                emit('close');
            },
            onHttpException: () => {
                toast.error(t('tasks.create.create_failed'));
            },
            onNetworkError: () => {
                toast.error(t('tasks.create.create_failed'));
            },
        });
    } catch {
        if (!form.hasErrors) {
            toast.error(t('tasks.create.create_failed'));
        }
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <WorkspaceDialogContent
            :title="t('tasks.create.new_task')"
            :description="t('tasks.create.dialog_description')"
            :close-label="t('common.actions.cancel')"
            max-width-class="sm:max-w-xl"
        >
            <form class="space-y-6 px-6 py-6 sm:px-8" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label for="title">{{ t('tasks.create.title') }}</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        :placeholder="t('tasks.create.title_placeholder')"
                        autofocus
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.title)"
                        @input="form.clearErrors('title')"
                    />
                    <InputError :message="form.errors.title" />
                </div>
                <div class="space-y-2">
                    <Label for="description">{{
                        t('tasks.create.description')
                    }}</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        :placeholder="t('tasks.create.description_placeholder')"
                        :disabled="form.processing"
                        :aria-invalid="Boolean(form.errors.description)"
                        @input="form.clearErrors('description')"
                    />
                    <InputError :message="form.errors.description" />
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="space-y-2">
                        <Label for="task-status">{{
                            t('tasks.create.status')
                        }}</Label>
                        <Select
                            v-model="form.status"
                            :disabled="form.processing"
                        >
                            <SelectTrigger
                                id="task-status"
                                :aria-invalid="Boolean(form.errors.status)"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="status in statuses"
                                    :key="status.id"
                                    :value="status.key"
                                >
                                    {{ status.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                    <div class="space-y-2">
                        <Label for="task-priority">{{
                            t('tasks.create.priority')
                        }}</Label>
                        <Select
                            v-model="form.priority"
                            :disabled="form.processing"
                        >
                            <SelectTrigger
                                id="task-priority"
                                :aria-invalid="Boolean(form.errors.priority)"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="priority in priorities"
                                    :key="priority.id"
                                    :value="priority.key"
                                >
                                    {{ priority.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.priority" />
                    </div>
                    <div class="space-y-2">
                        <Label for="due_date">{{
                            t('tasks.create.due_date')
                        }}</Label>
                        <Input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                            :disabled="form.processing"
                            :aria-invalid="Boolean(form.errors.due_date)"
                        />
                        <InputError :message="form.errors.due_date" />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label for="task-recurring-rule">{{
                        t('tasks.create.repeat')
                    }}</Label>
                    <Select
                        v-model="form.recurring_rule"
                        :disabled="!form.is_recurring || form.processing"
                    >
                        <SelectTrigger
                            id="task-recurring-rule"
                            :aria-invalid="Boolean(form.errors.recurring_rule)"
                        >
                            <SelectValue
                                :placeholder="
                                    form.is_recurring
                                        ? t(
                                              'tasks.create.frequency_placeholder',
                                          )
                                        : t('tasks.create.no_repeat')
                                "
                            />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="none">{{
                                t('tasks.create.no_repeat')
                            }}</SelectItem>
                            <SelectItem value="FREQ=DAILY">{{
                                t('tasks.recurring.daily')
                            }}</SelectItem>
                            <SelectItem value="FREQ=WEEKLY">{{
                                t('tasks.recurring.weekly')
                            }}</SelectItem>
                            <SelectItem value="FREQ=MONTHLY">{{
                                t('tasks.recurring.monthly')
                            }}</SelectItem>
                            <SelectItem value="FREQ=YEARLY">{{
                                t('tasks.recurring.yearly')
                            }}</SelectItem>
                            <SelectItem value="FREQ=DAILY;INTERVAL=2">{{
                                t('tasks.recurring.every_2_days')
                            }}</SelectItem>
                            <SelectItem value="FREQ=WEEKLY;INTERVAL=2">{{
                                t('tasks.recurring.every_2_weeks')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.recurring_rule" />
                    <div
                        class="mt-3 flex min-h-11 items-center gap-3 rounded-xl border border-border/70 bg-muted/25 px-3.5"
                    >
                        <Checkbox
                            id="task-is-recurring"
                            :model-value="form.is_recurring"
                            class="size-4.5 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                            :disabled="form.processing"
                            @update:model-value="
                                form.is_recurring = Boolean($event)
                            "
                        />
                        <Label
                            for="task-is-recurring"
                            class="cursor-pointer text-sm font-normal text-muted-foreground"
                        >
                            {{ t('tasks.create.repeat_task') }}
                        </Label>
                    </div>
                </div>
                <DialogFooter
                    class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        :disabled="form.processing"
                        @click="emit('close')"
                        >{{ t('common.actions.cancel') }}</Button
                    >
                    <Button type="submit" size="lg" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{
                            form.processing
                                ? t('tasks.create.creating')
                                : t('tasks.create.submit')
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </WorkspaceDialogContent>
    </Dialog>
</template>
