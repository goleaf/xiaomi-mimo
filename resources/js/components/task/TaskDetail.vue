<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Calendar, User, X } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
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
    router.delete(destroy(props.todo).url, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
            toast.success(t('tasks.detail.deleted'));
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
    <Teleport to="body">
        <Transition name="slide">
            <div
                v-if="open"
                class="fixed inset-y-0 right-0 z-50 w-full max-w-lg overflow-y-auto border-l bg-background shadow-xl"
            >
                <div
                    class="sticky top-0 z-10 flex items-center justify-between border-b bg-background p-4"
                >
                    <div class="flex items-center gap-2">
                        <input
                            type="checkbox"
                            :checked="todo.status === 'completed'"
                            class="h-4 w-4"
                            @change="toggleComplete"
                        />
                        <span class="text-sm text-muted-foreground">{{
                            t('tasks.detail.title')
                        }}</span>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        :aria-label="t('common.actions.close')"
                        @click="emit('close')"
                        ><X class="h-4 w-4"
                    /></Button>
                </div>

                <div class="space-y-6 p-6">
                    <!-- Title -->
                    <div>
                        <input
                            v-if="editingTitle"
                            v-model="form.title"
                            class="w-full border-b border-primary pb-1 text-lg font-semibold outline-none"
                            @blur="updateTitle"
                            @keyup.enter="updateTitle"
                            autofocus
                        />
                        <h2
                            v-else
                            class="cursor-pointer text-lg font-semibold hover:underline"
                            @click="editingTitle = true"
                        >
                            {{ todo.title }}
                        </h2>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-2">
                        <span class="w-24 text-sm text-muted-foreground">{{
                            t('tasks.filters.status')
                        }}</span>
                        <div class="flex gap-1">
                            <Badge
                                v-for="s in statusOptions"
                                :key="s"
                                :variant="
                                    todo.status === s ? 'default' : 'outline'
                                "
                                class="cursor-pointer"
                                @click="setStatus(s)"
                                >{{ t(`tasks.statuses.${s}`) }}</Badge
                            >
                        </div>
                    </div>

                    <!-- Priority -->
                    <div class="flex items-center gap-2">
                        <span class="w-24 text-sm text-muted-foreground">{{
                            t('tasks.filters.priority')
                        }}</span>
                        <div class="flex gap-1">
                            <Badge
                                v-for="p in priorityOptions"
                                :key="p"
                                :variant="
                                    todo.priority === p ? 'default' : 'outline'
                                "
                                class="cursor-pointer"
                                @click="setPriority(p)"
                                >{{ t(`tasks.priorities.${p}`) }}</Badge
                            >
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm">{{
                            t('tasks.detail.due', {
                                date: displayDate(todo.due_date),
                            })
                        }}</span>
                    </div>

                    <!-- Assignee -->
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm">{{
                            t('tasks.detail.assigned', {
                                name:
                                    todo.assignee?.name ??
                                    t('common.states.unassigned'),
                            })
                        }}</span>
                    </div>

                    <Separator />

                    <!-- Labels -->
                    <div v-if="todo.labels?.length">
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.labels') }}
                        </h3>
                        <div class="flex flex-wrap gap-1">
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

                    <!-- Tags -->
                    <div v-if="todo.tags?.length">
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.tags') }}
                        </h3>
                        <div class="flex flex-wrap gap-1">
                            <Badge
                                v-for="tag in todo.tags"
                                :key="tag.id"
                                variant="secondary"
                                >{{ tag.name }}</Badge
                            >
                        </div>
                    </div>

                    <Separator />

                    <!-- Description -->
                    <div>
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.description') }}
                        </h3>
                        <p
                            class="text-sm whitespace-pre-wrap text-muted-foreground"
                        >
                            {{
                                todo.description ??
                                t('tasks.detail.no_description')
                            }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Checklists -->
                    <div>
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.checklists') }}
                        </h3>
                        <div
                            v-for="checklist in todo.checklists ?? []"
                            :key="checklist.id"
                            class="mb-3 rounded-lg border p-3"
                        >
                            <p class="mb-2 text-sm font-medium">
                                {{ checklist.name }}
                            </p>
                            <div
                                v-for="item in checklist.items ?? []"
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
                                    v-model="checklistItemDrafts[checklist.id]"
                                    :placeholder="t('tasks.detail.add_item')"
                                    class="h-8 text-xs"
                                    @keyup.enter="
                                        addChecklistItem(checklist.id)
                                    "
                                />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Input
                                v-model="checklistName"
                                :placeholder="t('tasks.detail.checklist_name')"
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
                    </div>

                    <Separator />

                    <!-- Comments -->
                    <div>
                        <h3 class="mb-2 text-sm font-medium">
                            {{ t('tasks.detail.comments') }}
                        </h3>
                        <div
                            v-for="comment in todo.comments ?? []"
                            :key="comment.id"
                            class="mb-3 rounded-lg border p-3"
                        >
                            <div class="mb-1 flex items-center gap-2">
                                <span class="text-sm font-medium">{{
                                    comment.user?.name ??
                                    t('common.states.unknown')
                                }}</span>
                                <span class="text-xs text-muted-foreground">{{
                                    displayDate(comment.created_at)
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
                    </div>

                    <Separator />

                    <!-- Danger Zone -->
                    <div class="pt-4">
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="deleteTodo"
                            >{{ t('tasks.detail.delete') }}</Button
                        >
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}
.slide-enter-from,
.slide-leave-to {
    transform: translateX(100%);
}
</style>
