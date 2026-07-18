import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

export const useNotificationStore = defineStore('notification', () => {
    const notifications = ref<Array<{ id: string; read_at: string | null; [key: string]: unknown }>>([]);
    const unreadCount = computed(() => notifications.value.filter((n) => !n.read_at).length);

    function markRead(id: string) {
        router.post(`/notifications/${id}/read`, {}, { preserveScroll: true });
        const notification = notifications.value.find((n) => n.id === id);
        if (notification) {
            notification.read_at = new Date().toISOString();
        }
    }

    function markAllRead() {
        router.post('/notifications/read-all', {}, { preserveScroll: true });
        notifications.value.forEach((n) => {
            n.read_at = new Date().toISOString();
        });
    }

    function setNotifications(data: Array<{ id: string; read_at: string | null; [key: string]: unknown }>) {
        notifications.value = data;
    }

    function addNotification(notification: { id: string; read_at: string | null; [key: string]: unknown }) {
        notifications.value.unshift(notification);
    }

    return {
        notifications,
        unreadCount,
        markRead,
        markAllRead,
        setNotifications,
        addNotification,
    };
});
