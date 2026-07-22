<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowUpRight,
    Bell,
    BellOff,
    Check,
    CheckCheck,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock3,
    Inbox,
    MailOpen,
    MessageSquareText,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import type { Component } from 'vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import WorkspaceSegmentedButton from '@/components/shared/WorkspaceSegmentedButton.vue';
import WorkspaceSegmentedControl from '@/components/shared/WorkspaceSegmentedControl.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useToast } from '@/composables/useToast';
import { useWorkspaceUi } from '@/composables/useWorkspaceUi';
import {
    index as notificationsIndex,
    markAllRead as markAllReadRoute,
    markRead as markReadRoute,
} from '@/routes/notifications';
import type { PaginatedResponse } from '@/types/api';

interface NotificationItem {
    id: string;
    type?: string;
    data: {
        title?: unknown;
        body?: unknown;
        message?: unknown;
        todo_title?: unknown;
        reminder_id?: unknown;
        channel?: unknown;
        kind?: unknown;
        todo_id?: unknown;
        [key: string]: unknown;
    };
    read_at: string | null;
    created_at: string;
    url: string | null;
}

const props = defineProps<{
    notifications: PaginatedResponse<NotificationItem>;
    stats: {
        total: number;
        unread: number;
        read: number;
    };
    filters: {
        status: 'all' | 'unread';
        per_page: number;
    };
}>();

const toast = useToast();
const { copy, formatDate, formatNumber } = useWorkspaceUi();
const processingIds = ref<Set<string>>(new Set());
const markingAll = ref(false);
const filtering = ref(false);
const visibleNotifications = computed(() => props.notifications.data);

function markRead(id: string, onSuccess?: () => void): void {
    if (processingIds.value.has(id)) {
        return;
    }

    processingIds.value = new Set([...processingIds.value, id]);

    router.post(
        markReadRoute({ id }).url,
        {},
        {
            preserveScroll: true,
            only: ['notifications', 'stats'],
            onSuccess,
            onFinish: () => {
                const next = new Set(processingIds.value);
                next.delete(id);
                processingIds.value = next;
            },
        },
    );
}

function markAllRead(): void {
    if (markingAll.value || props.stats.unread === 0) {
        return;
    }

    markingAll.value = true;

    router.post(
        markAllReadRoute().url,
        {},
        {
            preserveScroll: true,
            only: ['notifications', 'stats'],
            onSuccess: () => toast.success(copy.value.notifications.marked_all),
            onFinish: () => {
                markingAll.value = false;
            },
        },
    );
}

function changeStatus(status: 'all' | 'unread'): void {
    if (filtering.value || status === props.filters.status) {
        return;
    }

    filtering.value = true;
    router.get(
        notificationsIndex().url,
        { status, per_page: props.filters.per_page },
        {
            preserveScroll: true,
            preserveState: true,
            only: ['notifications', 'stats', 'filters'],
            onFinish: () => {
                filtering.value = false;
            },
        },
    );
}

function openNotification(notification: NotificationItem): void {
    if (!notification.url) {
        if (!notification.read_at) {
            markRead(notification.id);
        }

        return;
    }

    if (notification.read_at) {
        router.visit(notification.url);

        return;
    }

    markRead(notification.id, () => router.visit(notification.url!));
}

function notificationTitle(notification: NotificationItem): string {
    if (notification.data.kind === 'reminder') {
        return copy.value.notifications.reminder_title;
    }

    if (typeof notification.data.title === 'string') {
        return notification.data.title;
    }

    if (typeof notification.data.todo_title === 'string') {
        return notification.data.todo_title;
    }

    return copy.value.notifications.fallback_title;
}

function notificationBody(notification: NotificationItem): string {
    if (
        notification.data.kind === 'reminder' &&
        typeof notification.data.todo_title === 'string'
    ) {
        return copy.value.notifications.reminder_body.replace(
            ':task',
            notification.data.todo_title,
        );
    }

    if (typeof notification.data.body === 'string') {
        return notification.data.body;
    }

    if (typeof notification.data.message === 'string') {
        return notification.data.message;
    }

    return copy.value.notifications.fallback_body;
}

function showBrowserNotifications(): void {
    if (
        typeof window === 'undefined' ||
        !('Notification' in window) ||
        window.Notification.permission !== 'granted'
    ) {
        return;
    }

    for (const notification of props.notifications.data) {
        if (notification.read_at || notification.data.channel !== 'browser') {
            continue;
        }

        const storageKey = `xiaomi-mimo:browser-reminder:${notification.id}`;

        if (window.localStorage.getItem(storageKey)) {
            continue;
        }

        const browserNotification = new window.Notification(
            notificationTitle(notification),
            {
                body: notificationBody(notification),
                tag: notification.id,
            },
        );
        window.localStorage.setItem(storageKey, 'shown');
        browserNotification.onclick = () => {
            window.focus();
            openNotification(notification);
            browserNotification.close();
        };
    }
}

