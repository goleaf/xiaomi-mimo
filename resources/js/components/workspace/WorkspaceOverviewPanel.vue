<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import {
    Building2,
    CalendarDays,
    CheckCircle2,
    CheckSquare,
    Folder,
    LockKeyhole,
    Pencil,
    RefreshCw,
    UserRound,
    Users,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Dialog, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { switchMethod, update } from '@/routes/workspaces';
import type { Workspace } from '@/types/models';

interface WorkspaceResponse {
    workspace: Workspace;
}

const props = defineProps<{ workspace: Workspace }>();

const toast = useToast();
const { formatDate, formatNumber, t } = useUi();
const showEditDialog = ref(false);
const editForm = useHttp<
    { name: string; description: string },
    WorkspaceResponse
>({
    name: props.workspace.name,
    description: props.workspace.description ?? '',
});
const switchRequest = useHttp<Record<string, never>, WorkspaceResponse>({});

watch(
    () => props.workspace,
    (workspace) => {
        if (!showEditDialog.value) {
            editForm.name = workspace.name;
            editForm.description = workspace.description ?? '';
            editForm.defaults();
        }
    },
);

function setEditDialog(open: boolean): void {
    if (editForm.processing) {
        return;
    }

    showEditDialog.value = open;
    editForm.resetAndClearErrors();
    editForm.name = props.workspace.name;
    editForm.description = props.workspace.description ?? '';
}

async function editWorkspace(): Promise<void> {
    if (!editForm.name.trim()) {
        editForm.setError('name', t('workspaces.name_required'));

        return;
    }

    editForm.name = editForm.name.trim();
    editForm.description = editForm.description.trim();

    try {
        await editForm.submit(update(props.workspace));
        toast.success(t('workspaces.updated'));
        showEditDialog.value = false;
        router.reload({ only: ['workspace', 'navigation'] });
    } catch {
        if (!editForm.hasErrors) {
            toast.error(t('workspaces.update_failed'));
        }
    }
}

async function switchWorkspace(): Promise<void> {
    if (props.workspace.is_current) {
        return;
    }

    try {
        await switchRequest.submit(switchMethod(props.workspace));
        toast.success(t('workspaces.switched', { name: props.workspace.name }));
        router.reload({ only: ['workspace', 'navigation'] });
    } catch {
        toast.error(t('workspaces.switch_failed'));
    }
}
</script>

<template>
    <section class="space-y-6" aria-labelledby="workspace-overview-title">
        <div class="grid gap-4 sm:grid-cols-3">
            <Card
                class="overflow-hidden border-orange-500/15 bg-orange-500/[0.04]"
            >
                <CardHeader class="pb-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <Users class="size-5" aria-hidden="true" />
                    </div>
                    <CardDescription>
                        {{ t('workspaces.members') }}
                    </CardDescription>
                    <CardTitle class="text-3xl tabular-nums">
                        {{ formatNumber(workspace.members_count ?? 0) }}
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card class="overflow-hidden border-sky-500/15 bg-sky-500/[0.04]">
                <CardHeader class="pb-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-300"
                    >
                        <Folder class="size-5" aria-hidden="true" />
                    </div>
                    <CardDescription>
                        {{ t('workspaces.projects') }}
                    </CardDescription>
                    <CardTitle class="text-3xl tabular-nums">
                        {{ formatNumber(workspace.projects_count ?? 0) }}
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card
                class="overflow-hidden border-emerald-500/15 bg-emerald-500/[0.04]"
            >
                <CardHeader class="pb-3">
                    <div
                        class="flex size-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                    >
                        <CheckSquare class="size-5" aria-hidden="true" />
                    </div>
                    <CardDescription>
                        {{ t('workspaces.tasks') }}
                    </CardDescription>
                    <CardTitle class="text-3xl tabular-nums">
                        {{ formatNumber(workspace.todos_count ?? 0) }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.3fr)_minmax(19rem,0.7fr)]"
        >
            <Card>
                <CardHeader>
                    <div
                        class="flex items-start justify-between gap-4 border-b border-border/70 pb-5"
                    >
                        <div class="space-y-1.5">
                            <CardTitle id="workspace-overview-title">
                                {{
                                    t(
                                        'workspaces.management.overview.details_title',
                                    )
                                }}
                            </CardTitle>
                            <CardDescription>
                                {{
                                    t(
                                        'workspaces.management.overview.details_description',
                                    )
                                }}
                            </CardDescription>
                        </div>
                        <Badge
                            v-if="workspace.is_current"
                            variant="outline"
                            class="border-emerald-500/25 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"
                        >
                            <CheckCircle2 aria-hidden="true" />
                            {{ t('workspaces.current') }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-5 sm:grid-cols-2">
                        <div
                            class="rounded-xl border border-border/70 bg-muted/25 p-4"
                        >
                            <dt
                                class="flex items-center gap-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                <Building2 class="size-4" aria-hidden="true" />
                                {{ t('workspaces.name') }}
                            </dt>
                            <dd class="mt-2 font-semibold">
                                {{ workspace.name }}
                            </dd>
                        </div>
                        <div
                            class="rounded-xl border border-border/70 bg-muted/25 p-4"
                        >
                            <dt
                                class="flex items-center gap-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                <UserRound class="size-4" aria-hidden="true" />
                                {{ t('workspaces.management.overview.owner') }}
                            </dt>
                            <dd class="mt-2 min-w-0">
                                <p class="truncate font-semibold">
                                    {{ workspace.owner?.name ?? '—' }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ workspace.owner?.email ?? '—' }}
                                </p>
                            </dd>
                        </div>
                        <div
                            class="rounded-xl border border-border/70 bg-muted/25 p-4"
                        >
                            <dt
                                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                {{ t('workspaces.management.overview.slug') }}
                            </dt>
                            <dd class="mt-2 font-mono text-sm">
                                {{ workspace.slug }}
                            </dd>
                        </div>
                        <div
                            class="rounded-xl border border-border/70 bg-muted/25 p-4"
                        >
                            <dt
                                class="flex items-center gap-2 text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                            >
                                <CalendarDays
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{
                                    t('workspaces.management.overview.created')
                                }}
                            </dt>
                            <dd class="mt-2 text-sm font-medium">
                                {{
                                    formatDate(workspace.created_at, {
                                        dateStyle: 'medium',
                                    })
                                }}
                            </dd>
                        </div>
                    </dl>
                    <div
                        class="mt-5 rounded-xl border border-border/70 bg-muted/25 p-4"
                    >
                        <p
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            {{ t('workspaces.description') }}
                        </p>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            {{
                                workspace.description ??
                                t('workspaces.no_description')
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card class="xl:sticky xl:top-6">
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                    >
                        <Pencil class="size-5" aria-hidden="true" />
                    </div>
                    <CardTitle>
                        {{ t('workspaces.management.overview.actions_title') }}
                    </CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'workspaces.management.overview.actions_description',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="space-y-3">
                    <Button
                        v-if="workspace.permissions?.update"
                        size="lg"
                        class="w-full"
                        @click="setEditDialog(true)"
                    >
                        <Pencil aria-hidden="true" />
                        {{ t('workspaces.actions.edit') }}
                    </Button>
                    <div
                        v-else
                        class="flex items-start gap-3 rounded-xl border bg-muted/35 p-4 text-sm text-muted-foreground"
                    >
                        <LockKeyhole
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        <span>
                            {{ t('workspaces.management.overview.read_only') }}
                        </span>
                    </div>
                    <Button
                        variant="outline"
                        size="lg"
                        class="w-full"
                        :disabled="
                            workspace.is_current || switchRequest.processing
                        "
                        @click="switchWorkspace"
                    >
                        <Spinner v-if="switchRequest.processing" />
                        <CheckCircle2
                            v-else-if="workspace.is_current"
                            aria-hidden="true"
                        />
                        <RefreshCw v-else aria-hidden="true" />
                        {{
                            workspace.is_current
                                ? t('workspaces.current')
                                : switchRequest.processing
                                  ? t('workspaces.switching')
                                  : t('workspaces.actions.switch')
                        }}
                    </Button>
                </CardContent>
            </Card>
        </div>

        <Dialog :open="showEditDialog" @update:open="setEditDialog">
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
                        <Label for="management-workspace-name">
                            {{ t('workspaces.name') }}
                        </Label>
                        <Input
                            id="management-workspace-name"
                            v-model="editForm.name"
                            :disabled="editForm.processing"
                            :aria-invalid="Boolean(editForm.errors.name)"
                            @input="editForm.clearErrors('name')"
                        />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="management-workspace-description">
                            {{ t('workspaces.description') }}
                        </Label>
                        <Input
                            id="management-workspace-description"
                            v-model="editForm.description"
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
    </section>
</template>
