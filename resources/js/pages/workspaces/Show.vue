<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    CheckSquare,
    Folder,
    LayoutDashboard,
    ListChecks,
    ShieldAlert,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import WorkspaceSegmentedControl from '@/components/shared/WorkspaceSegmentedControl.vue';
import { Badge } from '@/components/ui/badge';
import { buttonVariants } from '@/components/ui/button';
import WorkspaceConfigurationPanel from '@/components/workspace/WorkspaceConfigurationPanel.vue';
import WorkspaceDangerPanel from '@/components/workspace/WorkspaceDangerPanel.vue';
import WorkspaceMembersPanel from '@/components/workspace/WorkspaceMembersPanel.vue';
import WorkspaceOverviewPanel from '@/components/workspace/WorkspaceOverviewPanel.vue';
import { useUi } from '@/composables/useUi';
import { cn } from '@/lib/utils';
import {
    configuration as workspaceConfiguration,
    danger as workspaceDanger,
    index as workspaceIndex,
    invite as inviteWorkspaceMember,
    members as workspaceMembers,
    removeMember as removeWorkspaceMember,
    show as showWorkspace,
} from '@/routes/workspaces';
import {
    cancel as cancelWorkspaceInvitation,
    resend as resendWorkspaceInvitation,
} from '@/routes/workspaces/invitations';
import { update as updateWorkspaceMember } from '@/routes/workspaces/members';
import type {
    Workspace,
    WorkspaceInvitation,
    WorkspaceManagementMember,
    WorkspaceManagementSection,
    WorkspaceMemberRouteUrls,
} from '@/types/models';

const props = defineProps<{
    section: WorkspaceManagementSection;
    workspace: Workspace;
    members: WorkspaceManagementMember[];
    invitations: WorkspaceInvitation[];
    locale: string;
}>();

const { formatNumber, t } = useUi();

const navigationItems = computed(() => [
    {
        section: 'overview' as const,
        label: t('workspaces.management.navigation.overview'),
        icon: LayoutDashboard,
        href: showWorkspace(props.workspace),
    },
    {
        section: 'members' as const,
        label: t('workspaces.management.navigation.members'),
        icon: Users,
        href: workspaceMembers(props.workspace),
    },
    {
        section: 'configuration' as const,
        label: t('workspaces.management.navigation.configuration'),
        icon: ListChecks,
        href: workspaceConfiguration(props.workspace),
    },
    {
        section: 'danger' as const,
        label: t('workspaces.management.navigation.danger'),
        icon: ShieldAlert,
        href: workspaceDanger(props.workspace),
    },
]);

const memberRoutes = computed<WorkspaceMemberRouteUrls>(() => ({
    invite: inviteWorkspaceMember.url(props.workspace),
    resendInvitation: (invitationId: string) =>
        resendWorkspaceInvitation.url({
            workspace: props.workspace,
            invitation: invitationId,
        }),
    cancelInvitation: (invitationId: string) =>
        cancelWorkspaceInvitation.url({
            workspace: props.workspace,
            invitation: invitationId,
        }),
    updateMember: (userId: string) =>
        updateWorkspaceMember.url({ workspace: props.workspace, userId }),
    removeMember: (userId: string) =>
        removeWorkspaceMember.url({ workspace: props.workspace, userId }),
}));

function showOverview(): void {
    router.visit(showWorkspace(props.workspace));
}
</script>

<template>
    <div>
        <Head
            :title="
                t('workspaces.management.title', {
                    workspace: workspace.name,
                })
            "
        />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="t('workspaces.management.eyebrow')"
                    :title="
                        t('workspaces.management.title', {
                            workspace: workspace.name,
                        })
                    "
                    :description="t('workspaces.management.description')"
                >
                    <template #actions>
                        <Badge
                            v-if="workspace.is_current"
                            variant="outline"
                            class="h-11 border-emerald-500/25 bg-emerald-500/10 px-4 text-emerald-700 dark:text-emerald-300"
                        >
                            <CheckCircle2 aria-hidden="true" />
                            {{ t('workspaces.current') }}
                        </Badge>
                        <Link
                            :href="workspaceIndex()"
                            :class="
                                buttonVariants({
                                    variant: 'outline',
                                    size: 'lg',
                                })
                            "
                        >
                            <ArrowLeft aria-hidden="true" />
                            {{ t('workspaces.management.back') }}
                        </Link>
                    </template>

                    <template #metrics>
                        <WorkspaceMetric
                            :label="t('workspaces.members')"
                            :value="formatNumber(workspace.members_count ?? 0)"
                            :icon="Users"
                            tone="orange"
                        />
                        <WorkspaceMetric
                            :label="t('workspaces.projects')"
                            :value="formatNumber(workspace.projects_count ?? 0)"
                            :icon="Folder"
                            tone="blue"
                        />
                        <WorkspaceMetric
                            :label="t('workspaces.tasks')"
                            :value="formatNumber(workspace.todos_count ?? 0)"
                            :icon="CheckSquare"
                            tone="emerald"
                        />
                    </template>
                </WorkspacePageHeader>

                <section
                    class="rounded-[1.5rem] border border-border/80 bg-card p-4 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-6"
                >
                    <div
                        class="mb-6 flex flex-col gap-4 border-b border-border/70 pb-5 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                            >
                                <Building2 class="size-5" aria-hidden="true" />
                            </div>
                            <div class="min-w-0">
                                <p class="truncate font-semibold">
                                    {{ workspace.name }}
                                </p>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ workspace.owner?.name ?? '—' }}
                                </p>
                            </div>
                        </div>

                        <WorkspaceSegmentedControl
                            :label="t('workspaces.management.navigation.label')"
                            role="group"
                            class="w-full lg:w-auto"
                        >
                            <Link
                                v-for="item in navigationItems"
                                :key="item.section"
                                :href="item.href"
                                :aria-current="
                                    section === item.section
                                        ? 'page'
                                        : undefined
                                "
                                :class="
                                    cn(
                                        'flex min-h-10 shrink-0 items-center gap-2 rounded-lg px-3 py-2 text-sm whitespace-nowrap transition-all focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none motion-reduce:transition-none',
                                        section === item.section
                                            ? item.section === 'danger'
                                                ? 'bg-card font-medium text-destructive shadow-sm'
                                                : 'bg-card font-medium text-orange-800 shadow-sm dark:text-orange-200'
                                            : 'text-muted-foreground hover:bg-card/70 hover:text-foreground',
                                    )
                                "
                            >
                                <component
                                    :is="item.icon"
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{ item.label }}
                            </Link>
                        </WorkspaceSegmentedControl>
                    </div>

                    <WorkspaceOverviewPanel
                        v-if="section === 'overview'"
                        :workspace="workspace"
                    />
                    <WorkspaceMembersPanel
                        v-else-if="section === 'members'"
                        :workspace="workspace"
                        :members="members"
                        :invitations="invitations"
                        :locale="locale"
                        :routes="memberRoutes"
                    />
                    <WorkspaceConfigurationPanel
                        v-else-if="section === 'configuration'"
                        @overview="showOverview"
                    />
                    <WorkspaceDangerPanel
                        v-else
                        :workspace="workspace"
                        :members="members"
                    />
                </section>
            </div>
        </main>
    </div>
</template>
