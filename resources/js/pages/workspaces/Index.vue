<script setup lang="ts">
import { Head, router, useHttp, usePage } from '@inertiajs/vue3';
import {
    Building2,
    CheckCircle2,
    CheckSquare,
    Copy,
    Folder,
    Pencil,
    Plus,
    RefreshCw,
    Search,
    Settings2,
    Trash2,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import {
    destroy,
    duplicate,
    show as showWorkspace,
    store,
    switchMethod,
    update,
} from '@/routes/workspaces';
import type { Workspace } from '@/types/models';

type WorkspaceSort = 'name_asc' | 'name_desc' | 'newest' | 'oldest';

interface WorkspaceResponse {
    workspace: Workspace;
}

const props = defineProps<{ workspaces: { data: Workspace[] } }>();

const page = usePage();
const toast = useToast();
const { formatNumber, locale, t } = useUi();
const searchQuery = ref('');
const sortOrder = ref<WorkspaceSort>('name_asc');
const showCreateDialog = ref(false);
const editingWorkspace = ref<Workspace | null>(null);
const duplicatingWorkspace = ref<Workspace | null>(null);
const deletingWorkspace = ref<Workspace | null>(null);
const switchingWorkspaceId = ref<string | null>(null);

const form = useHttp({ name: '', description: '' });
const editForm = useHttp<
    { name: string; description: string },
    WorkspaceResponse
>({
    name: '',
    description: '',
});
const duplicateForm = useHttp<{ name: string }, WorkspaceResponse>({
    name: '',
});
const deleteRequest = useHttp<Record<string, never>>({});
const switchRequest = useHttp<Record<string, never>, WorkspaceResponse>({});

const filteredWorkspaces = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase(locale.value);
    const workspaces = props.workspaces.data.filter((workspace) => {
        if (!query) {
            return true;
        }

        return [
            workspace.name,
            workspace.description ?? '',
            workspace.owner?.name ?? '',
        ]
            .join(' ')
            .toLocaleLowerCase(locale.value)
            .includes(query);
    });

    return workspaces.sort((first, second) => {
        if (sortOrder.value === 'newest') {
            return Date.parse(second.created_at) - Date.parse(first.created_at);
        }

        if (sortOrder.value === 'oldest') {
            return Date.parse(first.created_at) - Date.parse(second.created_at);
        }

        const direction = sortOrder.value === 'name_desc' ? -1 : 1;

        return (
            first.name.localeCompare(second.name, locale.value, {
                sensitivity: 'base',
            }) * direction
        );
    });
});

const memberCount = computed(() =>
    props.workspaces.data.reduce(
        (total, workspace) => total + (workspace.members_count ?? 0),
        0,
    ),
);
const projectCount = computed(() =>
    props.workspaces.data.reduce(
        (total, workspace) => total + (workspace.projects_count ?? 0),
        0,
    ),
);

function isCurrentWorkspace(workspace: Workspace): boolean {
    return (
        workspace.is_current ??
        page.props.navigation.currentWorkspace?.id === workspace.id
    );
}

function canUpdateWorkspace(workspace: Workspace): boolean {
    return workspace.permissions?.update ?? false;
}

function canDuplicateWorkspace(workspace: Workspace): boolean {
    return workspace.permissions?.duplicate ?? false;
}

function canDeleteWorkspace(workspace: Workspace): boolean {
    return workspace.permissions?.delete ?? false;
}

function reloadPortfolio(): void {
    router.reload({ only: ['workspaces', 'navigation'] });
}

function setCreateDialog(open: boolean): void {
    showCreateDialog.value = open;

    if (open) {
        form.resetAndClearErrors();
    }
}

function openEditDialog(workspace: Workspace): void {
    editingWorkspace.value = workspace;
    editForm.resetAndClearErrors();
    editForm.name = workspace.name;
    editForm.description = workspace.description ?? '';
}

function setEditDialog(open: boolean): void {
    if (!open && !editForm.processing) {
        editingWorkspace.value = null;
        editForm.resetAndClearErrors();
    }
}

function openDuplicateDialog(workspace: Workspace): void {
    duplicatingWorkspace.value = workspace;
    duplicateForm.resetAndClearErrors();
    duplicateForm.name = t('workspaces.copy_name', { name: workspace.name });
}

function setDuplicateDialog(open: boolean): void {
    if (!open && !duplicateForm.processing) {
        duplicatingWorkspace.value = null;
        duplicateForm.resetAndClearErrors();
    }
}

