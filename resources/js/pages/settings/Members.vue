<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';
import type { Workspace, User } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { UserPlus, Trash2 } from '@lucide/vue';

const props = defineProps<{
    workspace: Workspace;
    members: Array<{ id: string; user: User; role: string }>;
}>();

const toast = useToast();
const inviteForm = useForm({ email: '', role: 'member' });

function invite() {
    inviteForm.post(route('workspaces.invite', props.workspace.id), {
        preserveScroll: true,
        onSuccess: () => { toast.success('Member invited'); inviteForm.reset(); },
    });
}

function removeMember(memberId: string) {
    if (confirm('Remove this member?')) {
        router.delete(route('workspaces.removeMember', [props.workspace.id, memberId]), {
            preserveScroll: true,
            onSuccess: () => toast.success('Member removed'),
        });
    }
}

function roleBadge(role: string) {
    return { owner: 'default', admin: 'secondary', member: 'outline' }[role] ?? 'outline';
}
</script>

<template>
    <Head title="Members" />
    <div class="space-y-6">
        <div>
            <h2 class="text-lg font-semibold">Workspace Members</h2>
            <p class="text-sm text-muted-foreground">Manage who has access to this workspace</p>
        </div>

        <Card>
            <CardHeader><CardTitle>Invite Member</CardTitle></CardHeader>
            <CardContent>
                <form @submit.prevent="invite" class="flex gap-3">
                    <Input v-model="inviteForm.email" type="email" placeholder="Email address" class="flex-1" required />
                    <Select v-model="inviteForm.role">
                        <SelectTrigger class="w-[120px]"><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem value="member">Member</SelectItem>
                            <SelectItem value="admin">Admin</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button type="submit" :disabled="inviteForm.processing">
                        <UserPlus class="mr-2 h-4 w-4" />Invite
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Members ({{ members.length }})</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="space-y-3">
                    <div v-for="member in members" :key="member.id"
                        class="flex items-center justify-between rounded-lg border p-4">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-full bg-muted flex items-center justify-center text-sm font-medium">
                                {{ member.user.name?.charAt(0) ?? '?' }}
                            </div>
                            <div>
                                <p class="text-sm font-medium">{{ member.user.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ member.user.email }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge :variant="roleBadge(member.role)">{{ member.role }}</Badge>
                            <Button v-if="member.role !== 'owner'" variant="ghost" size="sm"
                                @click="removeMember(member.id)">
                                <Trash2 class="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
