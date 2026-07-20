<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import {
    ListChecks,
    Palette,
    Pencil,
    Plus,
    Search,
    Tag as TagIcon,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label as FormLabel } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import WorkspaceTaskDefinitionsPanel from '@/components/workspace/WorkspaceTaskDefinitionsPanel.vue';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import type {
    Label,
    Tag,
    TaskPriorityDefinition,
    TaskStatusDefinition,
    Workspace,
    WorkspaceMetadataRouteUrls,
} from '@/types/models';

type DeleteTarget = { kind: 'label'; item: Label } | { kind: 'tag'; item: Tag };

const props = defineProps<{
    workspace: Workspace;
    labels: Label[];
    tags: Tag[];
    taskStatuses: TaskStatusDefinition[];
    taskPriorities: TaskPriorityDefinition[];
    locale: string;
    routes: WorkspaceMetadataRouteUrls;
}>();

const toast = useToast();
const { formatNumber, t } = useUi();
const searchQuery = ref('');
const editingLabel = ref<Label | null>(null);
const editingTag = ref<Tag | null>(null);
const deleteTarget = ref<DeleteTarget | null>(null);
const labelForm = useHttp<{ name: string; color: string }>({
    name: '',
    color: '#6366f1',
});
const tagForm = useHttp<{ name: string }>({ name: '' });
const editLabelForm = useHttp<{ name: string; color: string }>({
    name: '',
    color: '#6366f1',
});
const editTagForm = useHttp<{ name: string }>({ name: '' });
const deleteRequest = useHttp<Record<string, never>>({});

const canManage = computed(
    () => props.workspace.permissions?.manage_task_configuration === true,
);
const normalizedSearch = computed(() =>
    searchQuery.value.trim().toLocaleLowerCase(props.locale),
);
const filteredLabels = computed(() => {
    if (!normalizedSearch.value) {
        return props.labels;
    }

    return props.labels.filter((label) =>
        label.name
            .toLocaleLowerCase(props.locale)
            .includes(normalizedSearch.value),
    );
});
const filteredTags = computed(() => {
    if (!normalizedSearch.value) {
        return props.tags;
    }

    return props.tags.filter((tag) =>
        tag.name
            .toLocaleLowerCase(props.locale)
            .includes(normalizedSearch.value),
    );
});
const deleteTitle = computed(() =>
    deleteTarget.value?.kind === 'label'
        ? t('workspaces.management.configuration.labels.delete_title')
        : t('workspaces.management.configuration.tags.delete_title'),
);
const deleteDescription = computed(() => {
    const target = deleteTarget.value;

    if (!target) {
        return '';
    }

    return t(
        `workspaces.management.configuration.${target.kind === 'label' ? 'labels' : 'tags'}.delete_description`,
        {
            name: target.item.name,
            count: formatNumber(target.item.todos_count ?? 0),
        },
    );
});

function reloadMetadata(): void {
    router.reload({ only: ['workspace', 'labels', 'tags'] });
}

async function createLabel(): Promise<void> {
    labelForm.name = labelForm.name.trim();

    if (!labelForm.name) {
        return;
    }

    try {
        await labelForm.post(props.routes.storeLabel);

        if (!labelForm.wasSuccessful) {
            return;
        }

        toast.success(t('workspaces.management.configuration.labels.created'));
        labelForm.resetAndClearErrors();
        labelForm.name = '';
        labelForm.color = '#6366f1';
        reloadMetadata();
    } catch {
        toast.error(
            t('workspaces.management.configuration.labels.create_failed'),
        );
    }
}

async function createTag(): Promise<void> {
    tagForm.name = tagForm.name.trim();

    if (!tagForm.name) {
        return;
    }

    try {
        await tagForm.post(props.routes.storeTag);

        if (!tagForm.wasSuccessful) {
            return;
        }

        toast.success(t('workspaces.management.configuration.tags.created'));
        tagForm.resetAndClearErrors();
        tagForm.name = '';
        reloadMetadata();
    } catch {
        toast.error(
            t('workspaces.management.configuration.tags.create_failed'),
        );
    }
}

