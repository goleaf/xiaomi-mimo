<script setup lang="ts">
import { router, useHttp } from '@inertiajs/vue3';
import {
    Clock3,
    LockKeyhole,
    Mail,
    MailPlus,
    RefreshCw,
    Search,
    ShieldCheck,
    Trash2,
    UserPlus,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import WorkspaceConfirmDialog from '@/components/shared/WorkspaceConfirmDialog.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import type {
    Workspace,
    WorkspaceInvitation,
    WorkspaceManagementMember,
    WorkspaceMemberRouteUrls,
    WorkspaceRole,
} from '@/types/models';

type AssignableWorkspaceRole = Exclude<WorkspaceRole, 'owner'>;

const props = defineProps<{
    workspace: Workspace;
    members: WorkspaceManagementMember[];
    invitations: WorkspaceInvitation[];
    locale: string;
    routes: WorkspaceMemberRouteUrls;
}>();

const toast = useToast();
const { formatDate, formatNumber, t } = useUi();
const searchQuery = ref('');
const updatingMemberId = ref<string | null>(null);
const resendingInvitationId = ref<string | null>(null);
const memberToRemove = ref<WorkspaceManagementMember | null>(null);
const invitationToCancel = ref<WorkspaceInvitation | null>(null);
const inviteForm = useHttp<{
    email: string;
    role: AssignableWorkspaceRole;
}>({
    email: '',
    role: 'member',
});
const roleRequest = useHttp<{ role: AssignableWorkspaceRole }>({
    role: 'member',
});
const removeRequest = useHttp<Record<string, never>>({});
const resendRequest = useHttp<Record<string, never>>({});
const cancelRequest = useHttp<Record<string, never>>({});

const filteredMembers = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase(props.locale);

    if (!query) {
        return props.members;
    }

    return props.members.filter((member) =>
        `${member.name} ${member.email} ${t(
            `workspaces.management.members.roles.${member.role}`,
        )}`
            .toLocaleLowerCase(props.locale)
            .includes(query),
    );
});

const managerCount = computed(
    () => props.members.filter((member) => member.role !== 'member').length,
);

const avatarTones = [
    'bg-amber-100 text-amber-950 dark:bg-amber-950/70 dark:text-amber-200',
    'bg-sky-100 text-sky-950 dark:bg-sky-950/70 dark:text-sky-200',
    'bg-emerald-100 text-emerald-950 dark:bg-emerald-950/70 dark:text-emerald-200',
];

const roleClasses: Record<WorkspaceRole, string> = {
    owner: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950/50 dark:text-amber-200',
    admin: 'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950/50 dark:text-sky-200',
    member: 'border-border bg-muted/50 text-muted-foreground',
};

function initials(name: string): string {
    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toLocaleUpperCase(props.locale);
}

function avatarTone(memberId: string): string {
    const characterCode = memberId.charCodeAt(memberId.length - 1) || 0;

    return avatarTones[characterCode % avatarTones.length];
}

function isAssignableRole(value: unknown): value is AssignableWorkspaceRole {
    return value === 'admin' || value === 'member';
}

function reloadMembership(): void {
    router.reload({
        only: ['workspace', 'members', 'invitations', 'navigation'],
    });
}

async function inviteMember(): Promise<void> {
    if (!inviteForm.email.trim()) {
        return;
    }

    inviteForm.email = inviteForm.email.trim();

    try {
        await inviteForm.post(props.routes.invite);

        if (!inviteForm.wasSuccessful) {
            return;
        }

        toast.success(t('workspaces.management.invite.success'));
        inviteForm.resetAndClearErrors();
        inviteForm.email = '';
        inviteForm.role = 'member';
        reloadMembership();
    } catch {
        if (!inviteForm.hasErrors) {
            toast.error(t('workspaces.management.invite.failed'));
        }
    }
}

