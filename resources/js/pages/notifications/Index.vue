<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useToast } from '@/composables/useToast';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Bell, BellOff, CheckCheck } from '@lucide/vue';

const props = defineProps<{
    notifications: { data: Array<{ id: string; data: { message?: string; todo_title?: string; [key: string]: unknown }; read_at: string | null; created_at: string }> };
}>();

const toast = useToast();

function markRead(id: string) {
    router.post(route('notifications.markRead', id), {}, { preserveScroll: true });
}

function markAllRead() {
    router.post(route('notifications.markAllRead'), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('All notifications marked as read'),
    });
}

function formatDate(date: string): string {
    return new Date(date).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Notifications" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Notifications</h1>
                <p class="text-muted-foreground">{{ notifications.data.length }} notification(s)</p>
            </div>
            <Button variant="outline" @click="markAllRead"><CheckCheck class="mr-2 h-4 w-4" />Mark all read</Button>
        </div>

        <div class="space-y-3">
            <Card v-for="notification in notifications.data" :key="notification.id"
                :class="['transition-colors', !notification.read_at ? 'border-l-2 border-l-primary' : '']">
                <CardContent class="flex items-center gap-4 py-4">
                    <div :class="['h-8 w-8 rounded-full flex items-center justify-center', notification.read_at ? 'bg-muted' : 'bg-primary/10']">
                        <Bell :class="['h-4 w-4', notification.read_at ? 'text-muted-foreground' : 'text-primary']" />
                    </div>
                    <div class="flex-1">
                        <p class="text-sm">{{ notification.data.message ?? notification.data.todo_title ?? 'Notification' }}</p>
                        <p class="text-xs text-muted-foreground">{{ formatDate(notification.created_at) }}</p>
                    </div>
                    <Button v-if="!notification.read_at" variant="ghost" size="sm" @click="markRead(notification.id)">
                        Mark read
                    </Button>
                    <Badge v-else variant="secondary">Read</Badge>
                </CardContent>
            </Card>
        </div>

        <div v-if="notifications.data.length === 0" class="flex flex-col items-center justify-center py-12 text-muted-foreground">
            <BellOff class="h-12 w-12 mb-4 opacity-50" />
            <p>No notifications yet</p>
        </div>
    </div>
</template>
