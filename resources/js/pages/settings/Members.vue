<script setup lang="ts">
import { Head, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    LoaderCircle,
    LockKeyhole,
    Mail,
    Search,
    ShieldCheck,
    Trash2,
    UserCog,
    UserPlus,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import {
    invite as inviteWorkspaceMember,
    removeMember as removeWorkspaceMember,
} from '@/actions/App/Http/Controllers/WorkspaceController';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useToast } from '@/composables/useToast';
import type { SettingsLayoutProps } from '@/types';

type WorkspaceRole = 'owner' | 'admin' | 'member';

interface WorkspaceSummary {
    id: string;
    name: string;
    slug: string;
    owner_id: string;
}

interface WorkspaceMember {
    id: string;
    name: string;
    email: string;
    role: WorkspaceRole;
    is_current_user: boolean;
    can_remove: boolean;
}

interface MembersCopy {
    page_title: string;
    eyebrow: string;
    title: string;
    description: string;
    total_members: string;
    managers: string;
    roster_title: string;
    roster_description: string;
    search_label: string;
    search_placeholder: string;
    no_results_title: string;
    no_results_description: string;
    current_user: string;
    invite_title: string;
    invite_description: string;
    email_label: string;
    email_placeholder: string;
    role_label: string;
    invite_action: string;
    inviting: string;
    invite_success: string;
    read_only_title: string;
    read_only_description: string;
    remove_member: string;
    remove_title: string;
    remove_description: string;
    cancel: string;
    remove_action: string;
    removing: string;
    remove_success: string;
    roles: Record<WorkspaceRole, string>;
    role_descriptions: Record<WorkspaceRole, string>;
}

const props = defineProps<{
    workspace: WorkspaceSummary;
    members: WorkspaceMember[];
    can_manage_members: boolean;
    locale: string;
    copy: MembersCopy;
}>();

const toast = useToast();
const searchQuery = ref('');
const memberToRemove = ref<WorkspaceMember | null>(null);
const inviteForm = useForm<{ email: string; role: 'admin' | 'member' }>({
    email: '',
    role: 'member',
});
const removeForm = useForm({});

const managerCount = computed(
    () => props.members.filter((member) => member.role !== 'member').length,
);

setLayoutProps<SettingsLayoutProps>({
    settingsEyebrow: props.copy.eyebrow,
    settingsTitle: props.copy.title.replace(':workspace', props.workspace.name),
    settingsDescription: props.copy.description,
    settingsMetrics: [
        {
            label: props.copy.total_members,
            value: props.members.length,
            icon: 'users',
            tone: 'orange',
        },
        {
            label: props.copy.managers,
            value: managerCount.value,
            icon: 'shield',
            tone: 'emerald',
        },
    ],
});

const filteredMembers = computed(() => {
    const query = searchQuery.value.trim().toLocaleLowerCase(props.locale);

    if (!query) {
        return props.members;
    }

    return props.members.filter((member) =>
        `${member.name} ${member.email} ${props.copy.roles[member.role]}`
            .toLocaleLowerCase(props.locale)
            .includes(query),
    );
});

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

function invite(): void {
    inviteForm.submit(
        inviteWorkspaceMember({ workspace: props.workspace.id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(props.copy.invite_success);
                inviteForm.reset('email');
            },
        },
    );
}

function openRemoveDialog(member: WorkspaceMember): void {
    memberToRemove.value = member;
}

function handleRemoveDialogOpen(open: boolean): void {
    if (!open && !removeForm.processing) {
        memberToRemove.value = null;
    }
}

function removeMember(): void {
    if (!memberToRemove.value) {
        return;
    }

    removeForm.submit(
        removeWorkspaceMember({
            workspace: props.workspace.id,
            userId: memberToRemove.value.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(props.copy.remove_success);
                memberToRemove.value = null;
            },
        },
    );
}
</script>