async function updateMemberRole(
    member: WorkspaceManagementMember,
    value: unknown,
): Promise<void> {
    if (!isAssignableRole(value) || value === member.role) {
        return;
    }

    updatingMemberId.value = member.id;
    roleRequest.role = value;

    try {
        await roleRequest.patch(props.routes.updateMember(member.id));

        if (!roleRequest.wasSuccessful) {
            toast.error(t('workspaces.management.members.role_failed'));

            return;
        }

        toast.success(t('workspaces.management.members.role_updated'));
        roleRequest.resetAndClearErrors();
        reloadMembership();
    } catch {
        toast.error(t('workspaces.management.members.role_failed'));
    } finally {
        updatingMemberId.value = null;
    }
}

function setRemoveConfirmation(open: boolean): void {
    if (!open && !removeRequest.processing) {
        memberToRemove.value = null;
    }
}

async function removeMember(): Promise<void> {
    const member = memberToRemove.value;

    if (!member) {
        return;
    }

    try {
        await removeRequest.delete(props.routes.removeMember(member.id));

        if (!removeRequest.wasSuccessful) {
            toast.error(t('workspaces.management.members.remove_failed'));

            return;
        }

        toast.success(t('workspaces.management.members.removed'));
        memberToRemove.value = null;
        reloadMembership();
    } catch {
        toast.error(t('workspaces.management.members.remove_failed'));
    }
}

async function resendInvitation(
    invitation: WorkspaceInvitation,
): Promise<void> {
    resendingInvitationId.value = invitation.id;

    try {
        await resendRequest.post(props.routes.resendInvitation(invitation.id));

        if (!resendRequest.wasSuccessful) {
            toast.error(t('workspaces.management.invitations.resend_failed'));

            return;
        }

        toast.success(t('workspaces.management.invitations.resent'));
        reloadMembership();
    } catch {
        toast.error(t('workspaces.management.invitations.resend_failed'));
    } finally {
        resendingInvitationId.value = null;
    }
}

function setCancelConfirmation(open: boolean): void {
    if (!open && !cancelRequest.processing) {
        invitationToCancel.value = null;
    }
}

async function cancelInvitation(): Promise<void> {
    const invitation = invitationToCancel.value;

    if (!invitation) {
        return;
    }

    try {
        await cancelRequest.delete(
            props.routes.cancelInvitation(invitation.id),
        );

        if (!cancelRequest.wasSuccessful) {
            toast.error(t('workspaces.management.invitations.cancel_failed'));

            return;
        }

        toast.success(t('workspaces.management.invitations.cancelled'));
        invitationToCancel.value = null;
        reloadMembership();
    } catch {
        toast.error(t('workspaces.management.invitations.cancel_failed'));
    }
}
</script>

