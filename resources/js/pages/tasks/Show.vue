<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Calendar,
    User,
    MessageSquare,
    Clock,
    LoaderCircle,
    Pencil,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { update as updateTodo } from '@/actions/App/Http/Controllers/TodoController';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { useTaskDetailState } from '@/composables/useTaskDetailState';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { store as storeChecklistItem, toggle } from '@/routes/checklistItems';
import { store as storeChecklist } from '@/routes/checklists';
import { store as storeComment } from '@/routes/comments';
import { complete, uncomplete } from '@/routes/todos';
import type { Todo, TodoPriority, TodoStatus } from '@/types/models';

interface TaskShowLabels {
    editTask: string;
    cancel: string;
    saveChanges: string;
    saving: string;
    title: string;
    description: string;
    descriptionPlaceholder: string;
    status: string;
    priority: string;
    dueDate: string;
    updated: string;
    statuses: {
        pending: string;
        inProgress: string;
        completed: string;
    };
    priorities: {
        none: string;
        low: string;
        medium: string;
        high: string;
        urgent: string;
    };
}

const props = defineProps<{
    todo: { data: Todo };
    labels: TaskShowLabels;
}>();
const toast = useToast();
const todo = computed(() => props.todo.data);
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();

const editing = ref(false);
const { checklistItemDrafts, checklistName, comment } =
    useTaskDetailState(todo);
const editForm = useForm({
    title: todo.value.title,
    description: todo.value.description ?? '',
    status: todo.value.status as TodoStatus,
    priority: todo.value.priority as TodoPriority,
    due_date: todo.value.due_date ?? '',
});

watch(
    () => todo.value.id,
    () => {
        editForm.defaults({
            title: todo.value.title,
            description: todo.value.description ?? '',
            status: todo.value.status,
            priority: todo.value.priority,
            due_date: todo.value.due_date ?? '',
        });
        editForm.resetAndClearErrors();
        editing.value = false;
    },
    { flush: 'sync' },
);

function startEditing() {
    editForm.defaults({
        title: todo.value.title,
        description: todo.value.description ?? '',
        status: todo.value.status,
        priority: todo.value.priority,
        due_date: todo.value.due_date ?? '',
    });
    editForm.resetAndClearErrors();
    editing.value = true;
}

function cancelEditing() {
    editForm.resetAndClearErrors();
    editing.value = false;
}

function submitEdit() {
    editForm
        .transform((data) => ({
            ...data,
            description: data.description.trim() || null,
            due_date: data.due_date || null,
        }))
        .submit(updateTodo(todo.value), {
            preserveScroll: true,
            onSuccess: () => {
                editForm.defaults();
                editing.value = false;
                toast.success(props.labels.updated);
            },
        });
}

function toggleComplete() {
    const target = todo.value.status === 'completed' ? uncomplete : complete;

    router.post(target(todo.value).url, {}, { preserveScroll: true });
}

