<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import type { Todo, Comment, Checklist, ChecklistItem, Label, Tag, Attachment } from '@/types/models';
import { useToast } from '@/composables/useToast';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { X, Calendar, User, Tag as TagIcon, Paperclip, MessageSquare, CheckSquare, Clock } from '@lucide/vue';

const props = defineProps<{ todo: Todo; open: boolean }>();
const emit = defineEmits<{ close: [] }>();
const toast = useToast();

const editingTitle = ref(false);
const titleValue = ref(props.todo.title);
const newComment = ref('');
const newChecklistName = ref('');
const newChecklistItemContent = ref('');

function updateTitle() {
    if (titleValue.value.trim() && titleValue.value !== props.todo.title) {
        router.put(route('todos.update', props.todo.id), { title: titleValue.value }, { preserveScroll: true });
    }
    editingTitle.value = false;
}

function toggleComplete() {
    const routeName = props.todo.status === 'completed' ? 'todos.uncomplete' : 'todos.complete';
    router.post(route(routeName, props.todo.id), {}, { preserveScroll: true });
}

function setPriority(priority: string) {
    router.put(route('todos.update', props.todo.id), { priority }, { preserveScroll: true });
}

function setStatus(status: string) {
    router.put(route('todos.update', props.todo.id), { status }, { preserveScroll: true });
}

function addComment() {
    if (!newComment.value.trim()) return;
    router.post(route('comments.store', props.todo.id), { body: newComment.value }, {
        preserveScroll: true,
        onSuccess: () => { newComment.value = ''; },
    });
}

function addChecklist() {
    if (!newChecklistName.value.trim()) return;
    router.post(route('checklists.store', props.todo.id), { name: newChecklistName.value }, {
        preserveScroll: true,
        onSuccess: () => { newChecklistName.value = ''; },
    });
}

function addChecklistItem(checklistId: string) {
    if (!newChecklistItemContent.value.trim()) return;
    router.post(route('checklistItems.store', checklistId), { content: newChecklistItemContent.value }, {
        preserveScroll: true,
        onSuccess: () => { newChecklistItemContent.value = ''; },
    });
}

function toggleChecklistItem(itemId: string) {
    router.patch(route('checklistItems.toggle', itemId), {}, { preserveScroll: true });
}

function deleteTodo() {
    router.delete(route('todos.destroy', props.todo.id), {
        preserveScroll: true,
        onSuccess: () => { emit('close'); toast.success('Task deleted'); },
    });
}