function openDeleteDialog(workspace: Workspace): void {
    deletingWorkspace.value = workspace;
    deleteRequest.clearErrors();
}

function setDeleteDialog(open: boolean): void {
    if (!open && !deleteRequest.processing) {
        deletingWorkspace.value = null;
        deleteRequest.clearErrors();
    }
}

async function createWorkspace(): Promise<void> {
    if (!form.name.trim()) {
        form.setError('name', t('workspaces.name_required'));

        return;
    }

    try {
        await form.submit(store(), {
            onSuccess: () => {
                toast.success(t('workspaces.created'));
                showCreateDialog.value = false;
                form.resetAndClearErrors();
                reloadPortfolio();
            },
            onHttpException: () => {
                toast.error(t('workspaces.create_failed'));
            },
            onNetworkError: () => {
                toast.error(t('workspaces.create_failed'));
            },
        });
    } catch {
        if (!form.hasErrors) {
            toast.error(t('workspaces.create_failed'));
        }
    }
}

async function editWorkspace(): Promise<void> {
    const workspace = editingWorkspace.value;

    if (!workspace || !editForm.name.trim()) {
        editForm.setError('name', t('workspaces.name_required'));

        return;
    }

    editForm.name = editForm.name.trim();
    editForm.description = editForm.description.trim();

    try {
        await editForm.submit(update(workspace));
        toast.success(t('workspaces.updated'));
        editingWorkspace.value = null;
        editForm.resetAndClearErrors();
        reloadPortfolio();
    } catch {
        if (!editForm.hasErrors) {
            toast.error(t('workspaces.update_failed'));
        }
    }
}

async function duplicateWorkspace(): Promise<void> {
    const workspace = duplicatingWorkspace.value;

    if (!workspace || !duplicateForm.name.trim()) {
        duplicateForm.setError('name', t('workspaces.name_required'));

        return;
    }

    duplicateForm.name = duplicateForm.name.trim();

    try {
        await duplicateForm.submit(duplicate(workspace));
        toast.success(t('workspaces.duplicated'));
        duplicatingWorkspace.value = null;
        duplicateForm.resetAndClearErrors();
        reloadPortfolio();
    } catch {
        if (!duplicateForm.hasErrors) {
            toast.error(t('workspaces.duplicate_failed'));
        }
    }
}

async function deleteWorkspace(): Promise<void> {
    const workspace = deletingWorkspace.value;

    if (!workspace) {
        return;
    }

    try {
        await deleteRequest.submit(destroy(workspace));
        toast.success(t('workspaces.deleted'));
        deletingWorkspace.value = null;
        reloadPortfolio();
    } catch {
        toast.error(t('workspaces.delete_failed'));
    }
}

async function switchWorkspace(
    workspace: Workspace,
    reload = true,
): Promise<boolean> {
    if (isCurrentWorkspace(workspace)) {
        return true;
    }

    switchingWorkspaceId.value = workspace.id;

    try {
        await switchRequest.submit(switchMethod(workspace));
        toast.success(t('workspaces.switched', { name: workspace.name }));

        if (reload) {
            reloadPortfolio();
        }

        return true;
    } catch {
        toast.error(t('workspaces.switch_failed'));

        return false;
    } finally {
        switchingWorkspaceId.value = null;
    }
}

function manageWorkspace(workspace: Workspace): void {
    router.visit(showWorkspace(workspace));
}
</script>

