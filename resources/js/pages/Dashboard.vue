<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Calendar,
    CalendarClock,
    CheckCircle2,
    ListChecks,
} from '@lucide/vue';
import ProductivityChart from '@/components/dashboard/ProductivityChart.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useUi } from '@/composables/useUi';
import type { Todo } from '@/types/models';

defineProps<{
    stats: {
        today_count: number;
        overdue_count: number;
        completed_today: number;
        total_tasks: number;
        completed_total: number;
        completion_rate: number;
    };
    todayTasks: Todo[];
    overdueTasks: Todo[];
    upcomingTasks: Todo[];
    weeklyData: Array<{ date: string; completed: number; created: number }>;
}>();
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();

function priorityColor(priority: string): string {
    return (
        {
            urgent: 'bg-red-500',
            high: 'bg-orange-500',
            medium: 'bg-yellow-500',
            low: 'bg-blue-500',
            none: 'bg-muted-foreground/35',
        }[priority] ?? 'bg-muted-foreground/35'
    );
}

function formatDate(date: string | null): string {
    if (!date) {
        return '';
    }

    return formatLocalizedDate(date, {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <div>
        <Head :title="t('dashboard.title')" />

        <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
            <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
                <WorkspacePageHeader
                    :eyebrow="t('dashboard.weekly_productivity')"
                    :title="t('dashboard.title')"
                    :description="
                        t('dashboard.welcome', {
                            completed: formatNumber(stats.completed_total),
                            total: formatNumber(stats.total_tasks),
                            rate: formatNumber(stats.completion_rate),
                        })
                    "
                >
                    <template #metrics>
                        <WorkspaceMetric
                            :label="t('tasks.stats.total')"
                            :value="formatNumber(stats.total_tasks)"
                            :icon="ListChecks"
                            tone="orange"
                        />
                        <WorkspaceMetric
                            :label="t('dashboard.due_today')"
                            :value="formatNumber(stats.today_count)"
                            :icon="CalendarClock"
                            tone="blue"
                        />
                        <WorkspaceMetric
                            :label="t('dashboard.completed_today')"
                            :value="formatNumber(stats.completed_today)"
                            :icon="CheckCircle2"
                            tone="emerald"
                        />
                        <WorkspaceMetric
                            :label="t('tasks.stats.overdue')"
                            :value="formatNumber(stats.overdue_count)"
                            :icon="AlertTriangle"
                            tone="slate"
                        />
                    </template>
                </WorkspacePageHeader>

                <div class="grid gap-5 lg:grid-cols-2 xl:grid-cols-3">
                    <Card class="overflow-hidden">
                        <CardHeader class="flex flex-row items-center gap-3">
                            <div
                                class="flex size-9 items-center justify-center rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-300"
                            >
                                <CalendarClock
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </div>
                            <CardTitle class="text-base">
                                {{ t('dashboard.today_tasks') }}
                            </CardTitle>
                            <Badge variant="secondary" class="ml-auto">
                                {{ formatNumber(stats.today_count) }}
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="todayTasks.length === 0"
                                class="py-4 text-sm text-muted-foreground"
                            >
                                {{ t('dashboard.no_today') }}
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="todo in todayTasks"
                                    :key="todo.id"
                                    class="flex items-center gap-3 rounded-xl border border-border/80 bg-background p-3"
                                >
                                    <div
                                        :class="[
                                            'size-2 shrink-0 rounded-full',
                                            priorityColor(todo.priority),
                                        ]"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">
                                            {{ todo.title }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ formatDate(todo.due_date) }}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {{ t(`tasks.statuses.${todo.status}`) }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="overflow-hidden">
                        <CardHeader class="flex flex-row items-center gap-3">
                            <div
                                class="flex size-9 items-center justify-center rounded-xl bg-red-500/10 text-red-600 dark:text-red-300"
                            >
                                <AlertTriangle
                                    class="size-4"
                                    aria-hidden="true"
                                />
                            </div>
                            <CardTitle class="text-base">
                                {{ t('dashboard.overdue_tasks') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="overdueTasks.length === 0"
                                class="py-4 text-sm text-muted-foreground"
                            >
                                {{ t('dashboard.no_overdue') }}
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="todo in overdueTasks"
                                    :key="todo.id"
                                    class="flex items-center gap-3 rounded-xl border border-border/80 bg-background p-3"
                                >
                                    <div
                                        :class="[
                                            'size-2 shrink-0 rounded-full',
                                            priorityColor(todo.priority),
                                        ]"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">
                                            {{ todo.title }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ formatDate(todo.due_date) }}
                                        </p>
                                    </div>
                                    <Badge variant="destructive">
                                        {{ t('dashboard.overdue') }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card class="overflow-hidden">
                        <CardHeader class="flex flex-row items-center gap-3">
                            <div
                                class="flex size-9 items-center justify-center rounded-xl bg-sky-500/10 text-sky-700 dark:text-sky-300"
                            >
                                <Calendar class="size-4" aria-hidden="true" />
                            </div>
                            <CardTitle class="text-base">
                                {{ t('dashboard.upcoming_tasks') }}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div
                                v-if="upcomingTasks.length === 0"
                                class="py-4 text-sm text-muted-foreground"
                            >
                                {{ t('dashboard.no_upcoming') }}
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="todo in upcomingTasks"
                                    :key="todo.id"
                                    class="flex items-center gap-3 rounded-xl border border-border/80 bg-background p-3"
                                >
                                    <div
                                        :class="[
                                            'size-2 shrink-0 rounded-full',
                                            priorityColor(todo.priority),
                                        ]"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium">
                                            {{ todo.title }}
                                        </p>
                                        <p
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ formatDate(todo.due_date) }}
                                        </p>
                                    </div>
                                    <Badge variant="outline">
                                        {{ t(`tasks.statuses.${todo.status}`) }}
                                    </Badge>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <ProductivityChart :data="weeklyData" />
            </div>
        </main>
    </div>
</template>
