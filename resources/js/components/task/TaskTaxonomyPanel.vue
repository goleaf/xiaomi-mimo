<script setup lang="ts">
import { useHttp } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label as FormLabel } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import {
    attach as attachLabel,
    detach as detachLabel,
} from '@/routes/api/v1/labels';
import { attach as attachTag, detach as detachTag } from '@/routes/api/v1/tags';
import type { Label, Tag, Todo } from '@/types/models';

const props = defineProps<{
    todo: Todo;
    availableLabels: Label[];
    availableTags: Tag[];
}>();
const emit = defineEmits<{ refresh: [] }>();
const toast = useToast();
const { t } = useUi();
const busyKey = ref<string | null>(null);
const labelRequest = useHttp<{ label_id: string }, { data: Label }>({
    label_id: '',
});
const tagRequest = useHttp<{ tag_id: string }, { data: Tag }>({ tag_id: '' });
const deleteRequest = useHttp<Record<string, never>, undefined>({});

function hasLabel(labelId: string): boolean {
    return props.todo.labels?.some((label) => label.id === labelId) ?? false;
}

function hasTag(tagId: string): boolean {
    return props.todo.tags?.some((tag) => tag.id === tagId) ?? false;
}

async function toggleLabel(label: Label): Promise<void> {
    if (busyKey.value) {
        return;
    }

    busyKey.value = `label:${label.id}`;

    try {
        if (hasLabel(label.id)) {
            await deleteRequest.delete(
                detachLabel([props.todo.workspace_id, props.todo, label]).url,
            );
        } else {
            labelRequest.label_id = label.id;
            await labelRequest.post(
                attachLabel([props.todo.workspace_id, props.todo]).url,
            );
        }

        emit('refresh');
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}

async function toggleTag(tag: Tag): Promise<void> {
    if (busyKey.value) {
        return;
    }

    busyKey.value = `tag:${tag.id}`;

    try {
        if (hasTag(tag.id)) {
            await deleteRequest.delete(
                detachTag([props.todo.workspace_id, props.todo, tag]).url,
            );
        } else {
            tagRequest.tag_id = tag.id;
            await tagRequest.post(
                attachTag([props.todo.workspace_id, props.todo]).url,
            );
        }

        emit('refresh');
    } catch {
        toast.error(t('common.errors.generic'));
    } finally {
        busyKey.value = null;
    }
}
</script>

<template>
    <section class="rounded-[1.5rem] border border-border/80 bg-card p-5">
        <h2 class="text-base font-semibold">
            {{ t('tasks.detail.labels_and_tags') }}
        </h2>
        <div class="mt-4 space-y-5">
            <fieldset class="space-y-2">
                <legend class="mb-2 text-sm font-medium">
                    {{ t('tasks.detail.labels') }}
                </legend>
                <div
                    v-if="availableLabels.length"
                    class="grid gap-2 sm:grid-cols-2"
                >
                    <div
                        v-for="label in availableLabels"
                        :key="label.id"
                        class="flex min-h-11 items-center gap-3 rounded-xl border border-border/70 bg-muted/20 px-3"
                    >
                        <Checkbox
                            :id="`detail-label-${todo.id}-${label.id}`"
                            :model-value="hasLabel(label.id)"
                            :disabled="busyKey !== null"
                            @update:model-value="toggleLabel(label)"
                        />
                        <FormLabel
                            :for="`detail-label-${todo.id}-${label.id}`"
                            class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 font-normal"
                        >
                            <span
                                class="size-2.5 rounded-full"
                                :style="{ backgroundColor: label.color }"
                                aria-hidden="true"
                            />
                            <span class="truncate">{{ label.name }}</span>
                            <Spinner
                                v-if="busyKey === `label:${label.id}`"
                                class="ml-auto"
                            />
                        </FormLabel>
                    </div>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('tasks.detail.no_labels') }}
                </p>
            </fieldset>
            <fieldset class="space-y-2">
                <legend class="mb-2 text-sm font-medium">
                    {{ t('tasks.detail.tags') }}
                </legend>
                <div v-if="availableTags.length" class="flex flex-wrap gap-2">
                    <button
                        v-for="tag in availableTags"
                        :key="tag.id"
                        type="button"
                        class="rounded-lg focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none"
                        :aria-pressed="hasTag(tag.id)"
                        :disabled="busyKey !== null"
                        @click="toggleTag(tag)"
                    >
                        <Badge
                            :variant="hasTag(tag.id) ? 'default' : 'outline'"
                            class="gap-1.5"
                        >
                            <Spinner v-if="busyKey === `tag:${tag.id}`" />
                            {{ tag.name }}
                        </Badge>
                    </button>
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('tasks.detail.no_tags') }}
                </p>
            </fieldset>
        </div>
    </section>
</template>
