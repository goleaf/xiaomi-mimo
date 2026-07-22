<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { MessageSquare, Pencil, Trash2 } from '@lucide/vue';
import { reactive, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { destroy, index, store, update } from '@/routes/api/v1/comments';
import type { CursorPaginatedResponse } from '@/types/api';
import type { Comment } from '@/types/models';

const props = defineProps<{
    todoId: string;
    initialComments: Comment[];
    total: number;
}>();
const toast = useToast();
const { formatDate, formatNumber, t } = useUi();
const comments = ref<Comment[]>([]);
const commentTotal = ref(0);
const nextUrl = ref<string | null>(null);
const newComment = ref('');
const editingId = ref<string | null>(null);
const editDrafts = reactive<Record<string, string>>({});
const deletingComment = ref<Comment | null>(null);
const busyKey = ref<string | null>(null);
const listRequest = useHttp<
    Record<string, never>,
    CursorPaginatedResponse<Comment>
>({});
const commentRequest = useHttp<{ body: string }, { data: Comment }>({
    body: '',
});
const deleteRequest = useHttp<Record<string, never>, undefined>({});

function reset(): void {
    comments.value = [...props.initialComments];
    commentTotal.value = props.total;
    nextUrl.value = null;
    newComment.value = '';
    editingId.value = null;
    deletingComment.value = null;
    busyKey.value = null;

    for (const key of Object.keys(editDrafts)) {
        delete editDrafts[key];
    }

    void loadFirstPage();
}

watch(() => props.todoId, reset, { immediate: true, flush: 'sync' });

async function loadFirstPage(): Promise<void> {
    try {
        const response = await listRequest.get(
            index.url(props.todoId, { query: { per_page: 20 } }),
        );
        comments.value = response.data;
        nextUrl.value = response.links.next;
    } catch {
        toast.error(t('tasks.detail.comments_load_failed'));
    }
}

async function loadMore(): Promise<void> {
    if (!nextUrl.value || listRequest.processing) {
        return;
    }

    try {
        const response = await listRequest.get(nextUrl.value);
        const seen = new Set(comments.value.map((comment) => comment.id));
        comments.value.push(
            ...response.data.filter((comment) => !seen.has(comment.id)),
        );
        nextUrl.value = response.links.next;
    } catch {
        toast.error(t('tasks.detail.comments_load_failed'));
    }
}

async function createComment(): Promise<void> {
    if (!newComment.value.trim() || busyKey.value) {
        return;
    }

    busyKey.value = 'comment:new';
    commentRequest.body = newComment.value;

    try {
        const response = await commentRequest.post(store(props.todoId).url);
        comments.value.unshift(response.data);
        commentTotal.value++;
        newComment.value = '';
    } catch {
        if (!commentRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    } finally {
        busyKey.value = null;
    }
}

function startEditing(comment: Comment): void {
    editDrafts[comment.id] = comment.body;
    editingId.value = comment.id;
    commentRequest.clearErrors();
}

async function saveComment(comment: Comment): Promise<void> {
    const body = editDrafts[comment.id]?.trim();

    if (!body || body === comment.body || busyKey.value) {
        editingId.value = null;

        return;
    }

    busyKey.value = `comment:${comment.id}`;
    commentRequest.body = body;

    try {
        const response = await commentRequest.put(
            update([props.todoId, comment]).url,
        );
        const commentIndex = comments.value.findIndex(
            (item) => item.id === comment.id,
        );

        if (commentIndex >= 0) {
            comments.value[commentIndex] = response.data;
        }

        editingId.value = null;
    } catch {
        if (!commentRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    } finally {
        busyKey.value = null;
    }
}

async function deleteComment(): Promise<void> {
    if (!deletingComment.value || busyKey.value) {
        return;
    }

    const comment = deletingComment.value;
    busyKey.value = `comment:delete:${comment.id}`;

    try {
        await deleteRequest.delete(destroy([props.todoId, comment]).url);
        comments.value = comments.value.filter(
            (item) => item.id !== comment.id,
        );
        commentTotal.value = Math.max(0, commentTotal.value - 1);
        deletingComment.value = null;
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}
</script>

<template>
    <section class="rounded-[1.5rem] border border-border/80 bg-card p-5">
        <div class="flex items-center gap-2">
            <MessageSquare class="size-4 text-orange-700" aria-hidden="true" />
            <h2 class="text-base font-semibold">
                {{
                    t('tasks.detail.comments_count', {
                        count: formatNumber(commentTotal),
                    })
                }}
            </h2>
        </div>

        <form class="mt-4 space-y-2" @submit.prevent="createComment">
            <textarea
                v-model="newComment"
                rows="3"
                maxlength="5000"
                :placeholder="t('tasks.detail.comment_placeholder')"
                :disabled="busyKey !== null"
                :aria-invalid="Boolean(commentRequest.errors.body)"
                class="flex min-h-20 w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-orange-500 focus-visible:ring-[3px] focus-visible:ring-orange-500/20 disabled:opacity-50 aria-invalid:border-destructive"
                @input="commentRequest.clearErrors('body')"
            />
            <InputError :message="commentRequest.errors.body" />
            <div class="flex justify-end">
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="busyKey !== null || !newComment.trim()"
                >
                    <Spinner v-if="busyKey === 'comment:new'" />
                    {{ t('common.actions.post') }}
                </Button>
            </div>
        </form>

        <div class="mt-5 space-y-3">
            <article
                v-for="comment in comments"
                :key="comment.id"
                class="rounded-2xl border border-border/70 bg-muted/20 p-4"
            >
                <header class="flex flex-wrap items-start gap-2">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">
                            {{
                                comment.user?.name ?? t('common.states.unknown')
                            }}
                        </p>
                        <time class="text-xs text-muted-foreground">
                            {{
                                formatDate(comment.created_at, {
                                    dateStyle: 'medium',
                                    timeStyle: 'short',
                                })
                            }}
                        </time>
                    </div>
                    <Button
                        v-if="comment.permissions?.update"
                        variant="ghost"
                        size="icon-sm"
                        :aria-label="t('tasks.detail.edit_comment')"
                        :disabled="busyKey !== null"
                        @click="startEditing(comment)"
                    >
                        <Pencil class="size-4" aria-hidden="true" />
                    </Button>
                    <Button
                        v-if="comment.permissions?.delete"
                        variant="ghost"
                        size="icon-sm"
                        class="text-muted-foreground hover:text-destructive"
                        :aria-label="t('tasks.detail.delete_comment')"
                        :disabled="busyKey !== null"
                        @click="deletingComment = comment"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                    </Button>
                </header>

                <form
                    v-if="editingId === comment.id"
                    class="mt-3 space-y-2"
                    @submit.prevent="saveComment(comment)"
                >
                    <textarea
                        v-model="editDrafts[comment.id]"
                        rows="3"
                        maxlength="5000"
                        :disabled="busyKey !== null"
                        class="flex min-h-20 w-full rounded-xl border border-input bg-background px-3.5 py-2.5 text-sm outline-none focus-visible:border-orange-500 focus-visible:ring-[3px] focus-visible:ring-orange-500/20"
                    />
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            :disabled="busyKey !== null"
                            @click="editingId = null"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                        <Button type="submit" :disabled="busyKey !== null">
                            <Spinner
                                v-if="busyKey === `comment:${comment.id}`"
                            />
                            {{ t('common.actions.save') }}
                        </Button>
                    </div>
                </form>
                <p v-else class="mt-3 text-sm leading-6 whitespace-pre-wrap">
                    {{ comment.body }}
                </p>
            </article>

            <p
                v-if="comments.length === 0 && !listRequest.processing"
                class="rounded-xl border border-dashed border-border/80 px-4 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('tasks.detail.no_comments') }}
            </p>

            <Button
                v-if="nextUrl"
                variant="outline"
                class="w-full"
                :disabled="listRequest.processing"
                @click="loadMore"
            >
                <Spinner v-if="listRequest.processing" />
                {{ t('tasks.detail.load_older_comments') }}
            </Button>
        </div>
    </section>

    <WorkspaceConfirmDialog
        :open="deletingComment !== null"
        :title="t('tasks.detail.delete_comment_title')"
        :description="t('tasks.detail.delete_comment_description')"
        :confirm-label="t('common.actions.delete')"
        :cancel-label="t('common.actions.cancel')"
        :processing="busyKey?.startsWith('comment:delete:') ?? false"
        @update:open="!$event && (deletingComment = null)"
        @confirm="deleteComment"
    />
</template>
