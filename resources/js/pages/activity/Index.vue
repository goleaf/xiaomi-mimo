<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { User } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { ActivityLog } from '@/types/models';

defineProps<{ activities: { data: ActivityLog[] } }>();

function eventBadge(event: string): string {
    return (
        {
            created: 'default',
            updated: 'secondary',
            completed: 'default',
            deleted: 'destructive',
            archived: 'outline',
            restored: 'outline',
        }[event] ?? 'outline'
    );
}

function formatDate(date: string): string {
    return new Date(date).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function eventVerb(event: string): string {
    const map: Record<string, string> = {
        created: 'created',
        updated: 'updated',
        completed: 'completed',
        uncompleted: 'uncompleted',
        deleted: 'deleted',
        restored: 'restored',
        archived: 'archived',
        unarchived: 'unarchived',
        pinned: 'pinned',
        unpinned: 'unpinned',
        favorited: 'favorited',
        unfavorited: 'unfavorited',
        attached: 'attached',
        detached: 'detached',
    };
    return map[event] ?? event;
}

function subjectName(type: string): string {
    return type.split('\\').pop() ?? type;
}
</script>

<template>
    <Head title="Activity" />
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold">Activity Log</h1>
            <p class="text-muted-foreground">
                Track all changes in your workspace
            </p>
        </div>

        <div class="space-y-3">
            <Card v-for="activity in activities.data" :key="activity.id">
                <CardContent class="flex items-center gap-4 py-4">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-muted"
                    >
                        <User class="h-4 w-4 text-muted-foreground" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm">
                            <span class="font-medium">{{
                                activity.user?.name ?? 'System'
                            }}</span>
                            {{ eventVerb(activity.event) }} a
                            <span class="font-medium">{{
                                subjectName(activity.subject_type)
                            }}</span>
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(activity.created_at) }}
                        </p>
                    </div>
                    <Badge :variant="eventBadge(activity.event)">{{
                        activity.event
                    }}</Badge>
                </CardContent>
            </Card>
        </div>

        <div
            v-if="activities.data.length === 0"
            class="py-12 text-center text-muted-foreground"
        >
            <p>No activity yet</p>
        </div>
    </div>
</template>