watch(() => props.notifications.data, showBrowserNotifications, {
    immediate: true,
});

function notificationIcon(notification: NotificationItem): Component {
    const searchable =
        `${notificationTitle(notification)} ${notificationBody(notification)}`.toLowerCase();

    if (notification.data.reminder_id || searchable.includes('remind')) {
        return Clock3;
    }

    if (searchable.includes('comment')) {
        return MessageSquareText;
    }

    if (searchable.includes('complete')) {
        return CheckCircle2;
    }

    if (searchable.includes('overdue')) {
        return AlertTriangle;
    }

    return Bell;
}

function notificationTone(notification: NotificationItem): string {
    if (notification.read_at) {
        return 'bg-muted text-muted-foreground';
    }

    const icon = notificationIcon(notification);

    if (icon === AlertTriangle) {
        return 'bg-red-500/12 text-red-700 dark:text-red-300';
    }

    if (icon === CheckCircle2) {
        return 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300';
    }

    return 'bg-orange-500/12 text-orange-700 dark:text-orange-300';
}
</script>

<template>
    <Head :title="copy.notifications.title" />

    <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
        <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
            <WorkspacePageHeader
                :eyebrow="copy.common.workspace_intelligence"
                :title="copy.notifications.title"
                :description="copy.notifications.description"
            >
                <template #actions>
                    <Button
                        size="lg"
                        :disabled="markingAll || stats.unread === 0"
                        @click="markAllRead"
                    >
                        <Spinner v-if="markingAll" />
                        <CheckCheck v-else class="size-4" aria-hidden="true" />
                        {{ copy.notifications.mark_all }}
                    </Button>
                </template>

                <template #metrics>
                    <WorkspaceMetric
                        :label="copy.notifications.total"
                        :value="formatNumber(stats.total)"
                        :icon="Inbox"
                        tone="orange"
                    />
                    <WorkspaceMetric
                        :label="copy.notifications.unread"
                        :value="formatNumber(stats.unread)"
                        :icon="Bell"
                        tone="blue"
                    />
                    <WorkspaceMetric
                        :label="copy.notifications.cleared"
                        :value="formatNumber(stats.read)"
                        :icon="MailOpen"
                        tone="emerald"
                    />
                </template>
            </WorkspacePageHeader>

            <section
                class="overflow-hidden rounded-[1.5rem] border border-border/80 bg-card shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)]"
            >
                <div
                    class="flex flex-col gap-4 border-b border-border/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                >
                    <WorkspaceSegmentedControl :label="copy.common.filters">
                        <WorkspaceSegmentedButton
                            role="tab"
                            :aria-selected="filters.status === 'all'"
                            :active="filters.status === 'all'"
                            class="px-4"
                            :disabled="filtering"
                            @click="changeStatus('all')"
                        >
                            {{ copy.notifications.all_tab }}
                        </WorkspaceSegmentedButton>
                        <WorkspaceSegmentedButton
                            role="tab"
                            :aria-selected="filters.status === 'unread'"
                            :active="filters.status === 'unread'"
                            class="px-4"
                            :disabled="filtering"
                            @click="changeStatus('unread')"
                        >
                            {{ copy.notifications.unread_tab }}
                            <span
                                v-if="stats.unread"
                                class="rounded-full bg-orange-500 px-1.5 py-0.5 text-[0.65rem] font-semibold text-white tabular-nums"
                            >
                                {{ stats.unread }}
                            </span>
                        </WorkspaceSegmentedButton>
                    </WorkspaceSegmentedControl>

                    <p class="text-xs text-muted-foreground" aria-live="polite">
                        {{ formatNumber(visibleNotifications.length) }} /
                        {{ formatNumber(notifications.total) }}
                    </p>
                </div>

                <div
                    v-if="visibleNotifications.length"
                    class="divide-y divide-border/70"
                >
                    <article
                        v-for="notification in visibleNotifications"
                        :key="notification.id"
                        :class="[
                            'group relative grid animate-in grid-cols-[3rem_minmax(0,1fr)] gap-4 px-4 py-5 transition-colors duration-200 fade-in slide-in-from-bottom-2 motion-reduce:animate-none sm:grid-cols-[3rem_minmax(0,1fr)_auto] sm:items-center sm:px-6',
                            notification.read_at
                                ? 'bg-card hover:bg-muted/30'
                                : 'bg-orange-500/[0.035] hover:bg-orange-500/[0.065]',
                        ]"
                    >
                        <span
                            v-if="!notification.read_at"
                            class="absolute inset-y-0 left-0 w-1 bg-orange-500"
                            aria-hidden="true"
                        />

                        <div
                            :class="[
                                'flex size-12 items-center justify-center rounded-2xl',
                                notificationTone(notification),
                            ]"
                        >
                            <component
                                :is="notificationIcon(notification)"
                                class="size-5"
                                aria-hidden="true"
                            />
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    :class="[
                                        'text-sm leading-6',
                                        notification.read_at
                                            ? 'font-medium'
                                            : 'font-semibold',
                                    ]"
                                >
                                    {{ notificationTitle(notification) }}
                                </h2>
                                <span
                                    v-if="!notification.read_at"
                                    class="size-2 rounded-full bg-orange-500"
                                    :aria-label="
                                        copy.notifications.unread_status
                                    "
                                />
                            </div>
                            <p
                                class="mt-1 max-w-3xl text-sm leading-6 text-muted-foreground"
                            >
                                {{ notificationBody(notification) }}
                            </p>
                            <p class="mt-2 text-xs text-muted-foreground/80">
                                {{
                                    formatDate(notification.created_at, {
                                        month: 'short',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                    })
                                }}
                            </p>
                        </div>

                        <div
                            class="col-start-2 flex items-center gap-2 sm:col-start-auto"
                        >
                            <Button
                                v-if="notification.url"
                                variant="ghost"
                                size="sm"
                                :disabled="processingIds.has(notification.id)"
                                @click="openNotification(notification)"
                            >
                                <ArrowUpRight
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{ copy.notifications.view_task }}
                            </Button>
                            <Button
                                v-if="!notification.read_at"
                                variant="outline"
                                size="sm"
                                class="min-h-10 cursor-pointer rounded-xl border-orange-500/25 text-orange-700 hover:bg-orange-500/10 hover:text-orange-800 dark:text-orange-300 dark:hover:text-orange-200"
                                :disabled="processingIds.has(notification.id)"
                                @click="markRead(notification.id)"
                            >
                                <Check class="size-4" aria-hidden="true" />
                                {{ copy.notifications.mark_read }}
                            </Button>
                            <span
                                v-else
                                class="inline-flex min-h-9 items-center gap-1.5 rounded-full bg-muted px-3 text-xs font-medium text-muted-foreground"
                            >
                                <Check class="size-3.5" aria-hidden="true" />
                                {{ copy.notifications.read_status }}
                            </span>
                        </div>
                    </article>
                </div>

                <EmptyState
                    v-else
                    :title="
                        filters.status === 'unread'
                            ? copy.notifications.empty_unread_title
                            : copy.notifications.empty_title
                    "
                    :description="
                        filters.status === 'unread'
                            ? copy.notifications.empty_unread_description
                            : copy.notifications.empty_description
                    "
                >
                    <template #icon>
                        <BellOff class="size-7" aria-hidden="true" />
                    </template>
                </EmptyState>

                <nav
                    v-if="notifications.last_page > 1"
                    class="flex flex-col gap-3 border-t border-border/70 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6"
                    :aria-label="copy.notifications.pagination_label"
                >
                    <p class="text-sm text-muted-foreground">
                        {{
                            copy.notifications.pagination_range
                                .replace(
                                    ':from',
                                    formatNumber(notifications.from ?? 0),
                                )
                                .replace(
                                    ':to',
                                    formatNumber(notifications.to ?? 0),
                                )
                                .replace(
                                    ':total',
                                    formatNumber(notifications.total),
                                )
                        }}
                    </p>
                    <div class="flex gap-2">
                        <Button
                            v-if="notifications.prev_page_url"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <Link
                                :href="notifications.prev_page_url"
                                :only="['notifications', 'stats', 'filters']"
                                preserve-scroll
                                preserve-state
                            >
                                <ChevronLeft
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{ copy.notifications.previous }}
                            </Link>
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled>
                            <ChevronLeft class="size-4" aria-hidden="true" />
                            {{ copy.notifications.previous }}
                        </Button>
                        <Button
                            v-if="notifications.next_page_url"
                            as-child
                            variant="outline"
                            size="sm"
                        >
                            <Link
                                :href="notifications.next_page_url"
                                :only="['notifications', 'stats', 'filters']"
                                preserve-scroll
                                preserve-state
                            >
                                {{ copy.notifications.next }}
                                <ChevronRight
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </Link>
                        </Button>
                        <Button v-else variant="outline" size="sm" disabled>
                            {{ copy.notifications.next }}
                            <ChevronRight class="size-4" aria-hidden="true" />
                        </Button>
                    </div>
                </nav>
            </section>
        </div>
    </main>
</template>