function startEditingLabel(label: Label): void {
    editingTag.value = null;
    editingLabel.value = label;
    editLabelForm.name = label.name;
    editLabelForm.color = label.color;
    editLabelForm.clearErrors();
}

function cancelEditingLabel(): void {
    editingLabel.value = null;
    editLabelForm.resetAndClearErrors();
}

async function updateLabel(): Promise<void> {
    const label = editingLabel.value;

    if (!label) {
        return;
    }

    editLabelForm.name = editLabelForm.name.trim();

    try {
        await editLabelForm.put(props.routes.updateLabel(label.id));

        if (!editLabelForm.wasSuccessful) {
            return;
        }

        toast.success(t('workspaces.management.configuration.labels.updated'));
        cancelEditingLabel();
        reloadMetadata();
    } catch {
        toast.error(
            t('workspaces.management.configuration.labels.save_failed'),
        );
    }
}

function startEditingTag(tag: Tag): void {
    editingLabel.value = null;
    editingTag.value = tag;
    editTagForm.name = tag.name;
    editTagForm.clearErrors();
}

function cancelEditingTag(): void {
    editingTag.value = null;
    editTagForm.resetAndClearErrors();
}

async function updateTag(): Promise<void> {
    const tag = editingTag.value;

    if (!tag) {
        return;
    }

    editTagForm.name = editTagForm.name.trim();

    try {
        await editTagForm.put(props.routes.updateTag(tag.id));

        if (!editTagForm.wasSuccessful) {
            return;
        }

        toast.success(t('workspaces.management.configuration.tags.updated'));
        cancelEditingTag();
        reloadMetadata();
    } catch {
        toast.error(t('workspaces.management.configuration.tags.save_failed'));
    }
}

function setDeleteConfirmation(open: boolean): void {
    if (!open && !deleteRequest.processing) {
        deleteTarget.value = null;
    }
}

async function deleteMetadata(): Promise<void> {
    const target = deleteTarget.value;

    if (!target) {
        return;
    }

    const url =
        target.kind === 'label'
            ? props.routes.deleteLabel(target.item.id)
            : props.routes.deleteTag(target.item.id);

    try {
        await deleteRequest.delete(url);

        if (!deleteRequest.wasSuccessful) {
            toast.error(
                t(
                    `workspaces.management.configuration.${target.kind === 'label' ? 'labels' : 'tags'}.delete_failed`,
                ),
            );

            return;
        }

        toast.success(
            t(
                `workspaces.management.configuration.${target.kind === 'label' ? 'labels' : 'tags'}.deleted`,
            ),
        );
        deleteTarget.value = null;
        reloadMetadata();
    } catch {
        toast.error(
            t(
                `workspaces.management.configuration.${target.kind === 'label' ? 'labels' : 'tags'}.delete_failed`,
            ),
        );
    }
}
</script>