function formatDate(date: string | null): string {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const priorityOptions = ['none', 'low', 'medium', 'high', 'urgent'];
const statusOptions = ['pending', 'in_progress', 'completed'];
</script>

<template>
    <Teleport to="body">
        <Transition name="slide">
            <div v-if="open" class="fixed inset-y-0 right-0 z-50 w-full max-w-lg border-l bg-background shadow-xl overflow-y-auto">
                <div class="sticky top-0 z-10 flex items-center justify-between border-b bg-background p-4">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" :checked="todo.status === 'completed'" class="h-4 w-4" @change="toggleComplete" />
                        <span class="text-sm text-muted-foreground">Task Detail</span>
                    </div>
                    <Button variant="ghost" size="sm" @click="emit('close')"><X class="h-4 w-4" /></Button>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Title -->
                    <div>
                        <input
                            v-if="editingTitle"
                            v-model="titleValue"
                            class="w-full text-lg font-semibold border-b border-primary outline-none pb-1"
                            @blur="updateTitle"
                            @keyup.enter="updateTitle"
                            autofocus
                        />
                        <h2 v-else class="text-lg font-semibold cursor-pointer hover:underline" @click="editingTitle = true">{{ todo.title }}</h2>
                    </div>

                    <!-- Status -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground w-24">Status</span>
                        <div class="flex gap-1">
                            <Badge v-for="s in statusOptions" :key="s" :variant="todo.status === s ? 'default' : 'outline'" class="cursor-pointer" @click="setStatus(s)">{{ s.replace('_', ' ') }}</Badge>
                        </div>
                    </div>

                    <!-- Priority -->
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-muted-foreground w-24">Priority</span>
                        <div class="flex gap-1">
                            <Badge v-for="p in priorityOptions" :key="p" :variant="todo.priority === p ? 'default' : 'outline'" class="cursor-pointer" @click="setPriority(p)">{{ p }}</Badge>
                        </div>
                    </div>

                    <!-- Due Date -->
                    <div class="flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm">Due: {{ formatDate(todo.due_date) }}</span>
                    </div>

                    <!-- Assignee -->
                    <div class="flex items-center gap-2">
                        <User class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm">Assigned: {{ todo.assignee?.name ?? 'Unassigned' }}</span>
                    </div>

                    <Separator />

                    <!-- Labels -->
                    <div v-if="todo.labels?.length">
                        <h3 class="text-sm font-medium mb-2">Labels</h3>
                        <div class="flex flex-wrap gap-1">
                            <Badge v-for="label in todo.labels" :key="label.id" :style="{ backgroundColor: label.color, color: 'white' }">{{ label.name }}</Badge>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="todo.tags?.length">
                        <h3 class="text-sm font-medium mb-2">Tags</h3>
                        <div class="flex flex-wrap gap-1">
                            <Badge v-for="tag in todo.tags" :key="tag.id" variant="secondary">{{ tag.name }}</Badge>
                        </div>
                    </div>

                    <Separator />

                    <!-- Description -->
                    <div>
                        <h3 class="text-sm font-medium mb-2">Description</h3>
                        <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ todo.description ?? 'No description' }}</p>
                    </div>

                    <Separator />

                    <!-- Checklists -->
                    <div>
                        <h3 class="text-sm font-medium mb-2">Checklists</h3>
                        <div v-for="checklist in todo.checklists ?? []" :key="checklist.id" class="mb-3 border rounded-lg p-3">
                            <p class="text-sm font-medium mb-2">{{ checklist.name }}</p>
                            <div v-for="item in checklist.items ?? []" :key="item.id" class="flex items-center gap-2 py-1">
                                <input type="checkbox" :checked="item.is_checked" class="h-3 w-3" @change="toggleChecklistItem(item.id)" />
                                <span :class="['text-sm', item.is_checked ? 'line-through text-muted-foreground' : '']">{{ item.content }}</span>
                            </div>
                            <div class="flex gap-2 mt-2">
                                <Input v-model="newChecklistItemContent" placeholder="Add item..." class="h-8 text-xs" @keyup.enter="addChecklistItem(checklist.id)" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <Input v-model="newChecklistName" placeholder="New checklist name..." class="h-8 text-xs" @keyup.enter="addChecklist" />
                            <Button variant="outline" size="sm" @click="addChecklist">Add</Button>
                        </div>
                    </div>

                    <Separator />

                    <!-- Comments -->
                    <div>
                        <h3 class="text-sm font-medium mb-2">Comments</h3>
                        <div v-for="comment in todo.comments ?? []" :key="comment.id" class="mb-3 border rounded-lg p-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium">{{ comment.user?.name ?? 'Unknown' }}</span>
                                <span class="text-xs text-muted-foreground">{{ formatDate(comment.created_at) }}</span>
                            </div>
                            <p class="text-sm whitespace-pre-wrap">{{ comment.body }}</p>
                        </div>
                        <div class="flex gap-2">
                            <Input v-model="newComment" placeholder="Write a comment..." class="h-8 text-xs" @keyup.enter="addComment" />
                            <Button variant="outline" size="sm" @click="addComment">Post</Button>
                        </div>
                    </div>

                    <Separator />

                    <!-- Danger Zone -->
                    <div class="pt-4">
                        <Button variant="destructive" size="sm" @click="deleteTodo">Delete Task</Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: transform 0.3s ease; }
.slide-enter-from, .slide-leave-to { transform: translateX(100%); }
</style>