<template>
    <Head :title="copy.page_title" />

    <div class="space-y-6 pb-8">
        <div
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.75fr)]"
        >
            <Card class="gap-0 overflow-hidden py-0">
                <CardHeader class="gap-5 border-b py-5 sm:py-6">
                    <div class="space-y-1.5">
                        <CardTitle>{{ copy.roster_title }}</CardTitle>
                        <CardDescription>
                            {{ copy.roster_description }}
                        </CardDescription>
                    </div>

                    <div class="relative sm:max-w-sm">
                        <Label for="member-search" class="sr-only">
                            {{ copy.search_label }}
                        </Label>
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <Input
                            id="member-search"
                            v-model="searchQuery"
                            type="search"
                            :placeholder="copy.search_placeholder"
                            class="pl-9"
                        />
                    </div>
                </CardHeader>

                <CardContent class="p-0">
                    <ul
                        v-if="filteredMembers.length"
                        class="divide-y"
                        role="list"
                    >
                        <li
                            v-for="member in filteredMembers"
                            :key="member.id"
                            class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-3 gap-y-3 px-5 py-4 transition-colors hover:bg-muted/35 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center sm:px-6"
                        >
                            <Avatar class="size-11 border shadow-xs">
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
                                        {{ copy.current_user }}
                                    </Badge>
                                </div>
                                <a
                                    :href="`mailto:${member.email}`"
                                    class="mt-0.5 block truncate text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                                >
                                    {{ member.email }}
                                </a>
                            </div>

                            <div
                                class="col-span-2 flex items-center justify-between gap-3 pl-14 sm:col-span-1 sm:justify-end sm:pl-0"
                            >
                                <div class="min-w-0 text-right">
                                    <Badge
                                        variant="outline"
                                        :class="roleClasses[member.role]"
                                    >
                                        {{ copy.roles[member.role] }}
                                    </Badge>
                                    <p
                                        class="mt-1 hidden max-w-40 truncate text-xs text-muted-foreground lg:block"
                                    >
                                        {{
                                            copy.role_descriptions[member.role]
                                        }}
                                    </p>
                                </div>

                                <Button
                                    v-if="member.can_remove"
                                    type="button"
                                    variant="ghost"
                                    size="icon-sm"
                                    class="text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                    :aria-label="
                                        copy.remove_member.replace(
                                            ':name',
                                            member.name,
                                        )
                                    "
                                    @click="openRemoveDialog(member)"
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
                            {{ copy.no_results_title }}
                        </p>
                        <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                            {{ copy.no_results_description }}
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card v-if="can_manage_members" class="xl:sticky xl:top-6">
                <CardHeader>
                    <div
                        class="mb-2 flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                    >
                        <UserPlus class="size-5" aria-hidden="true" />
                    </div>
                    <CardTitle>{{ copy.invite_title }}</CardTitle>
                    <CardDescription>
                        {{ copy.invite_description }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form class="space-y-4" @submit.prevent="invite">
                        <div class="space-y-2">
                            <Label for="invite-email">
                                {{ copy.email_label }}
                            </Label>
                            <div class="relative">
                                <Mail
                                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <Input
                                    id="invite-email"
                                    v-model="inviteForm.email"
                                    type="email"
                                    autocomplete="email"
                                    :placeholder="copy.email_placeholder"
                                    class="pl-9"
                                    :aria-invalid="
                                        Boolean(inviteForm.errors.email)
                                    "
                                    required
                                />
                            </div>
                            <p
                                v-if="inviteForm.errors.email"
                                class="text-sm text-destructive"
                                role="alert"
                            >
                                {{ inviteForm.errors.email }}
                            </p>
                        </div>

                        <div class="space-y-2">
                            <Label for="invite-role">
                                {{ copy.role_label }}
                            </Label>
                            <Select v-model="inviteForm.role">
                                <SelectTrigger
                                    id="invite-role"
                                    class="w-full"
                                    :aria-invalid="
                                        Boolean(inviteForm.errors.role)
                                    "
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="member">
                                        {{ copy.roles.member }}
                                    </SelectItem>
                                    <SelectItem value="admin">
                                        {{ copy.roles.admin }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <p
                                v-if="inviteForm.errors.role"
                                class="text-sm text-destructive"
                                role="alert"
                            >
                                {{ inviteForm.errors.role }}
                            </p>
                        </div>

                        <Button
                            type="submit"
                            class="w-full"
                            :disabled="inviteForm.processing"
                        >
                            <LoaderCircle
                                v-if="inviteForm.processing"
                                class="animate-spin"
                                aria-hidden="true"
                            />
                            <UserPlus v-else aria-hidden="true" />
                            {{
                                inviteForm.processing
                                    ? copy.inviting
                                    : copy.invite_action
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
                    <CardTitle>{{ copy.read_only_title }}</CardTitle>
                    <CardDescription>
                        {{ copy.read_only_description }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        class="flex items-center gap-3 rounded-xl border bg-background p-3 text-sm"
                    >
                        <ShieldCheck
                            class="size-5 shrink-0 text-muted-foreground"
                            aria-hidden="true"
                        />
                        <span>{{ workspace.name }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Dialog
            :open="memberToRemove !== null"
            @update:open="handleRemoveDialogOpen"
        >
            <WorkspaceDialogContent
                :title="copy.remove_title"
                :description="
                    memberToRemove
                        ? `${memberToRemove.name} — ${copy.remove_description}`
                        : copy.remove_description
                "
                :close-label="copy.cancel"
                accent="red"
                max-width-class="sm:max-w-md"
            >
                <div class="space-y-6 px-6 py-6 sm:px-8">
                    <div
                        class="flex size-11 items-center justify-center rounded-2xl border border-destructive/15 bg-destructive/10 text-destructive"
                    >
                        <UserCog class="size-5" aria-hidden="true" />
                    </div>
                    <DialogFooter
                        class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11 cursor-pointer rounded-xl"
                            :disabled="removeForm.processing"
                            @click="memberToRemove = null"
                        >
                            {{ copy.cancel }}
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            class="min-h-11 cursor-pointer rounded-xl"
                            :disabled="removeForm.processing"
                            @click="removeMember"
                        >
                            <LoaderCircle
                                v-if="removeForm.processing"
                                class="animate-spin"
                                aria-hidden="true"
                            />
                            <Trash2 v-else aria-hidden="true" />
                            {{
                                removeForm.processing
                                    ? copy.removing
                                    : copy.remove_action
                            }}
                        </Button>
                    </DialogFooter>
                </div>
            </WorkspaceDialogContent>
        </Dialog>
    </div>
</template>