<template>
    <section class="space-y-6" aria-labelledby="workspace-configuration-title">
        <div
            class="flex flex-col gap-4 rounded-2xl border border-orange-500/15 bg-orange-500/[0.04] p-5 sm:flex-row sm:items-end sm:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex items-center gap-3">
                    <div
                        class="flex size-11 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <ListChecks class="size-5" aria-hidden="true" />
                    </div>
                    <div>
                        <h2
                            id="workspace-configuration-title"
                            class="text-xl font-semibold tracking-tight"
                        >
                            {{ t('workspaces.management.configuration.title') }}
                        </h2>
                        <p class="mt-1 text-sm leading-6 text-muted-foreground">
                            {{
                                t(
                                    'workspaces.management.configuration.description',
                                )
                            }}
                        </p>
                    </div>
                </div>
            </div>
            <div class="w-full sm:max-w-sm">
                <FormLabel for="metadata-search" class="sr-only">
                    {{ t('workspaces.management.configuration.search_label') }}
                </FormLabel>
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="metadata-search"
                        v-model="searchQuery"
                        class="pl-9"
                        type="search"
                        :placeholder="
                            t(
                                'workspaces.management.configuration.search_label',
                            )
                        "
                    />
                </div>
            </div>
        </div>

        <Alert v-if="!canManage">
            <ListChecks aria-hidden="true" />
            <AlertTitle>
                {{ t('workspaces.management.configuration.title') }}
            </AlertTitle>
            <AlertDescription>
                {{ t('workspaces.management.configuration.read_only') }}
            </AlertDescription>
        </Alert>

        <WorkspaceTaskDefinitionsPanel
            :workspace="workspace"
            :statuses="taskStatuses"
            :priorities="taskPriorities"
            :search="searchQuery"
            :locale="locale"
            :routes="routes"
        />

        <div class="grid items-start gap-6 xl:grid-cols-2">
            <Card class="border-sky-500/15">
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-300"
                    >
                        <Palette class="size-5" aria-hidden="true" />
                    </div>
                    <CardTitle>
                        {{
                            t(
                                'workspaces.management.configuration.labels.title',
                            )
                        }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'workspaces.management.configuration.labels.description',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <form
                        v-if="canManage"
                        class="grid gap-3 rounded-xl border bg-muted/20 p-4 sm:grid-cols-[minmax(0,1fr)_4rem_auto] sm:items-end"
                        @submit.prevent="createLabel"
                    >
                        <div class="space-y-2">
                            <FormLabel for="new-label-name">
                                {{
                                    t(
                                        'workspaces.management.configuration.labels.name',
                                    )
                                }}
                            </FormLabel>
                            <Input
                                id="new-label-name"
                                v-model="labelForm.name"
                                :placeholder="
                                    t(
                                        'workspaces.management.configuration.labels.name_placeholder',
                                    )
                                "
                                :disabled="labelForm.processing"
                                :aria-invalid="Boolean(labelForm.errors.name)"
                                @input="labelForm.clearErrors('name')"
                            />
                            <InputError :message="labelForm.errors.name" />
                        </div>
                        <div class="space-y-2">
                            <FormLabel for="new-label-color">
                                {{
                                    t(
                                        'workspaces.management.configuration.labels.color',
                                    )
                                }}
                            </FormLabel>
                            <Input
                                id="new-label-color"
                                v-model="labelForm.color"
                                type="color"
                                class="h-10 w-full cursor-pointer p-1"
                                :disabled="labelForm.processing"
                            />
                        </div>
                        <Button
                            type="submit"
                            :disabled="
                                labelForm.processing || !labelForm.name.trim()
                            "
                        >
                            <Spinner v-if="labelForm.processing" />
                            <Plus v-else aria-hidden="true" />
                            {{
                                t(
                                    'workspaces.management.configuration.labels.create',
                                )
                            }}
                        </Button>
                    </form>

                    <ul
                        v-if="filteredLabels.length"
                        class="divide-y rounded-xl border"
                    >
                        <li
                            v-for="label in filteredLabels"
                            :key="label.id"
                            class="p-4"
                        >
                            <form
                                v-if="editingLabel?.id === label.id"
                                class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_4rem_auto] sm:items-start"
                                @submit.prevent="updateLabel"
                            >
                                <div class="space-y-2">
                                    <FormLabel :for="`edit-label-${label.id}`">
                                        {{
                                            t(
                                                'workspaces.management.configuration.labels.name',
                                            )
                                        }}
                                    </FormLabel>
                                    <Input
                                        :id="`edit-label-${label.id}`"
                                        v-model="editLabelForm.name"
                                        :disabled="editLabelForm.processing"
                                        :aria-invalid="
                                            Boolean(editLabelForm.errors.name)
                                        "
                                        @input="
                                            editLabelForm.clearErrors('name')
                                        "
                                    />
                                    <InputError
                                        :message="editLabelForm.errors.name"
                                    />
                                </div>
                                <div class="space-y-2">
                                    <FormLabel
                                        :for="`edit-label-color-${label.id}`"
                                    >
                                        {{
                                            t(
                                                'workspaces.management.configuration.labels.color',
                                            )
                                        }}
                                    </FormLabel>
                                    <Input
                                        :id="`edit-label-color-${label.id}`"
                                        v-model="editLabelForm.color"
                                        type="color"
                                        class="h-10 w-full cursor-pointer p-1"
                                        :disabled="editLabelForm.processing"
                                    />
                                </div>
                                <div class="flex gap-2 sm:pt-7">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :disabled="editLabelForm.processing"
                                        @click="cancelEditingLabel"
                                    >
                                        {{ t('common.actions.cancel') }}
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="
                                            editLabelForm.processing ||
                                            !editLabelForm.name.trim()
                                        "
                                    >
                                        <Spinner
                                            v-if="editLabelForm.processing"
                                        />
                                        {{ t('common.actions.save') }}
                                    </Button>
                                </div>
                            </form>
                            <div
                                v-else
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        class="size-4 shrink-0 rounded-full border border-black/10 shadow-sm"
                                        :style="{
                                            backgroundColor: label.color,
                                        }"
                                        aria-hidden="true"
                                    />
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">
                                            {{ label.name }}
                                        </p>
                                        <Badge variant="outline" class="mt-1">
                                            {{
                                                t(
                                                    'workspaces.management.configuration.tasks_count',
                                                    {
                                                        count: formatNumber(
                                                            label.todos_count ??
                                                                0,
                                                        ),
                                                    },
                                                )
                                            }}
                                        </Badge>
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        label.permissions?.update ||
                                        label.permissions?.delete
                                    "
                                    class="flex gap-2"
                                >
                                    <Button
                                        v-if="label.permissions?.update"
                                        variant="outline"
                                        size="icon"
                                        :aria-label="
                                            t(
                                                'workspaces.management.configuration.labels.edit_action',
                                                { name: label.name },
                                            )
                                        "
                                        @click="startEditingLabel(label)"
                                    >
                                        <Pencil aria-hidden="true" />
                                    </Button>
                                    <Button
                                        v-if="label.permissions?.delete"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        :aria-label="
                                            t(
                                                'workspaces.management.configuration.labels.delete_action',
                                                { name: label.name },
                                            )
                                        "
                                        @click="
                                            deleteTarget = {
                                                kind: 'label',
                                                item: label,
                                            }
                                        "
                                    >
                                        <Trash2 aria-hidden="true" />
                                    </Button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-xl border border-dashed px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        {{
                            t(
                                'workspaces.management.configuration.labels.empty',
                            )
                        }}
                    </p>
                </CardContent>
            </Card>

            <Card class="border-violet-500/15">
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-xl bg-violet-500/10 text-violet-700 dark:text-violet-300"
                    >
                        <TagIcon class="size-5" aria-hidden="true" />
                    </div>
                    <CardTitle>
                        {{
                            t('workspaces.management.configuration.tags.title')
                        }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'workspaces.management.configuration.tags.description',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-5">
                    <form
                        v-if="canManage"
                        class="grid gap-3 rounded-xl border bg-muted/20 p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"
                        @submit.prevent="createTag"
                    >
                        <div class="space-y-2">
                            <FormLabel for="new-tag-name">
                                {{
                                    t(
                                        'workspaces.management.configuration.tags.name',
                                    )
                                }}
                            </FormLabel>
                            <Input
                                id="new-tag-name"
                                v-model="tagForm.name"
                                :placeholder="
                                    t(
                                        'workspaces.management.configuration.tags.name_placeholder',
                                    )
                                "
                                :disabled="tagForm.processing"
                                :aria-invalid="Boolean(tagForm.errors.name)"
                                @input="tagForm.clearErrors('name')"
                            />
                            <InputError :message="tagForm.errors.name" />
                        </div>
                        <Button
                            type="submit"
                            :disabled="
                                tagForm.processing || !tagForm.name.trim()
                            "
                        >
                            <Spinner v-if="tagForm.processing" />
                            <Plus v-else aria-hidden="true" />
                            {{
                                t(
                                    'workspaces.management.configuration.tags.create',
                                )
                            }}
                        </Button>
                    </form>

                    <ul
                        v-if="filteredTags.length"
                        class="divide-y rounded-xl border"
                    >
                        <li
                            v-for="tag in filteredTags"
                            :key="tag.id"
                            class="p-4"
                        >
                            <form
                                v-if="editingTag?.id === tag.id"
                                class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-start"
                                @submit.prevent="updateTag"
                            >
                                <div class="space-y-2">
                                    <FormLabel :for="`edit-tag-${tag.id}`">
                                        {{
                                            t(
                                                'workspaces.management.configuration.tags.name',
                                            )
                                        }}
                                    </FormLabel>
                                    <Input
                                        :id="`edit-tag-${tag.id}`"
                                        v-model="editTagForm.name"
                                        :disabled="editTagForm.processing"
                                        :aria-invalid="
                                            Boolean(editTagForm.errors.name)
                                        "
                                        @input="editTagForm.clearErrors('name')"
                                    />
                                    <InputError
                                        :message="editTagForm.errors.name"
                                    />
                                </div>
                                <div class="flex gap-2 sm:pt-7">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        :disabled="editTagForm.processing"
                                        @click="cancelEditingTag"
                                    >
                                        {{ t('common.actions.cancel') }}
                                    </Button>
                                    <Button
                                        type="submit"
                                        :disabled="
                                            editTagForm.processing ||
                                            !editTagForm.name.trim()
                                        "
                                    >
                                        <Spinner
                                            v-if="editTagForm.processing"
                                        />
                                        {{ t('common.actions.save') }}
                                    </Button>
                                </div>
                            </form>
                            <div
                                v-else
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="min-w-0">
                                    <p class="truncate font-medium">
                                        #{{ tag.name }}
                                    </p>
                                    <Badge variant="outline" class="mt-1">
                                        {{
                                            t(
                                                'workspaces.management.configuration.tasks_count',
                                                {
                                                    count: formatNumber(
                                                        tag.todos_count ?? 0,
                                                    ),
                                                },
                                            )
                                        }}
                                    </Badge>
                                </div>
                                <div
                                    v-if="
                                        tag.permissions?.update ||
                                        tag.permissions?.delete
                                    "
                                    class="flex gap-2"
                                >
                                    <Button
                                        v-if="tag.permissions?.update"
                                        variant="outline"
                                        size="icon"
                                        :aria-label="
                                            t(
                                                'workspaces.management.configuration.tags.edit_action',
                                                { name: tag.name },
                                            )
                                        "
                                        @click="startEditingTag(tag)"
                                    >
                                        <Pencil aria-hidden="true" />
                                    </Button>
                                    <Button
                                        v-if="tag.permissions?.delete"
                                        variant="ghost"
                                        size="icon"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        :aria-label="
                                            t(
                                                'workspaces.management.configuration.tags.delete_action',
                                                { name: tag.name },
                                            )
                                        "
                                        @click="
                                            deleteTarget = {
                                                kind: 'tag',
                                                item: tag,
                                            }
                                        "
                                    >
                                        <Trash2 aria-hidden="true" />
                                    </Button>
                                </div>
                            </div>
                        </li>
                    </ul>
                    <p
                        v-else
                        class="rounded-xl border border-dashed px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        {{
                            t('workspaces.management.configuration.tags.empty')
                        }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <WorkspaceConfirmDialog
            :open="Boolean(deleteTarget)"
            :title="deleteTitle"
            :description="deleteDescription"
            :confirm-label="t('common.actions.delete')"
            :cancel-label="t('common.actions.cancel')"
            :processing="deleteRequest.processing"
            destructive
            @update:open="setDeleteConfirmation"
            @confirm="deleteMetadata"
        />
    </section>
</template>
