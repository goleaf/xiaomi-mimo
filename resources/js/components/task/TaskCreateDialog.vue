<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { store } from '@/routes/todos';

const props = defineProps<{
    open: boolean;
    workspaceId: string;
    projectId?: string;
}>();
const emit = defineEmits<{ close: []; created: [] }>();
const toast = useToast();
const { t } = useUi();

const form = ref({
    title: '',
    description: '',
    priority: 'none',
    due_date: '',
    project_id: props.projectId ?? '',
    is_recurring: false,
    recurring_rule: 'none',
});

watch(
    () => props.open,
    (open) => {
        if (open) {
            form.value = {
                title: '',
                description: '',
                priority: 'none',
                due_date: '',
                project_id: props.projectId ?? '',
                is_recurring: false,
                recurring_rule: 'none',
            };
        }
    },
);

function submit() {
    if (!form.value.title.trim()) {
        return;
    }

    const {
        is_recurring: isRecurring,
        recurring_rule: recurringRule,
        ...data
    } = form.value;

    if (isRecurring && recurringRule !== 'none') {
        Object.assign(data, { recurring_rule: recurringRule });
    }

    router.post(store(props.workspaceId).url, data, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(t('tasks.create.created'));
            emit('created');
            emit('close');
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader
                ><DialogTitle>{{
                    t('tasks.create.new_task')
                }}</DialogTitle></DialogHeader
            >
            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="title">{{ t('tasks.create.title') }}</Label>
                    <Input
                        id="title"
                        v-model="form.title"
                        :placeholder="t('tasks.create.title_placeholder')"
                        autofocus
                    />
                </div>
                <div class="space-y-2">
                    <Label for="description">{{
                        t('tasks.create.description')
                    }}</Label>
                    <Input
                        id="description"
                        v-model="form.description"
                        :placeholder="t('tasks.create.description_placeholder')"
                    />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <Label>{{ t('tasks.create.priority') }}</Label>
                        <Select v-model="form.priority">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">{{
                                    t('tasks.priorities.none')
                                }}</SelectItem>
                                <SelectItem value="low">{{
                                    t('tasks.priorities.low')
                                }}</SelectItem>
                                <SelectItem value="medium">{{
                                    t('tasks.priorities.medium')
                                }}</SelectItem>
                                <SelectItem value="high">{{
                                    t('tasks.priorities.high')
                                }}</SelectItem>
                                <SelectItem value="urgent">{{
                                    t('tasks.priorities.urgent')
                                }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <div class="space-y-2">
                        <Label for="due_date">{{
                            t('tasks.create.due_date')
                        }}</Label>
                        <Input
                            id="due_date"
                            v-model="form.due_date"
                            type="date"
                        />
                    </div>
                </div>
                <div class="space-y-2">
                    <Label>{{ t('tasks.create.repeat') }}</Label>
                    <Select
                        v-model="form.recurring_rule"
                        :disabled="!form.is_recurring"
                    >
                        <SelectTrigger
                            ><SelectValue
                                :placeholder="
                                    form.is_recurring
                                        ? t(
                                              'tasks.create.frequency_placeholder',
                                          )
                                        : t('tasks.create.no_repeat')
                                "
                        /></SelectTrigger>
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
                    <div class="mt-1 flex items-center gap-2">
                        <input
                            type="checkbox"
                            v-model="form.is_recurring"
                            class="h-3 w-3"
                        />
                        <span class="text-xs text-muted-foreground">{{
                            t('tasks.create.repeat_task')
                        }}</span>
                    </div>
                </div>
                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="emit('close')"
                        >{{ t('common.actions.cancel') }}</Button
                    >
                    <Button type="submit">{{
                        t('tasks.create.submit')
                    }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
