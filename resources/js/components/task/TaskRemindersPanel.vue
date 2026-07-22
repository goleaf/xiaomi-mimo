<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { Bell, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { destroy, store } from '@/routes/api/v1/reminders';
import type { Reminder } from '@/types/models';

const props = defineProps<{ todoId: string; initialReminders: Reminder[] }>();
const toast = useToast();
const { formatDate, t } = useUi();
const reminders = ref<Reminder[]>([]);
const deletingId = ref<string | null>(null);
const reminderRequest = useHttp<
    { reminded_at: string; type: Reminder['type'] },
    { data: Reminder }
>({
    reminded_at: '',
    type: 'in_app',
});
const deleteRequest = useHttp<Record<string, never>, undefined>({});

watch(
    () => props.todoId,
    () => {
        reminders.value = [...props.initialReminders];
        reminderRequest.resetAndClearErrors();
        deletingId.value = null;
    },
    { immediate: true, flush: 'sync' },
);

async function createReminder(): Promise<void> {
    if (!reminderRequest.reminded_at || reminderRequest.processing) {
        return;
    }

    try {
        const response = await reminderRequest.post(store(props.todoId).url);
        reminders.value.push(response.data);
        reminders.value.sort((a, b) =>
            a.reminded_at.localeCompare(b.reminded_at),
        );
        reminderRequest.reminded_at = '';
        toast.success(t('tasks.detail.reminder_created'));
    } catch {
        if (!reminderRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    }
}

async function deleteReminder(reminder: Reminder): Promise<void> {
    if (deleteRequest.processing) {
        return;
    }

    deletingId.value = reminder.id;

    try {
        await deleteRequest.delete(destroy([props.todoId, reminder]).url);
        reminders.value = reminders.value.filter(
            (item) => item.id !== reminder.id,
        );
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        deletingId.value = null;
    }
}
</script>

<template>
    <section class="rounded-[1.5rem] border border-border/80 bg-card p-5">
        <div class="flex items-center gap-2">
            <Bell class="size-4 text-orange-700" aria-hidden="true" />
            <h2 class="text-base font-semibold">
                {{ t('tasks.detail.reminders') }}
            </h2>
        </div>

        <form
            class="mt-4 grid gap-2 sm:grid-cols-[minmax(0,1fr)_10rem_auto]"
            @submit.prevent="createReminder"
        >
            <Input
                v-model="reminderRequest.reminded_at"
                type="datetime-local"
                :disabled="reminderRequest.processing"
                :aria-invalid="Boolean(reminderRequest.errors.reminded_at)"
            />
            <Select
                v-model="reminderRequest.type"
                :disabled="reminderRequest.processing"
            >
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    <SelectItem value="in_app">{{
                        t('tasks.detail.reminder_types.in_app')
                    }}</SelectItem>
                    <SelectItem value="email">{{
                        t('tasks.detail.reminder_types.email')
                    }}</SelectItem>
                    <SelectItem value="browser">{{
                        t('tasks.detail.reminder_types.browser')
                    }}</SelectItem>
                </SelectContent>
            </Select>
            <Button
                type="submit"
                variant="outline"
                :disabled="
                    reminderRequest.processing || !reminderRequest.reminded_at
                "
            >
                <Spinner v-if="reminderRequest.processing" />
                {{ t('common.actions.add') }}
            </Button>
        </form>
        <InputError
            class="mt-2"
            :message="
                reminderRequest.errors.reminded_at ??
                reminderRequest.errors.type
            "
        />
        <p class="mt-2 text-xs text-muted-foreground">
            {{ t('tasks.detail.reminder_delivery_note') }}
        </p>

        <div class="mt-4 space-y-2">
            <div
                v-for="reminder in reminders"
                :key="reminder.id"
                class="flex items-center gap-3 rounded-xl border border-border/70 bg-muted/20 p-3"
            >
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium">
                        {{
                            formatDate(reminder.reminded_at, {
                                dateStyle: 'medium',
                                timeStyle: 'short',
                            })
                        }}
                    </p>
                    <Badge variant="outline" class="mt-1">
                        {{ t(`tasks.detail.reminder_types.${reminder.type}`) }}
                    </Badge>
                </div>
                <Badge
                    v-if="reminder.status !== 'pending'"
                    :variant="
                        reminder.status === 'failed'
                            ? 'destructive'
                            : 'secondary'
                    "
                >
                    {{ t(`tasks.detail.reminder_statuses.${reminder.status}`) }}
                </Badge>
                <Button
                    v-if="reminder.permissions?.delete"
                    variant="ghost"
                    size="icon-sm"
                    class="text-muted-foreground hover:text-destructive"
                    :aria-label="t('tasks.detail.delete_reminder')"
                    :disabled="deleteRequest.processing"
                    @click="deleteReminder(reminder)"
                >
                    <Spinner v-if="deletingId === reminder.id" />
                    <Trash2 v-else class="size-4" aria-hidden="true" />
                </Button>
            </div>
            <p
                v-if="reminders.length === 0"
                class="rounded-xl border border-dashed border-border/80 px-4 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('tasks.detail.no_reminders') }}
            </p>
        </div>
    </section>
</template>
