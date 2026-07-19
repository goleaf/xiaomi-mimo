<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, CheckCircle2, Trash2, User } from '@lucide/vue';
import { ref } from 'vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { useTaskDetailState } from '@/composables/useTaskDetailState';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { store as storeChecklistItem, toggle } from '@/routes/checklistItems';
import { store as storeChecklist } from '@/routes/checklists';
import { store as storeComment } from '@/routes/comments';
import { complete, destroy, uncomplete, update } from '@/routes/todos';
import type { Todo } from '@/types/models';

const props = defineProps<{ todo: Todo; open: boolean }>();
const emit = defineEmits<{ close: [] }>();
const toast = useToast();
const { formatDate, t } = useUi();
const showDeleteDialog = ref(false);
const deletingTodo = ref(false);
const { checklistItemDrafts, checklistName, comment, editingTitle, form } =
    useTaskDetailState(() => props.todo);

function updateTitle() {
    if (form.title.trim() && form.title !== props.todo.title) {
        router.put(
            update(props.todo).url,
            { title: form.title },
            { preserveScroll: true },
        );
    }

    editingTitle.value = false;
}

function toggleComplete() {
    const target = props.todo.status === 'completed' ? uncomplete : complete;

    router.post(target(props.todo).url, {}, { preserveScroll: true });
}

function setPriority(priority: string) {
    router.put(update(props.todo).url, { priority }, { preserveScroll: true });
}

function setStatus(status: string) {
    router.put(update(props.todo).url, { status }, { preserveScroll: true });
}

function addComment() {
    if (!comment.value.trim()) {
        return;
    }

    router.post(
        storeComment(props.todo).url,
        { body: comment.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                comment.value = '';
            },
        },
    );
}

function addChecklist() {
    if (!checklistName.value.trim()) {
        return;
    }

    router.post(
        storeChecklist(props.todo).url,
        { name: checklistName.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                checklistName.value = '';
            },
        },
    );
}

function addChecklistItem(checklistId: string) {
    const content = checklistItemDrafts[checklistId] ?? '';

    if (!content.trim()) {
        return;
    }

    router.post(
        storeChecklistItem(checklistId).url,
        { content },
        {
            preserveScroll: true,
            onSuccess: () => {
                delete checklistItemDrafts[checklistId];
            },
        },
    );
}

function toggleChecklistItem(itemId: string) {
    router.patch(toggle(itemId).url, {}, { preserveScroll: true });
}

function deleteTodo() {
    deletingTodo.value = true;
    router.delete(destroy(props.todo).url, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteDialog.value = false;
            emit('close');
            toast.success(t('tasks.detail.deleted'));
        },
        onFinish: () => {
            deletingTodo.value = false;
        },
    });
}

