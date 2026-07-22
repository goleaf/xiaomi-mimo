<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { Download, Paperclip, Trash2, Upload } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { destroy, store } from '@/routes/api/v1/attachments';
import type { Attachment } from '@/types/models';

const props = defineProps<{
    todoId: string;
    initialAttachments: Attachment[];
}>();
const toast = useToast();
const { formatNumber, t } = useUi();
const attachments = ref<Attachment[]>([]);
const attachmentToDelete = ref<Attachment | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadRequest = useHttp<{ file: File | null }, { data: Attachment }>({
    file: null,
});
const deleteRequest = useHttp<Record<string, never>, undefined>({});

watch(
    () => props.todoId,
    () => {
        attachments.value = [...props.initialAttachments];
        attachmentToDelete.value = null;
        uploadRequest.resetAndClearErrors();
    },
    { immediate: true, flush: 'sync' },
);

function chooseFile(event: Event): void {
    const target = event.target as HTMLInputElement;
    uploadRequest.file = target.files?.[0] ?? null;
    uploadRequest.clearErrors('file');
}

async function upload(): Promise<void> {
    if (!uploadRequest.file || uploadRequest.processing) {
        return;
    }

    try {
        const response = await uploadRequest.post(store(props.todoId).url);
        attachments.value.push(response.data);
        uploadRequest.resetAndClearErrors();

        if (fileInput.value) {
            fileInput.value.value = '';
        }

        toast.success(t('tasks.detail.attachment_uploaded'));
    } catch {
        if (!uploadRequest.hasErrors) {
            toast.error(t('common.errors.generic'));
        }
    }
}

async function deleteAttachment(): Promise<void> {
    if (!attachmentToDelete.value || deleteRequest.processing) {
        return;
    }

    const attachment = attachmentToDelete.value;

    try {
        await deleteRequest.delete(destroy([props.todoId, attachment]).url);
        attachments.value = attachments.value.filter(
            (item) => item.id !== attachment.id,
        );
        attachmentToDelete.value = null;
        toast.success(t('tasks.detail.attachment_deleted'));
    } catch {
        toast.error(t('common.errors.generic'));
    }
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${formatNumber(bytes)} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${formatNumber(bytes / 1024, { maximumFractionDigits: 1 })} KB`;
    }

    return `${formatNumber(bytes / (1024 * 1024), { maximumFractionDigits: 1 })} MB`;
}
</script>

<template>
    <section class="rounded-[1.5rem] border border-border/80 bg-card p-5">
        <div class="flex items-center gap-2">
            <Paperclip class="size-4 text-orange-700" aria-hidden="true" />
            <h2 class="text-base font-semibold">
                {{ t('tasks.detail.attachments') }}
            </h2>
        </div>

        <form class="mt-4 space-y-2" @submit.prevent="upload">
            <div class="flex flex-col gap-2 sm:flex-row">
                <Input
                    ref="fileInput"
                    type="file"
                    accept=".jpg,.jpeg,.png,.webp,.pdf,.txt,.csv,.json"
                    :disabled="uploadRequest.processing"
                    :aria-invalid="Boolean(uploadRequest.errors.file)"
                    @change="chooseFile"
                />
                <Button
                    type="submit"
                    variant="outline"
                    :disabled="uploadRequest.processing || !uploadRequest.file"
                >
                    <Spinner v-if="uploadRequest.processing" />
                    <Upload v-else class="size-4" aria-hidden="true" />
                    {{ t('tasks.detail.upload') }}
                </Button>
            </div>
            <progress
                v-if="uploadRequest.progress"
                class="h-2 w-full accent-orange-600"
                :value="uploadRequest.progress.percentage"
                max="100"
                :aria-label="t('tasks.detail.upload_progress')"
            />
            <InputError :message="uploadRequest.errors.file" />
            <p class="text-xs text-muted-foreground">
                {{ t('tasks.detail.attachment_help') }}
            </p>
        </form>

        <div class="mt-5 space-y-2">
            <div
                v-for="attachment in attachments"
                :key="attachment.id"
                class="flex items-center gap-3 rounded-xl border border-border/70 bg-muted/20 p-3"
            >
                <Paperclip class="size-4 shrink-0 text-muted-foreground" />
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium">
                        {{ attachment.filename }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ formatSize(attachment.size) }}
                    </p>
                </div>
                <Button as-child variant="ghost" size="icon-sm">
                    <a
                        :href="attachment.download_url"
                        :aria-label="
                            t('tasks.detail.download_attachment', {
                                name: attachment.filename,
                            })
                        "
                    >
                        <Download class="size-4" aria-hidden="true" />
                    </a>
                </Button>
                <Button
                    v-if="attachment.permissions?.delete"
                    variant="ghost"
                    size="icon-sm"
                    class="text-muted-foreground hover:text-destructive"
                    :aria-label="
                        t('tasks.detail.delete_attachment', {
                            name: attachment.filename,
                        })
                    "
                    :disabled="deleteRequest.processing"
                    @click="attachmentToDelete = attachment"
                >
                    <Trash2 class="size-4" aria-hidden="true" />
                </Button>
            </div>
            <p
                v-if="attachments.length === 0"
                class="rounded-xl border border-dashed border-border/80 px-4 py-6 text-center text-sm text-muted-foreground"
            >
                {{ t('tasks.detail.no_attachments') }}
            </p>
        </div>
    </section>

    <WorkspaceConfirmDialog
        :open="attachmentToDelete !== null"
        :title="t('tasks.detail.delete_attachment_title')"
        :description="t('tasks.detail.delete_attachment_description')"
        :confirm-label="t('common.actions.delete')"
        :cancel-label="t('common.actions.cancel')"
        :processing="deleteRequest.processing"
        @update:open="!$event && (attachmentToDelete = null)"
        @confirm="deleteAttachment"
    />
</template>
