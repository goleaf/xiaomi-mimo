<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { update } from '@/routes/api/v1/tasks';
import type { TaskDefinitionCatalog, Todo } from '@/types/models';

const props = defineProps<{
    todo: Todo;
    taskDefinitions: TaskDefinitionCatalog;
}>();
const emit = defineEmits<{ updated: [todo: Todo] }>();
const toast = useToast();
const { t } = useUi();
const { statuses, priorities } = useTaskDefinitions(
    () => props.taskDefinitions,
);
const form = useHttp<
    {
        title: string;
        description: string;
        status: string;
        priority: string;
        due_date: string;
        is_recurring: boolean;
        recurring_rule: string;
    },
    { data: Todo }
>({
    title: '',
    description: '',
    status: '',
    priority: '',
    due_date: '',
    is_recurring: false,
    recurring_rule: 'FREQ=DAILY',
});

function reset(): void {
    form.title = props.todo.title;
    form.description = props.todo.description ?? '';
    form.status = props.todo.status;
    form.priority = props.todo.priority;
    form.due_date = props.todo.due_date ?? '';
    form.is_recurring = props.todo.is_recurring;
    form.recurring_rule = props.todo.recurring_rule ?? 'FREQ=DAILY';
    form.clearErrors();
}

watch(() => props.todo.id, reset, { immediate: true, flush: 'sync' });

async function save(): Promise<void> {
    if (!form.title.trim()) {
        form.setError('title', t('tasks.create.title_required'));

        return;
    }

    try {
        const response = await form.put(
            update([props.todo.workspace_id, props.todo]).url,
        );
        emit('updated', response.data);
        toast.success(t('tasks.detail.updated'));
    } catch {
        if (!form.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    }
}
</script>

<template>
    <section
        class="rounded-[1.5rem] border border-border/80 bg-card p-5 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)]"
    >
        <h2 class="mb-4 text-base font-semibold">
            {{ t('tasks.detail.overview') }}
        </h2>
        <form class="space-y-4" @submit.prevent="save">
            <div class="space-y-2">
                <Label :for="`task-title-${todo.id}`">{{
                    t('tasks.create.title')
                }}</Label>
                <Input
                    :id="`task-title-${todo.id}`"
                    v-model="form.title"
                    maxlength="500"
                    :disabled="form.processing"
                    :aria-invalid="Boolean(form.errors.title)"
                    @input="form.clearErrors('title')"
                />
                <InputError :message="form.errors.title" />
            </div>
            <div class="space-y-2">
                <Label :for="`task-description-${todo.id}`">{{
                    t('tasks.detail.description')
                }}</Label>
                <textarea
                    :id="`task-description-${todo.id}`"
                    v-model="form.description"
                    rows="4"
                    :disabled="form.processing"
                    :aria-invalid="Boolean(form.errors.description)"
                    class="flex min-h-24 w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-orange-500 focus-visible:ring-[3px] focus-visible:ring-orange-500/20 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive"
                />
                <InputError :message="form.errors.description" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="space-y-2">
                    <Label>{{ t('tasks.filters.status') }}</Label>
                    <Select v-model="form.status" :disabled="form.processing">
                        <SelectTrigger class="w-full"
                            ><SelectValue
                        /></SelectTrigger>
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
                    <Label>{{ t('tasks.filters.priority') }}</Label>
                    <Select v-model="form.priority" :disabled="form.processing">
                        <SelectTrigger class="w-full"
                            ><SelectValue
                        /></SelectTrigger>
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
                    <Label :for="`task-due-${todo.id}`">{{
                        t('tasks.filters.due_date')
                    }}</Label>
                    <Input
                        :id="`task-due-${todo.id}`"
                        v-model="form.due_date"
                        type="date"
                        :disabled="form.processing"
                    />
                    <InputError :message="form.errors.due_date" />
                </div>
            </div>
            <fieldset class="space-y-3 rounded-xl border border-border/70 p-4">
                <legend class="px-1 text-sm font-medium">
                    {{ t('tasks.detail.recurrence') }}
                </legend>
                <div class="flex min-h-11 items-center gap-3">
                    <Checkbox
                        :id="`task-recurring-${todo.id}`"
                        :model-value="form.is_recurring"
                        :disabled="form.processing"
                        @update:model-value="
                            form.is_recurring = Boolean($event)
                        "
                    />
                    <Label
                        :for="`task-recurring-${todo.id}`"
                        class="cursor-pointer font-normal"
                    >
                        {{ t('tasks.create.repeat_task') }}
                    </Label>
                </div>
                <Select
                    v-model="form.recurring_rule"
                    :disabled="!form.is_recurring || form.processing"
                >
                    <SelectTrigger class="w-full"
                        ><SelectValue
                    /></SelectTrigger>
                    <SelectContent>
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
            </fieldset>
            <div class="flex justify-end">
                <Button
                    type="submit"
                    size="lg"
                    :disabled="form.processing || !form.title.trim()"
                >
                    <Spinner v-if="form.processing" />
                    {{
                        form.processing
                            ? t('common.actions.saving')
                            : t('common.actions.save')
                    }}
                </Button>
            </div>
        </form>
    </section>
</template>
