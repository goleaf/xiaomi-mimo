<script setup lang="ts">
import { Head, router, useHttp } from '@inertiajs/vue3';
import { Building2, CheckSquare, Folder, Plus, Users } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { store } from '@/routes/workspaces';
import type { Workspace } from '@/types/models';

const props = defineProps<{ workspaces: { data: Workspace[] } }>();

const toast = useToast();
const { formatNumber, t } = useUi();
const showCreateDialog = ref(false);
const form = useHttp({ name: '', description: '' });

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

function setCreateDialog(open: boolean): void {
    showCreateDialog.value = open;

    if (open) {
        form.resetAndClearErrors();
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
                router.reload({ only: ['workspaces'] });
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
                        <Button @click="setCreateDialog(true)">
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
                        class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                    >
                        <Card
                            v-for="(workspace, index) in workspaces.data"
                            :key="workspace.id"
                            class="group relative min-h-56 overflow-hidden bg-background transition-[border-color,box-shadow,transform] hover:-translate-y-0.5 hover:border-orange-500/25 hover:shadow-[0_24px_50px_-38px_rgba(234,88,12,0.5)] motion-reduce:transform-none"
                        >
                            <span
                                class="absolute inset-y-0 left-0 w-1.5 bg-orange-500"
                                aria-hidden="true"
                            />
                            <span
                                class="absolute -right-4 -bottom-9 text-8xl leading-none font-semibold tracking-[-0.1em] text-foreground/[0.025] select-none dark:text-white/[0.035]"
                                aria-hidden="true"
                            >
                                {{ String(index + 1).padStart(2, '0') }}
                            </span>
                            <CardHeader class="relative">
                                <div
                                    class="mb-2 flex size-11 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                                >
                                    <Building2
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
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
                            </CardHeader>
                            <CardContent class="relative mt-auto">
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
                            </CardContent>
                        </Card>
                    </div>

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
                            class="h-11 rounded-xl"
                            autofocus
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
                            class="h-11 rounded-xl"
                        />
                        <InputError :message="form.errors.description" />
                    </div>
                    <DialogFooter
                        class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11 cursor-pointer rounded-xl"
                            :disabled="form.processing"
                            @click="setCreateDialog(false)"
                        >
                            {{ t('common.actions.cancel') }}
                        </Button>
                        <Button
                            type="submit"
                            class="min-h-11 cursor-pointer rounded-xl bg-orange-600 text-white hover:bg-orange-700 focus-visible:ring-orange-500"
                            :disabled="form.processing"
                        >
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
    </div>
</template>
