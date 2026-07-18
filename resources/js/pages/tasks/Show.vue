<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { Todo, Comment as TodoComment, Checklist, ChecklistItem, Label, Tag, Attachment } from '@/types/models';
import { useToast } from '@/composables/useToast';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Calendar, User, CheckSquare, MessageSquare, Paperclip, Clock } from '@lucide/vue';

const props = defineProps<{ todo: { data: Todo } }>();
const toast = useToast();
const todo = computed(() => props.todo.data);

const newComment = ref('');
const newChecklistName = ref('');
const newChecklistItemContent = ref('');

function toggleComplete() {
    const routeName = todo.value.status === 'completed' ? 'todos.uncomplete' : 'todos.complete';
    router.post(route(routeName, todo.value.id), {}, { preserveScroll: true });
}

function addComment() {
    if (!newComment.value.trim()) return;
    router.post(route('comments.store', todo.value.id), { body: newComment.value }, {
        preserveScroll: true, onSuccess: () => { newComment.value = ''; },
    });
}

function addChecklist() {
    if (!newChecklistName.value.trim()) return;
    router.post(route('checklists.store', todo.value.id), { name: newChecklistName.value }, {
        preserveScroll: true, onSuccess: () => { newChecklistName.value = ''; },
    });
}

function addChecklistItem(checklistId: string) {
    if (!newChecklistItemContent.value.trim()) return;
    router.post(route('checklistItems.store', checklistId), { content: newChecklistItemContent.value }, {
        preserveScroll: true, onSuccess: () => { newChecklistItemContent.value = ''; },
    });
}

function toggleChecklistItem(itemId: string) {
    router.patch(route('checklistItems.toggle', itemId), {}, { preserveScroll: true });
}

function goBack() { window.history.back(); }
function formatDate(date: string | null): string {
    if (!date) return 'Not set';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}
const priorityBadge = (p: string) => ({ urgent: 'destructive', high: 'destructive', medium: 'secondary', low: 'outline', none: 'outline' }[p] ?? 'outline');
</script>

<template>
    <Head :title="todo.title" />
    <div class="space-y-6 p-6 max-w-3xl">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" @click="goBack"><ArrowLeft class="h-4 w-4" /></Button>
            <div class="flex-1">
                <h1 class="text-2xl font-bold">{{ todo.title }}</h1>
                <div class="flex items-center gap-3 mt-1">
                    <Badge :variant="priorityBadge(todo.priority)">{{ todo.priority }}</Badge>
                    <span class="text-sm text-muted-foreground">{{ todo.status?.replace('_', ' ') }}</span>
                    <span v-if="todo.project" class="text-sm text-muted-foreground">in {{ todo.project.name }}</span>
                </div>
            </div>
            <Button :variant="todo.status === 'completed' ? 'outline' : 'default'" @click="toggleComplete">
                {{ todo.status === 'completed' ? 'Reopen' : 'Complete' }}
            </Button>
        </div>

        <!-- Metadata -->
        <div class="grid grid-cols-2 gap-4">
            <Card><CardContent class="pt-4 space-y-1">
                <div class="flex items-center gap-2 text-sm"><Calendar class="h-4 w-4 text-muted-foreground" />Due: {{ formatDate(todo.due_date) }}</div>
                <div class="flex items-center gap-2 text-sm"><User class="h-4 w-4 text-muted-foreground" />Assigned: {{ todo.assignee?.name ?? 'Unassigned' }}</div>
                <div class="flex items-center gap-2 text-sm"><Clock class="h-4 w-4 text-muted-foreground" />Created: {{ formatDate(todo.created_at) }}</div>
            </CardContent></Card>
            <Card><CardContent class="pt-4">
                <div v-if="todo.labels?.length" class="mb-2">
                    <p class="text-xs font-medium text-muted-foreground mb-1">Labels</p>
                    <div class="flex flex-wrap gap-1">
                        <Badge v-for="l in todo.labels" :key="l.id" :style="{ backgroundColor: l.color, color: 'white' }" class="text-xs">{{ l.name }}</Badge>
                    </div>
                </div>
                <div v-if="todo.tags?.length">
                    <p class="text-xs font-medium text-muted-foreground mb-1">Tags</p>
                    <div class="flex flex-wrap gap-1">
                        <Badge v-for="t in todo.tags" :key="t.id" variant="secondary" class="text-xs">{{ t.name }}</Badge>
                    </div>
                </div>
            </CardContent></Card>
        </div>

        <!-- Description -->
        <Card v-if="todo.description">
            <CardHeader><CardTitle class="text-base">Description</CardTitle></CardHeader>
            <CardContent><p class="text-sm whitespace-pre-wrap">{{ todo.description }}</p></CardContent>
        </Card>

        <!-- Checklists -->
        <Card v-if="todo.checklists?.length">
            <CardHeader><CardTitle class="text-base">Checklists</CardTitle></CardHeader>
            <CardContent class="space-y-4">
                <div v-for="cl in todo.checklists" :key="cl.id" class="border rounded-lg p-3">
                    <p class="text-sm font-medium mb-2">{{ cl.name }}</p>
                    <div v-for="item in cl.items ?? []" :key="item.id" class="flex items-center gap-2 py-1">
                        <input type="checkbox" :checked="item.is_checked" class="h-3 w-3" @change="toggleChecklistItem(item.id)" />
                        <span :class="['text-sm', item.is_checked ? 'line-through text-muted-foreground' : '']">{{ item.content }}</span>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <Input v-model="newChecklistItemContent" placeholder="Add item..." class="h-8 text-xs" @keyup.enter="addChecklistItem(cl.id)" />
                    </div>
                </div>
                <div class="flex gap-2">
                    <Input v-model="newChecklistName" placeholder="New checklist..." class="h-8 text-xs" @keyup.enter="addChecklist" />
                    <Button variant="outline" size="sm" @click="addChecklist">Add</Button>
                </div>
            </CardContent>
        </Card>

        <!-- Comments -->
        <Card>
            <CardHeader class="flex flex-row items-center gap-2">
                <MessageSquare class="h-4 w-4" />
                <CardTitle class="text-base">Comments ({{ todo.comments?.length ?? 0 }})</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div v-for="comment in todo.comments ?? []" :key="comment.id" class="border rounded-lg p-3">
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
            </CardContent>
        </Card>
    </div>
</template>