<template>
    <div>
        <Head :title="t('workspaces.title')" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="t('workspaces.eyebrow')"
                    :title="t('workspaces.title')"
                    :description="t('workspaces.page_description')"
                >
                    <template #actions>
                        <Button size="lg" @click="setCreateDialog(true)">
                            <Plus class="size-4" aria-hidden="true" />
                            {{ t('workspaces.new') }}
                        </Button>
                    </template>

                    <template #metrics>
                        <WorkspaceMetric
                            :label="t('workspaces.title')"
                            :value="formatNumber(workspaces.data.length)"
                            :icon="Building2"
                            tone="orange"
                        />
                        <WorkspaceMetric
                            :label="t('workspaces.members')"
                            :value="formatNumber(memberCount)"
                            :icon="Users"
                            tone="emerald"
                        />
                        <WorkspaceMetric
                            :label="t('workspaces.projects')"
                            :value="formatNumber(projectCount)"
                            :icon="Folder"
                            tone="blue"
                        />
                    </template>
                </WorkspacePageHeader>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-4 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-6"
                >
                    <div
                        v-if="workspaces.data.length"
                        class="grid gap-3 border-b border-border/70 pb-5 sm:grid-cols-[minmax(0,1fr)_13rem]"
                    >
                        <div class="relative">
                            <Label for="workspace-search" class="sr-only">
                                {{ t('workspaces.search_label') }}
                            </Label>
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                                aria-hidden="true"
                            />
                            <Input
                                id="workspace-search"
                                v-model="searchQuery"
                                type="search"
                                :placeholder="
                                    t('workspaces.search_placeholder')
                                "
                                class="pl-10"
                            />
                        </div>
                        <div>
                            <Label for="workspace-sort" class="sr-only">
                                {{ t('workspaces.sort_label') }}
                            </Label>
                            <Select v-model="sortOrder">
                                <SelectTrigger
                                    id="workspace-sort"
                                    class="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="name_asc">
                                        {{ t('workspaces.sort.name_asc') }}
                                    </SelectItem>
                                    <SelectItem value="name_desc">
                                        {{ t('workspaces.sort.name_desc') }}
                                    </SelectItem>
                                    <SelectItem value="newest">
                                        {{ t('workspaces.sort.newest') }}
                                    </SelectItem>
                                    <SelectItem value="oldest">
                                        {{ t('workspaces.sort.oldest') }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <div
                        v-if="filteredWorkspaces.length"
                        class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <Card
                            v-for="(workspace, index) in filteredWorkspaces"
                            :key="workspace.id"
                            class="group relative flex min-h-[25rem] flex-col overflow-hidden bg-background transition-[border-color,box-shadow,transform] hover:-translate-y-0.5 hover:border-orange-500/25 hover:shadow-[0_24px_50px_-38px_rgba(234,88,12,0.5)] motion-reduce:transform-none"
                            :class="
                                isCurrentWorkspace(workspace)
                                    ? 'border-emerald-500/30 ring-1 ring-emerald-500/10'
                                    : ''
                            "
                        >
                            <span
                                class="absolute inset-y-0 left-0 w-1.5"
                                :class="
                                    isCurrentWorkspace(workspace)
                                        ? 'bg-emerald-500'
                                        : 'bg-orange-500'
                                "
                                aria-hidden="true"
                            />
                            <span
                                class="absolute -right-4 -bottom-9 text-8xl leading-none font-semibold tracking-[-0.1em] text-foreground/[0.025] select-none dark:text-white/[0.035]"
                                aria-hidden="true"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                            <CardHeader class="relative gap-4">
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div
                                        class="flex size-11 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                                    >
                                        <Building2
                                            class="size-5"
                                            aria-hidden="true"
                                        />
                                    </div>
                                    <Badge
                                        v-if="isCurrentWorkspace(workspace)"
                                        variant="outline"
                                        class="border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                                    >
                                        <CheckCircle2 aria-hidden="true" />
                                        {{ t('workspaces.current') }}
                                    </Badge>
                                </div>
                                <div class="space-y-2">
                                    <CardTitle class="tracking-[-0.02em]">
                                        {{ workspace.name }}
                                    </CardTitle>
                                    <p
                                        class="line-clamp-2 text-sm leading-6 text-muted-foreground"
                                    >
                                        {{
                                            workspace.description ??
                                            t('workspaces.no_description')
                                        }}
                                    </p>
                                </div>
                            </CardHeader>
                            <CardContent class="relative mt-auto space-y-4">
                                <div
                                    class="grid grid-cols-3 divide-x divide-border/70 rounded-xl border border-border/70 bg-muted/25"
                                >
                                    <div
                                        class="flex items-center justify-center gap-1.5 px-2 py-3 text-sm"
                                        :title="t('workspaces.members')"
                                    >
                                        <Users
                                            class="size-4 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <span class="font-medium tabular-nums">
                                            {{
                                                formatNumber(
                                                    workspace.members_count ??
                                                        0,
                                                )
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-center gap-1.5 px-2 py-3 text-sm"
                                        :title="t('workspaces.projects')"
                                    >
                                        <Folder
                                            class="size-4 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <span class="font-medium tabular-nums">
                                            {{
                                                formatNumber(
                                                    workspace.projects_count ??
                                                        0,
                                                )
                                            }}
                                        </span>
                                    </div>
                                    <div
                                        class="flex items-center justify-center gap-1.5 px-2 py-3 text-sm"
                                        :title="t('workspaces.tasks')"
                                    >
                                        <CheckSquare
                                            class="size-4 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                        <span class="font-medium tabular-nums">
                                            {{
                                                formatNumber(
                                                    workspace.todos_count ?? 0,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    class="grid grid-cols-2 gap-2"
                                    :aria-label="
                                        t('workspaces.actions_label', {
                                            name: workspace.name,
                                        })
                                    "
                                >
                                    <Button
                                        size="sm"
                                        class="w-full"
                                        :disabled="switchRequest.processing"
                                        @click="manageWorkspace(workspace)"
                                    >
                                        <Settings2 aria-hidden="true" />
                                        {{ t('workspaces.actions.manage') }}
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        class="w-full"
                                        :disabled="
                                            switchRequest.processing ||
                                            isCurrentWorkspace(workspace)
                                        "
                                        @click="switchWorkspace(workspace)"
                                    >
                                        <Spinner
                                            v-if="
                                                switchingWorkspaceId ===
                                                workspace.id
                                            "
                                        />
                                        <CheckCircle2
                                            v-else-if="
                                                isCurrentWorkspace(workspace)
                                            "
                                            aria-hidden="true"
                                        />
                                        <RefreshCw v-else aria-hidden="true" />
                                        {{
                                            isCurrentWorkspace(workspace)
                                                ? t('workspaces.current')
                                                : switchingWorkspaceId ===
                                                    workspace.id
                                                  ? t('workspaces.switching')
                                                  : t(
                                                        'workspaces.actions.switch',
                                                    )
                                        }}
                                    </Button>
                                    <Button
                                        v-if="canUpdateWorkspace(workspace)"
                                        variant="ghost"
                                        size="sm"
                                        @click="openEditDialog(workspace)"
                                    >
                                        <Pencil aria-hidden="true" />
                                        {{ t('workspaces.actions.edit') }}
                                    </Button>
                                    <Button
                                        v-if="canDuplicateWorkspace(workspace)"
                                        variant="ghost"
                                        size="sm"
                                        @click="openDuplicateDialog(workspace)"
                                    >
                                        <Copy aria-hidden="true" />
                                        {{ t('workspaces.actions.duplicate') }}
                                    </Button>
                                    <Button
                                        v-if="canDeleteWorkspace(workspace)"
                                        variant="ghost"
                                        size="sm"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive focus-visible:ring-destructive/20"
                                        @click="openDeleteDialog(workspace)"
                                    >
                                        <Trash2 aria-hidden="true" />
                                        {{ t('workspaces.actions.delete') }}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <EmptyState
                        v-else-if="workspaces.data.length"
                        compact
                        :title="t('workspaces.no_results')"
                        :description="t('workspaces.no_results_description')"
                    >
                        <template #icon>
                            <Search class="size-7" aria-hidden="true" />
                        </template>
                    </EmptyState>

                    <EmptyState
                        v-else
                        :title="t('workspaces.empty')"
                        :description="t('workspaces.empty_description')"
                        :action-label="t('workspaces.create')"
                        @action="setCreateDialog(true)"
                    >
                        <template #icon>
                            <Building2 class="size-7" aria-hidden="true" />
                        </template>
                    </EmptyState>
                </section>
            </div>
        </main>

        <Dialog :open="showCreateDialog" @update:open="setCreateDialog">
            <WorkspaceDialogContent
                :title="t('workspaces.new')"
                :description="t('workspaces.create_description')"
                :close-label="t('common.actions.cancel')"
            >
                <form
                    class="space-y-6 px-6 py-6 sm:px-8"
                    @submit.prevent="createWorkspace"
                >
                    <div class="space-y-2">
                        <Label for="ws-name">{{ t('workspaces.name') }}</Label>
                        <Input
                            id="ws-name"
                            v-model="form.name"
                            :placeholder="t('workspaces.name_placeholder')"
                            autofocus
                            :disabled="form.processing"
                            :aria-invalid="Boolean(form.errors.name)"
                            @input="form.clearErrors('name')"
                        />
                        <InputError :message="form.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="ws-desc">
                            {{ t('workspaces.description') }}
                        </Label>
                        <Input
                            id="ws-desc"
                            v-model="form.description"
                            :placeholder="
                                t('workspaces.description_placeholder')
                            "
                            :disabled="form.processing"
                            :aria-invalid="Boolean(form.errors.description)"
                            @input="form.clearErrors('description')"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <DialogFooter
                        class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            :disabled="form.processing"
                            @click="setCreateDialog(false)"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            size="lg"
                            :disabled="form.processing"
                        >
                            <Spinner v-if="form.processing" />
                            {{
                                form.processing
                                    ? t('workspaces.creating')
                                    : t('common.actions.create')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </WorkspaceDialogContent>
        </Dialog>

        <Dialog :open="Boolean(editingWorkspace)" @update:open="setEditDialog">
            <WorkspaceDialogContent
                :title="t('workspaces.actions.edit')"
                :description="t('workspaces.edit_description')"
                :close-label="t('common.actions.cancel')"
            >
                <form
                    class="space-y-6 px-6 py-6 sm:px-8"
                    @submit.prevent="editWorkspace"
                >
                    <div class="space-y-2">
                        <Label for="workspace-edit-name">
                            {{ t('workspaces.name') }}
                        </Label>
                        <Input
                            id="workspace-edit-name"
                            v-model="editForm.name"
                            :placeholder="t('workspaces.name_placeholder')"
                            :disabled="editForm.processing"
                            :aria-invalid="Boolean(editForm.errors.name)"
                            @input="editForm.clearErrors('name')"
                        />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="workspace-edit-description">
                            {{ t('workspaces.description') }}
                        </Label>
                        <Input
                            id="workspace-edit-description"
                            v-model="editForm.description"
                            :placeholder="
                                t('workspaces.description_placeholder')
                            "
                            :disabled="editForm.processing"
                            :aria-invalid="Boolean(editForm.errors.description)"
                            @input="editForm.clearErrors('description')"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>
                    <DialogFooter
                        class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            :disabled="editForm.processing"
                            @click="setEditDialog(false)"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            size="lg"
                            :disabled="editForm.processing"
                        >
                            <Spinner v-if="editForm.processing" />
                            {{
                                editForm.processing
                                    ? t('workspaces.updating')
                                    : t('common.actions.save')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </WorkspaceDialogContent>
        </Dialog>

        <Dialog
            :open="Boolean(duplicatingWorkspace)"
            @update:open="setDuplicateDialog"
        >
            <WorkspaceDialogContent
                :title="t('workspaces.actions.duplicate')"
                :description="t('workspaces.duplicate_description')"
                :close-label="t('common.actions.cancel')"
            >
                <form
                    class="space-y-6 px-6 py-6 sm:px-8"
                    @submit.prevent="duplicateWorkspace"
                >
                    <div class="space-y-2">
                        <Label for="workspace-duplicate-name">
                            {{ t('workspaces.name') }}
                        </Label>
                        <Input
                            id="workspace-duplicate-name"
                            v-model="duplicateForm.name"
                            :placeholder="
                                t('workspaces.duplicate_name_placeholder')
                            "
                            :disabled="duplicateForm.processing"
                            :aria-invalid="Boolean(duplicateForm.errors.name)"
                            @input="duplicateForm.clearErrors('name')"
                        />
                        <InputError :message="duplicateForm.errors.name" />
                    </div>
                    <DialogFooter
                        class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="lg"
                            :disabled="duplicateForm.processing"
                            @click="setDuplicateDialog(false)"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            size="lg"
                            :disabled="duplicateForm.processing"
                        >
                            <Spinner v-if="duplicateForm.processing" />
                            {{
                                duplicateForm.processing
                                    ? t('workspaces.duplicating')
                                    : t('workspaces.actions.duplicate')
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </WorkspaceDialogContent>
        </Dialog>

        <WorkspaceConfirmDialog
            :open="Boolean(deletingWorkspace)"
            :title="t('workspaces.actions.delete')"
            :description="
                t('workspaces.delete_description', {
                    name: deletingWorkspace?.name ?? '',
                    members: formatNumber(
                        deletingWorkspace?.members_count ?? 0,
                    ),
                    projects: formatNumber(
                        deletingWorkspace?.projects_count ?? 0,
                    ),
                    tasks: formatNumber(deletingWorkspace?.todos_count ?? 0),
                })
            "
            :confirm-label="t('workspaces.actions.delete')"
            :cancel-label="t('common.actions.cancel')"
            :processing="deleteRequest.processing"
            :confirmation-text="deletingWorkspace?.name"
            :confirmation-label="
                t('workspaces.delete_confirmation', {
                    name: deletingWorkspace?.name ?? '',
                })
            "
            destructive
            @update:open="setDeleteDialog"
            @confirm="deleteWorkspace"
        />
    </div>
</template>