function displayDate(date: string | null): string {
    if (!date) {
        return t('common.states.not_set');
    }

    return formatDate(date, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

const priorityOptions = ['none', 'low', 'medium', 'high', 'urgent'];
const statusOptions = ['pending', 'in_progress', 'completed'];
</script>

<template>
    <Sheet :open="open" @update:open="!$event && emit('close')">
        <SheetContent
            side="right"
            :close-label="t('common.actions.close')"
            class="w-full max-w-none gap-0 overflow-y-auto border-border/80 p-0 sm:max-w-xl"
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
                <div class="relative flex items-center gap-3 pr-10">
                    <Checkbox
                        :model-value="todo.status === 'completed'"
                        class="size-5 data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                        :aria-label="todo.title"
                        @update:model-value="toggleComplete"
                    />
                    <div class="min-w-0">
                        <SheetTitle class="truncate text-xl tracking-tight">
                            {{ t('tasks.detail.title') }}
                        </SheetTitle>
                        <SheetDescription class="truncate">
                            {{ todo.project?.name ?? todo.title }}
                        </SheetDescription>
                    </div>
                </div>
            </SheetHeader>

            <div class="space-y-5 bg-muted/20 p-4 sm:p-6">
                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-5 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)]"
                >
                    <Input
                        v-if="editingTitle"
                        v-model="form.title"
                        class="h-11 rounded-xl text-lg font-semibold"
                        autofocus
                        @blur="updateTitle"
                        @keyup.enter="updateTitle"
                    />
                    <button
                        v-else
                        type="button"
                        class="w-full cursor-pointer rounded-lg text-left text-lg font-semibold tracking-tight hover:text-orange-700 focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none dark:hover:text-orange-300"
                        @click="editingTitle = true"
                    >
                        {{ todo.title }}
                    </button>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div>
                            <p
                                class="mb-2 text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                            >
                                {{ t('tasks.filters.status') }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="status in statusOptions"
                                    :key="status"
                                    type="button"
                                    class="cursor-pointer rounded-lg focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none"
                                    :aria-pressed="todo.status === status"
                                    @click="setStatus(status)"
                                >
                                    <Badge
                                        :variant="
                                            todo.status === status
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{ t(`tasks.statuses.${status}`) }}
                                    </Badge>
                                </button>
                            </div>
                        </div>
                        <div>
                            <p
                                class="mb-2 text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                            >
                                {{ t('tasks.filters.priority') }}
                            </p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="priority in priorityOptions"
                                    :key="priority"
                                    type="button"
                                    class="cursor-pointer rounded-lg focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none"
                                    :aria-pressed="todo.priority === priority"
                                    @click="setPriority(priority)"
                                >
                                    <Badge
                                        :variant="
                                            todo.priority === priority
                                                ? 'default'
                                                : 'outline'
                                        "
                                    >
                                        {{ t(`tasks.priorities.${priority}`) }}
                                    </Badge>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                        <div
                            class="flex items-center gap-3 rounded-xl border border-border/70 bg-muted/25 p-3"
                        >
                            <Calendar
                                class="size-4 text-orange-700 dark:text-orange-300"
                                aria-hidden="true"
                            />
                            <span class="text-sm">{{
                                t('tasks.detail.due', {
                                    date: displayDate(todo.due_date),
                                })
                            }}</span>
                        </div>
                        <div
                            class="flex items-center gap-3 rounded-xl border border-border/70 bg-muted/25 p-3"
                        >
                            <User
                                class="size-4 text-orange-700 dark:text-orange-300"
                                aria-hidden="true"
                            />
                            <span class="truncate text-sm">{{
                                t('tasks.detail.assigned', {
                                    name:
                                        todo.assignee?.name ??
                                        t('common.states.unassigned'),
                                })
                            }}</span>
                        </div>
                    </div>
                </section>

                <section
                    v-if="todo.labels?.length || todo.tags?.length"
                    class="rounded-[1.5rem] border border-border/80 bg-card p-5"
                >
                    <div v-if="todo.labels?.length">
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.labels') }}
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge
                                v-for="label in todo.labels"
                                :key="label.id"
                                :style="{
                                    backgroundColor: label.color,
                                    color: 'white',
                                }"
                                >{{ label.name }}</Badge
                            >
                        </div>
                    </div>
                    <div
                        v-if="todo.tags?.length"
                        :class="todo.labels?.length ? 'mt-4' : ''"
                    >
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.tags') }}
                        </h3>
                        <div class="flex flex-wrap gap-1.5">
                            <Badge
                                v-for="tag in todo.tags"
                                :key="tag.id"
                                variant="secondary"
                                >{{ tag.name }}</Badge
                            >
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-5"
                >
                    <h3 class="text-sm font-medium">
                        {{ t('tasks.detail.description') }}
                    </h3>
                    <p
                        class="mt-2 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                    >
                        {{
                            todo.description ?? t('tasks.detail.no_description')
                        }}
                    </p>
                </section>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-5"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <CheckCircle2
                            class="size-4 text-orange-700 dark:text-orange-300"
                            aria-hidden="true"
                        />
                        <h3 class="text-sm font-medium">
                            {{ t('tasks.detail.checklists') }}
                        </h3>
                    </div>
                    <div
                        v-for="checklist in todo.checklists ?? []"
                        :key="checklist.id"
                        class="mb-3 rounded-2xl border border-border/70 bg-muted/20 p-4"
                    >
                        <p class="mb-2 text-sm font-medium">
                            {{ checklist.name }}
                        </p>
                        <div
                            v-for="item in checklist.items ?? []"
                            :key="item.id"
                            class="flex items-center gap-3 py-1.5"
                        >
                            <Checkbox
                                :model-value="item.is_checked"
                                class="data-[state=checked]:border-orange-600 data-[state=checked]:bg-orange-600"
                                :aria-label="item.content"
                                @update:model-value="
                                    toggleChecklistItem(item.id)
                                "
                            />
                            <span
                                :class="[
                                    'text-sm',
                                    item.is_checked
                                        ? 'text-muted-foreground line-through'
                                        : '',
                                ]"
                                >{{ item.content }}</span
                            >
                        </div>
                        <Input
                            v-model="checklistItemDrafts[checklist.id]"
                            :placeholder="t('tasks.detail.add_item')"
                            class="mt-2 h-10 rounded-xl text-sm"
                            @keyup.enter="addChecklistItem(checklist.id)"
                        />
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Input
                            v-model="checklistName"
                            :placeholder="t('tasks.detail.checklist_name')"
                            class="h-10 rounded-xl text-sm"
                            @keyup.enter="addChecklist"
                        />
                        <Button
                            variant="outline"
                            class="rounded-xl"
                            @click="addChecklist"
                        >
                            {{ t('common.actions.add') }}
                        </Button>
                    </div>
                </section>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-5"
                >
                    <h3 class="mb-4 text-sm font-medium">
                        {{ t('tasks.detail.comments') }}
                    </h3>
                    <div
                        v-for="taskComment in todo.comments ?? []"
                        :key="taskComment.id"
                        class="mb-3 rounded-2xl border border-border/70 bg-muted/20 p-4"
                    >
                        <div
                            class="mb-1 flex flex-wrap items-center gap-x-2 gap-y-1"
                        >
                            <span class="text-sm font-medium">{{
                                taskComment.user?.name ??
                                t('common.states.unknown')
                            }}</span>
                            <span class="text-xs text-muted-foreground">{{
                                displayDate(taskComment.created_at)
                            }}</span>
                        </div>
                        <p class="text-sm leading-6 whitespace-pre-wrap">
                            {{ taskComment.body }}
                        </p>
                    </div>
                    <div class="flex flex-col gap-2 sm:flex-row">
                        <Input
                            v-model="comment"
                            :placeholder="t('tasks.detail.comment_placeholder')"
                            class="h-10 rounded-xl text-sm"
                            @keyup.enter="addComment"
                        />
                        <Button
                            variant="outline"
                            class="rounded-xl"
                            @click="addComment"
                        >
                            {{ t('common.actions.post') }}
                        </Button>
                    </div>
                </section>

                <section
                    class="rounded-[1.5rem] border border-destructive/20 bg-destructive/[0.04] p-5"
                >
                    <Button
                        variant="destructive"
                        class="min-h-11 rounded-xl"
                        @click="showDeleteDialog = true"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                        {{ t('tasks.detail.delete') }}
                    </Button>
                </section>
            </div>
        </SheetContent>
    </Sheet>

    <WorkspaceConfirmDialog
        :open="showDeleteDialog"
        :title="t('tasks.detail.delete_confirm_title')"
        :description="t('tasks.detail.delete_confirm_description')"
        :confirm-label="t('tasks.detail.delete')"
        :cancel-label="t('common.actions.cancel')"
        :processing="deletingTodo"
        @update:open="showDeleteDialog = $event"
        @confirm="deleteTodo"
    >
        <template #icon>
            <Trash2 class="size-5" aria-hidden="true" />
        </template>
    </WorkspaceConfirmDialog>
</template>