function addComment() {
    if (!comment.value.trim()) {
        return;
    }

    router.post(
        storeComment(todo.value).url,
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
        storeChecklist(todo.value).url,
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

function goBack() {
    window.history.back();
}
function formatDate(date: string | null): string {
    if (!date) {
        return t('common.states.not_set');
    }

    return formatLocalizedDate(date, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
function priorityBadge(
    priority: TodoPriority,
): 'destructive' | 'secondary' | 'outline' {
    if (priority === 'urgent' || priority === 'high') {
        return 'destructive';
    }

    return priority === 'medium' ? 'secondary' : 'outline';
}
</script>

<template>
    <div>
        <Head :title="todo.title" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="t('tasks.detail.title')"
                    :title="todo.title"
                    :description="
                        todo.description ?? t('tasks.detail.no_description')
                    "
                >
                    <template #actions>
                        <Button variant="outline" @click="goBack">
                            <ArrowLeft class="size-4" aria-hidden="true" />
                            {{ t('common.actions.back') }}
                        </Button>
                        <Button
                            v-if="!editing"
                            variant="outline"
                            @click="startEditing"
                        >
                            <Pencil class="size-4" aria-hidden="true" />
                            {{ labels.editTask }}
                        </Button>
                        <Button
                            :variant="
                                todo.status === 'completed'
                                    ? 'outline'
                                    : 'default'
                            "
                            @click="toggleComplete"
                        >
                            {{
                                todo.status === 'completed'
                                    ? t('common.actions.reopen')
                                    : t('common.actions.complete')
                            }}
                        </Button>
                    </template>
                </WorkspacePageHeader>

                <div
                    class="flex flex-wrap items-center gap-2 rounded-xl border border-border/80 bg-card px-4 py-3 shadow-sm"
                >
                    <Badge :variant="priorityBadge(todo.priority)">
                        {{ t(`tasks.priorities.${todo.priority}`) }}
                    </Badge>
                    <span class="text-sm text-muted-foreground">
                        {{ t(`tasks.statuses.${todo.status}`) }}
                    </span>
                    <span
                        v-if="todo.project"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            t('tasks.detail.in_project', {
                                project: todo.project.name,
                            })
                        }}
                    </span>
                </div>

                <Card v-if="editing">
                    <CardHeader>
                        <CardTitle class="text-base">{{
                            labels.editTask
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form class="space-y-4" @submit.prevent="submitEdit">
                            <div class="space-y-2">
                                <Label for="task-title">{{
                                    labels.title
                                }}</Label>
                                <Input
                                    id="task-title"
                                    v-model="editForm.title"
                                    maxlength="500"
                                    autofocus
                                />
                                <p
                                    v-if="editForm.errors.title"
                                    class="text-sm text-destructive"
                                >
                                    {{ editForm.errors.title }}
                                </p>
                            </div>

                            <div class="space-y-2">
                                <Label for="task-description">{{
                                    labels.description
                                }}</Label>
                                <textarea
                                    id="task-description"
                                    v-model="editForm.description"
                                    rows="4"
                                    :placeholder="labels.descriptionPlaceholder"
                                    class="flex min-h-24 w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-orange-500 focus-visible:ring-[3px] focus-visible:ring-orange-500/20 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
                                />
                                <p
                                    v-if="editForm.errors.description"
                                    class="text-sm text-destructive"
                                >
                                    {{ editForm.errors.description }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div class="space-y-2">
                                    <Label>{{ labels.status }}</Label>
                                    <Select v-model="editForm.status">
                                        <SelectTrigger class="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">{{
                                                labels.statuses.pending
                                            }}</SelectItem>
                                            <SelectItem value="in_progress">{{
                                                labels.statuses.inProgress
                                            }}</SelectItem>
                                            <SelectItem value="completed">{{
                                                labels.statuses.completed
                                            }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p
                                        v-if="editForm.errors.status"
                                        class="text-sm text-destructive"
                                    >
                                        {{ editForm.errors.status }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label>{{ labels.priority }}</Label>
                                    <Select v-model="editForm.priority">
                                        <SelectTrigger class="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="none">{{
                                                labels.priorities.none
                                            }}</SelectItem>
                                            <SelectItem value="low">{{
                                                labels.priorities.low
                                            }}</SelectItem>
                                            <SelectItem value="medium">{{
                                                labels.priorities.medium
                                            }}</SelectItem>
                                            <SelectItem value="high">{{
                                                labels.priorities.high
                                            }}</SelectItem>
                                            <SelectItem value="urgent">{{
                                                labels.priorities.urgent
                                            }}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p
                                        v-if="editForm.errors.priority"
                                        class="text-sm text-destructive"
                                    >
                                        {{ editForm.errors.priority }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Label for="task-due-date">{{
                                        labels.dueDate
                                    }}</Label>
                                    <Input
                                        id="task-due-date"
                                        v-model="editForm.due_date"
                                        type="date"
                                    />
                                    <p
                                        v-if="editForm.errors.due_date"
                                        class="text-sm text-destructive"
                                    >
                                        {{ editForm.errors.due_date }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    :disabled="editForm.processing"
                                    @click="cancelEditing"
                                >
                                    {{ labels.cancel }}
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="
                                        editForm.processing ||
                                        !editForm.title.trim()
                                    "
                                >
                                    <LoaderCircle
                                        v-if="editForm.processing"
                                        class="h-4 w-4 animate-spin"
                                    />
                                    {{
                                        editForm.processing
                                            ? labels.saving
                                            : labels.saveChanges
                                    }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- Metadata -->
                <div class="grid gap-4 md:grid-cols-2">
                    <Card
                        ><CardContent class="space-y-1 pt-4">
                            <div class="flex items-center gap-2 text-sm">
                                <Calendar
                                    class="h-4 w-4 text-muted-foreground"
                                />{{
                                    t('tasks.detail.due', {
                                        date: formatDate(todo.due_date),
                                    })
                                }}
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <User class="h-4 w-4 text-muted-foreground" />{{
                                    t('tasks.detail.assigned', {
                                        name:
                                            todo.assignee?.name ??
                                            t('common.states.unassigned'),
                                    })
                                }}
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <Clock
                                    class="h-4 w-4 text-muted-foreground"
                                />{{
                                    t('tasks.detail.created', {
                                        date: formatDate(todo.created_at),
                                    })
                                }}
                            </div>
                        </CardContent></Card
                    >
                    <Card
                        ><CardContent class="pt-4">
                            <div v-if="todo.labels?.length" class="mb-2">
                                <p
                                    class="mb-1 text-xs font-medium text-muted-foreground"
                                >
                                    {{ t('tasks.detail.labels') }}
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="l in todo.labels"
                                        :key="l.id"
                                        :style="{
                                            backgroundColor: l.color,
                                            color: 'white',
                                        }"
                                        class="text-xs"
                                        >{{ l.name }}</Badge
                                    >
                                </div>
                            </div>
                            <div v-if="todo.tags?.length">
                                <p
                                    class="mb-1 text-xs font-medium text-muted-foreground"
                                >
                                    {{ t('tasks.detail.tags') }}
                                </p>
                                <div class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="t in todo.tags"
                                        :key="t.id"
                                        variant="secondary"
                                        class="text-xs"
                                        >{{ t.name }}</Badge
                                    >
                                </div>
                            </div>
                        </CardContent></Card
                    >
                </div>

                <!-- Description -->
                <Card v-if="todo.description">
                    <CardHeader
                        ><CardTitle class="text-base">{{
                            t('tasks.detail.description')
                        }}</CardTitle></CardHeader
                    >
                    <CardContent
                        ><p class="text-sm whitespace-pre-wrap">
                            {{ todo.description }}
                        </p></CardContent
                    >
                </Card>

                <!-- Checklists -->
                <Card v-if="todo.checklists?.length">
                    <CardHeader
                        ><CardTitle class="text-base">{{
                            t('tasks.detail.checklists')
                        }}</CardTitle></CardHeader
                    >
                    <CardContent class="space-y-4">
                        <div
                            v-for="cl in todo.checklists"
                            :key="cl.id"
                            class="rounded-2xl border border-border/70 bg-muted/20 p-4"
                        >
                            <p class="mb-2 text-sm font-medium">
                                {{ cl.name }}
                            </p>
                            <div
                                v-for="item in cl.items ?? []"
                                :key="item.id"
                                class="flex items-center gap-2 py-1"
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
                            <div class="mt-2 flex gap-2">
                                <Input
                                    v-model="checklistItemDrafts[cl.id]"
                                    :placeholder="t('tasks.detail.add_item')"
                                    class="h-8 text-xs"
                                    @keyup.enter="addChecklistItem(cl.id)"
                                />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Input
                                v-model="checklistName"
                                :placeholder="t('tasks.detail.checklist_short')"
                                class="h-8 text-xs"
                                @keyup.enter="addChecklist"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                @click="addChecklist"
                                >{{ t('common.actions.add') }}</Button
                            >
                        </div>
                    </CardContent>
                </Card>

                <!-- Comments -->
                <Card>
                    <CardHeader class="flex flex-row items-center gap-2">
                        <MessageSquare class="h-4 w-4" />
                        <CardTitle class="text-base">{{
                            t('tasks.detail.comments_count', {
                                count: formatNumber(todo.comments?.length ?? 0),
                            })
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="comment in todo.comments ?? []"
                            :key="comment.id"
                            class="rounded-2xl border border-border/70 bg-muted/20 p-4"
                        >
                            <div class="mb-1 flex items-center gap-2">
                                <span class="text-sm font-medium">{{
                                    comment.user?.name ??
                                    t('common.states.unknown')
                                }}</span>
                                <span class="text-xs text-muted-foreground">{{
                                    formatDate(comment.created_at)
                                }}</span>
                            </div>
                            <p class="text-sm whitespace-pre-wrap">
                                {{ comment.body }}
                            </p>
                        </div>
                        <div class="flex gap-2">
                            <Input
                                v-model="comment"
                                :placeholder="
                                    t('tasks.detail.comment_placeholder')
                                "
                                class="h-8 text-xs"
                                @keyup.enter="addComment"
                            />
                            <Button
                                variant="outline"
                                size="sm"
                                @click="addComment"
                                >{{ t('common.actions.post') }}</Button
                            >
                        </div>
                    </CardContent>
                </Card>
            </div>
        </main>
    </div>
</template>