<template>
    <section class="space-y-6" aria-labelledby="workspace-members-title">
        <div class="grid gap-4 sm:grid-cols-2">
            <Card class="border-orange-500/15 bg-orange-500/[0.04]">
                <CardHeader class="pb-3">
                    <CardDescription>
                        {{ t('workspaces.management.members.total') }}
                    </CardDescription>
                    <CardTitle
                        class="flex items-center gap-3 text-3xl tabular-nums"
                    >
                        <Users
                            class="size-6 text-orange-600"
                            aria-hidden="true"
                        />
                        {{ formatNumber(members.length) }}
                    </CardTitle>
                </CardHeader>
            </Card>
            <Card class="border-emerald-500/15 bg-emerald-500/[0.04]">
                <CardHeader class="pb-3">
                    <CardDescription>
                        {{ t('workspaces.management.members.managers') }}
                    </CardDescription>
                    <CardTitle
                        class="flex items-center gap-3 text-3xl tabular-nums"
                    >
                        <ShieldCheck
                            class="size-6 text-emerald-600"
                            aria-hidden="true"
                        />
                        {{ formatNumber(managerCount) }}
                    </CardTitle>
                </CardHeader>
            </Card>
        </div>

        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.4fr)_minmax(19rem,0.6fr)]"
        >
            <Card class="gap-0 overflow-hidden py-0">
                <CardHeader class="gap-5 border-b py-5 sm:py-6">
                    <div class="space-y-1.5">
                        <CardTitle id="workspace-members-title">
                            {{
                                t('workspaces.management.members.roster_title')
                            }}
                        </CardTitle>
                        <CardDescription>
                            {{
                                t(
                                    'workspaces.management.members.roster_description',
                                )
                            }}
                        </CardDescription>
                    </div>
                    <div class="relative sm:max-w-sm">
                        <Label for="management-member-search" class="sr-only">
                            {{
                                t('workspaces.management.members.search_label')
                            }}
                        </Label>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            id="management-member-search"
                            v-model="searchQuery"
                            type="search"
                            :placeholder="
                                t(
                                    'workspaces.management.members.search_placeholder',
                                )
                            "
                            class="pl-9"
                        />
                    </div>
                </CardHeader>
                <CardContent class="p-0">
                    <ul
                        v-if="filteredMembers.length"
                        class="divide-y"
                        role="list"
                        :aria-busy="
                            roleRequest.processing || removeRequest.processing
                        "
                    >
                        <li
                            v-for="member in filteredMembers"
                            :key="member.membership_id"
                            class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-3 px-5 py-4 transition-colors hover:bg-muted/35 motion-reduce:transition-none sm:grid-cols-[auto_minmax(0,1fr)_minmax(10rem,auto)_auto] sm:items-center sm:px-6"
                        >
                            <Avatar class="size-11 border shadow-xs">
                                <AvatarImage
                                    v-if="member.avatar"
                                    :src="member.avatar"
                                    :alt="member.name"
                                />
                                <AvatarFallback
                                    :class="[
                                        'text-sm font-semibold',
                                        avatarTone(member.id),
                                    ]"
                                >
                                    {{ initials(member.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold">
                                        {{ member.name }}
                                    </p>
                                    <Badge
                                        v-if="member.is_current_user"
                                        variant="secondary"
                                        class="px-1.5 py-0 text-[10px]"
                                    >
                                        {{
                                            t(
                                                'workspaces.management.members.current_user',
                                            )
                                        }}
                                    </Badge>
                                </div>
                                <a
                                    :href="`mailto:${member.email}`"
                                    class="mt-0.5 block truncate text-sm text-muted-foreground underline-offset-4 transition-colors hover:text-orange-800 hover:underline focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none motion-reduce:transition-none dark:hover:text-orange-200"
                                >
                                    {{ member.email }}
                                </a>
                            </div>

                            <div class="col-span-2 pl-14 sm:col-span-1 sm:pl-0">
                                <Select
                                    v-if="
                                        member.permissions.update &&
                                        member.role !== 'owner'
                                    "
                                    :model-value="member.role"
                                    :disabled="
                                        roleRequest.processing ||
                                        removeRequest.processing
                                    "
                                    @update:model-value="
                                        updateMemberRole(member, $event)
                                    "
                                >
                                    <SelectTrigger
                                        class="w-full sm:w-40"
                                        :aria-label="`${member.name}: ${t(
                                            'workspaces.management.invite.role_label',
                                        )}`"
                                    >
                                        <Spinner
                                            v-if="
                                                updatingMemberId === member.id
                                            "
                                        />
                                        <SelectValue v-else />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="member">
                                            {{
                                                t(
                                                    'workspaces.management.members.roles.member',
                                                )
                                            }}
                                        </SelectItem>
                                        <SelectItem value="admin">
                                            {{
                                                t(
                                                    'workspaces.management.members.roles.admin',
                                                )
                                            }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <div
                                    v-else
                                    class="min-w-0 text-left sm:text-right"
                                >
                                    <Badge
                                        variant="outline"
                                        :class="roleClasses[member.role]"
                                    >
                                        {{
                                            t(
                                                `workspaces.management.members.roles.${member.role}`,
                                            )
                                        }}
                                    </Badge>
                                    <p
                                        class="mt-1 hidden max-w-44 truncate text-xs text-muted-foreground lg:block"
                                    >
                                        {{
                                            t(
                                                `workspaces.management.members.role_descriptions.${member.role}`,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end">
                                <Button
                                    v-if="member.permissions.remove"
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="
                                        t(
                                            'workspaces.management.members.remove_action',
                                        )
                                    "
                                    :disabled="removeRequest.processing"
                                    @click="memberToRemove = member"
                                >
                                    <Trash2 aria-hidden="true" />
                                </Button>
                            </div>
                        </li>
                    </ul>
                    <div
                        v-else
                        class="flex min-h-56 flex-col items-center justify-center px-6 py-12 text-center"
                    >
                        <div
                            class="flex size-11 items-center justify-center rounded-full bg-muted"
                        >
                            <Search
                                class="size-5 text-muted-foreground"
                                aria-hidden="true"
                            />
                        </div>
                        <p class="mt-4 text-sm font-semibold">
                            {{ t('workspaces.management.members.no_results') }}
                        </p>
                        <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                            {{
                                t(
                                    'workspaces.management.members.no_results_description',
                                )
                            }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card
                v-if="workspace.permissions?.manage_members"
                class="xl:sticky xl:top-6"
            >
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-xl bg-orange-600 text-white"
                    >
                        <UserPlus class="size-5" aria-hidden="true" />
                    </div>
                    <CardTitle>
                        {{ t('workspaces.management.invite.title') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('workspaces.management.invite.description') }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="inviteMember">
                        <div class="space-y-2">
                            <Label for="workspace-invite-email">
                                {{
                                    t(
                                        'workspaces.management.invite.email_label',
                                    )
                                }}
                            </Label>
                            <div class="relative">
                                <Mail
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="workspace-invite-email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    autocomplete="email"
                                    class="pl-9"
                                    :placeholder="
                                        t(
                                            'workspaces.management.invite.email_placeholder',
                                        )
                                    "
                                    :disabled="inviteForm.processing"
                                    :aria-invalid="
                                        Boolean(inviteForm.errors.email)
                                    "
                                    required
                                    @input="inviteForm.clearErrors('email')"
                                />
                            </div>
                            <InputError :message="inviteForm.errors.email" />
                        </div>
                        <div class="space-y-2">
                            <Label for="workspace-invite-role">
                                {{
                                    t('workspaces.management.invite.role_label')
                                }}
                            </Label>
                            <Select
                                v-model="inviteForm.role"
                                :disabled="inviteForm.processing"
                            >
                                <SelectTrigger
                                    id="workspace-invite-role"
                                    class="w-full"
                                    :aria-invalid="
                                        Boolean(inviteForm.errors.role)
                                    "
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="member">
                                        {{
                                            t(
                                                'workspaces.management.members.roles.member',
                                            )
                                        }}
                                    </SelectItem>
                                    <SelectItem value="admin">
                                        {{
                                            t(
                                                'workspaces.management.members.roles.admin',
                                            )
                                        }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="inviteForm.errors.role" />
                        </div>
                        <Button
                            type="submit"
                            size="lg"
                            class="w-full"
                            :disabled="inviteForm.processing"
                        >
                            <Spinner v-if="inviteForm.processing" />
                            <MailPlus v-else aria-hidden="true" />
                            {{
                                inviteForm.processing
                                    ? t('workspaces.management.invite.sending')
                                    : t('workspaces.management.invite.action')
                            }}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <Card v-else class="bg-muted/25 xl:sticky xl:top-6">
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-xl border bg-background"
                    >
                        <LockKeyhole class="size-5" aria-hidden="true" />
                    </div>
                    <CardTitle>
                        {{ t('workspaces.management.members.title') }}
                    </CardTitle>
                    <CardDescription>
                        {{ t('workspaces.management.members.read_only') }}
                    </CardDescription>
                </CardHeader>
            </Card>
        </div>

        <Card
            v-if="workspace.permissions?.manage_members"
            class="gap-0 overflow-hidden py-0"
        >
            <CardHeader class="border-b py-5 sm:py-6">
                <CardTitle>
                    {{ t('workspaces.management.invitations.title') }}
                </CardTitle>
                <CardDescription>
                    {{ t('workspaces.management.invitations.description') }}
                </CardDescription>
            </CardHeader>
            <CardContent class="p-0">
                <ul v-if="invitations.length" class="divide-y" role="list">
                    <li
                        v-for="invitation in invitations"
                        :key="invitation.id"
                        class="flex flex-col gap-4 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                            >
                                <Mail class="size-4" aria-hidden="true" />
                            </div>
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="truncate text-sm font-semibold">
                                        {{ invitation.email }}
                                    </p>
                                    <Badge variant="outline">
                                        {{
                                            t(
                                                `workspaces.management.members.roles.${invitation.role}`,
                                            )
                                        }}
                                    </Badge>
                                    <Badge
                                        v-if="invitation.is_expired"
                                        variant="destructive"
                                    >
                                        {{
                                            t(
                                                'workspaces.management.invitations.expired',
                                            )
                                        }}
                                    </Badge>
                                </div>
                                <p
                                    class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <Clock3
                                            class="size-3"
                                            aria-hidden="true"
                                        />
                                        {{
                                            t(
                                                'workspaces.management.invitations.sent',
                                                {
                                                    date: formatDate(
                                                        invitation.created_at,
                                                        { dateStyle: 'medium' },
                                                    ),
                                                },
                                            )
                                        }}
                                    </span>
                                    <span>
                                        {{
                                            t(
                                                'workspaces.management.invitations.expires',
                                                {
                                                    date: formatDate(
                                                        invitation.expires_at,
                                                        { dateStyle: 'medium' },
                                                    ),
                                                },
                                            )
                                        }}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <Button
                                v-if="invitation.permissions.resend"
                                variant="outline"
                                size="sm"
                                :disabled="resendRequest.processing"
                                @click="resendInvitation(invitation)"
                            >
                                <Spinner
                                    v-if="
                                        resendingInvitationId === invitation.id
                                    "
                                />
                                <RefreshCw v-else aria-hidden="true" />
                                {{
                                    resendingInvitationId === invitation.id
                                        ? t(
                                              'workspaces.management.invitations.resending',
                                          )
                                        : t(
                                              'workspaces.management.invitations.resend',
                                          )
                                }}
                            </Button>
                            <Button
                                v-if="invitation.permissions.cancel"
                                variant="ghost"
                                size="sm"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                :disabled="cancelRequest.processing"
                                @click="invitationToCancel = invitation"
                            >
                                <Trash2 aria-hidden="true" />
                                {{
                                    t(
                                        'workspaces.management.invitations.cancel',
                                    )
                                }}
                            </Button>
                        </div>
                    </li>
                </ul>
                <div
                    v-else
                    class="flex min-h-40 flex-col items-center justify-center px-6 py-10 text-center"
                >
                    <Mail
                        class="size-6 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{ t('workspaces.management.invitations.empty') }}
                    </p>
                </div>
            </CardContent>
        </Card>

        <WorkspaceConfirmDialog
            :open="Boolean(memberToRemove)"
            :title="t('workspaces.management.members.remove_title')"
            :description="
                t('workspaces.management.members.remove_description', {
                    name: memberToRemove?.name ?? '',
                })
            "
            :confirm-label="t('workspaces.management.members.remove_action')"
            :cancel-label="t('common.actions.cancel')"
            :processing="removeRequest.processing"
            destructive
            @update:open="setRemoveConfirmation"
            @confirm="removeMember"
        />

        <WorkspaceConfirmDialog
            :open="Boolean(invitationToCancel)"
            :title="t('workspaces.management.invitations.cancel')"
            :description="invitationToCancel?.email ?? ''"
            :confirm-label="t('workspaces.management.invitations.cancel')"
            :cancel-label="t('common.actions.cancel')"
            :processing="cancelRequest.processing"
            destructive
            @update:open="setCancelConfirmation"
            @confirm="cancelInvitation"
        />
    </section>
</template>
