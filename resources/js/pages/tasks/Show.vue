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
import { ref, computed } from 'vue';
import { update as updateTodo } from '@/actions/App/Http/Controllers/TodoController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

const editing = ref(false);
const newComment = ref('');
const newChecklistName = ref('');
const newChecklistItemContent = ref('');
const editForm = useForm({
    title: todo.value.title,
    description: todo.value.description ?? '',
    status: todo.value.status as TodoStatus,
    priority: todo.value.priority as TodoPriority,
    due_date: todo.value.due_date ?? '',
});

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
    const routeName =
        todo.value.status === 'completed'
            ? 'todos.uncomplete'
            : 'todos.complete';
    router.post(route(routeName, todo.value.id), {}, { preserveScroll: true });
}

function addComment() {
    if (!newComment.value.trim()) {
        return;
    }

    router.post(
        route('comments.store', todo.value.id),
        { body: newComment.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newComment.value = '';
            },
        },
    );
}

function addChecklist() {
    if (!newChecklistName.value.trim()) {
        return;
    }

    router.post(
        route('checklists.store', todo.value.id),
        { name: newChecklistName.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newChecklistName.value = '';
            },
        },
    );
}

function addChecklistItem(checklistId: string) {
    if (!newChecklistItemContent.value.trim()) {
        return;
    }

    router.post(
        route('checklistItems.store', checklistId),
        { content: newChecklistItemContent.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                newChecklistItemContent.value = '';
            },
        },
    );
}

function toggleChecklistItem(itemId: string) {
    router.patch(
        route('checklistItems.toggle', itemId),
        {},
        { preserveScroll: true },
    );
}

function goBack() {
    window.history.back();
}
function formatDate(date: string | null): string {
    if (!date) {
        return 'Not set';
    }

    return new Date(date).toLocaleDateString('en-US', {
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
    <Head :title="todo.title" />
    <div class="max-w-3xl space-y-6 p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" @click="goBack"
                ><ArrowLeft class="h-4 w-4"
            /></Button>
            <div class="flex-1">
                <h1 class="text-2xl font-bold">{{ todo.title }}</h1>
                <div class="mt-1 flex items-center gap-3">
                    <Badge :variant="priorityBadge(todo.priority)">{{
                        todo.priority
                    }}</Badge>
                    <span class="text-sm text-muted-foreground">{{
                        todo.status?.replace('_', ' ')
                    }}</span>
                    <span
                        v-if="todo.project"
                        class="text-sm text-muted-foreground"
                        >in {{ todo.project.name }}</span
                    >
                </div>
            </div>
            <Button v-if="!editing" variant="outline" @click="startEditing">
                <Pencil class="h-4 w-4" />
                {{ labels.editTask }}
            </Button>
            <Button
                :variant="todo.status === 'completed' ? 'outline' : 'default'"
                @click="toggleComplete"
            >
                {{ todo.status === 'completed' ? 'Reopen' : 'Complete' }}
            </Button>
        </div>

        <Card v-if="editing">
            <CardHeader>
                <CardTitle class="text-base">{{ labels.editTask }}</CardTitle>
            </CardHeader>
            <CardContent>
                <form class="space-y-4" @submit.prevent="submitEdit">
                    <div class="space-y-2">
                        <Label for="task-title">{{ labels.title }}</Label>
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
                            class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive/20 dark:bg-input/30 dark:aria-invalid:ring-destructive/40"
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
                                editForm.processing || !editForm.title.trim()
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
        <div class="grid grid-cols-2 gap-4">
            <Card
                ><CardContent class="space-y-1 pt-4">
                    <div class="flex items-center gap-2 text-sm">
                        <Calendar class="h-4 w-4 text-muted-foreground" />Due:
                        {{ formatDate(todo.due_date) }}
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <User class="h-4 w-4 text-muted-foreground" />Assigned:
                        {{ todo.assignee?.name ?? 'Unassigned' }}
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <Clock class="h-4 w-4 text-muted-foreground" />Created:
                        {{ formatDate(todo.created_at) }}
                    </div>
                </CardContent></Card
            >
            <Card
                ><CardContent class="pt-4">
                    <div v-if="todo.labels?.length" class="mb-2">
                        <p
                            class="mb-1 text-xs font-medium text-muted-foreground"
                        >
                            Labels
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
                            Tags
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
                ><CardTitle class="text-base"
                    >Description</CardTitle
                ></CardHeader
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
                ><CardTitle class="text-base">Checklists</CardTitle></CardHeader
            >
            <CardContent class="space-y-4">
                <div
                    v-for="cl in todo.checklists"
                    :key="cl.id"
                    class="rounded-lg border p-3"
                >
                    <p class="mb-2 text-sm font-medium">{{ cl.name }}</p>
                    <div
                        v-for="item in cl.items ?? []"
                        :key="item.id"
                        class="flex items-center gap-2 py-1"
                    >
                        <input
                            type="checkbox"
                            :checked="item.is_checked"
                            class="h-3 w-3"
                            @change="toggleChecklistItem(item.id)"
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
                            v-model="newChecklistItemContent"
                            placeholder="Add item..."
                            class="h-8 text-xs"
                            @keyup.enter="addChecklistItem(cl.id)"
                        />
                    </div>
                </div>
                <div class="flex gap-2">
                    <Input
                        v-model="newChecklistName"
                        placeholder="New checklist..."
                        class="h-8 text-xs"
                        @keyup.enter="addChecklist"
                    />
                    <Button variant="outline" size="sm" @click="addChecklist"
                        >Add</Button
                    >
                </div>
            </CardContent>
        </Card>

        <!-- Comments -->
        <Card>
            <CardHeader class="flex flex-row items-center gap-2">
                <MessageSquare class="h-4 w-4" />
                <CardTitle class="text-base"
                    >Comments ({{ todo.comments?.length ?? 0 }})</CardTitle
                >
            </CardHeader>
            <CardContent class="space-y-3">
                <div
                    v-for="comment in todo.comments ?? []"
                    :key="comment.id"
                    class="rounded-lg border p-3"
                >
                    <div class="mb-1 flex items-center gap-2">
                        <span class="text-sm font-medium">{{
                            comment.user?.name ?? 'Unknown'
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
                        v-model="newComment"
                        placeholder="Write a comment..."
                        class="h-8 text-xs"
                        @keyup.enter="addComment"
                    />
                    <Button variant="outline" size="sm" @click="addComment"
                        >Post</Button
                    >
                </div>
            </CardContent>
        </Card>
    </div>
</template>
