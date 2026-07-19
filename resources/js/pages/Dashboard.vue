<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { AlertTriangle, Calendar } from '@lucide/vue';
import { computed } from 'vue';
import TaskStats from '@/components/task/TaskStats.vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useUi } from '@/composables/useUi';
import type { Todo } from '@/types/models';

const props = defineProps<{
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

const allTodos = computed(() => [
    ...props.todayTasks,
    ...props.overdueTasks,
    ...props.upcomingTasks,
]);

function priorityColor(priority: string): string {
    return (
        {
            urgent: 'bg-red-500',
            high: 'bg-orange-500',
            medium: 'bg-yellow-500',
            low: 'bg-blue-500',
            none: 'bg-gray-300',
        }[priority] ?? 'bg-gray-300'
    );
}

function formatDate(date: string | null): string {
    if (!date) {
        return '';
    }

    return formatLocalizedDate(date, {
        month: 'short',
        day: 'numeric',
    });
}

const maxWeekly = computed(() =>
    Math.max(...props.weeklyData.map((d) => d.completed), 1),
);
</script>

<template>
    <Head :title="t('dashboard.title')" />
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold">{{ t('dashboard.title') }}</h1>
            <p class="text-muted-foreground">
                {{ t('dashboard.welcome') }}
            </p>
        </div>

        <TaskStats :todos="allTodos" />

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <AlertTriangle class="h-4 w-4 text-red-500" />
                    <CardTitle class="text-base">{{
                        t('dashboard.overdue_tasks')
                    }}</CardTitle>
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
                            class="flex items-center gap-3 rounded-lg border p-3"
                        >
                            <div
                                :class="[
                                    'h-2 w-2 shrink-0 rounded-full',
                                    priorityColor(todo.priority),
                                ]"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ todo.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(todo.due_date) }}
                                </p>
                            </div>
                            <Badge variant="destructive">{{
                                t('dashboard.overdue')
                            }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <Calendar class="h-4 w-4 text-blue-500" />
                    <CardTitle class="text-base">{{
                        t('dashboard.upcoming_tasks')
                    }}</CardTitle>
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
                            class="flex items-center gap-3 rounded-lg border p-3"
                        >
                            <div
                                :class="[
                                    'h-2 w-2 shrink-0 rounded-full',
                                    priorityColor(todo.priority),
                                ]"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">
                                    {{ todo.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ formatDate(todo.due_date) }}
                                </p>
                            </div>
                            <Badge variant="outline">{{
                                t(`tasks.statuses.${todo.status}`)
                            }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader
                ><CardTitle class="text-base">{{
                    t('dashboard.weekly_overview')
                }}</CardTitle></CardHeader
            >
            <CardContent>
                <div class="flex h-32 items-end gap-2">
                    <div
                        v-for="day in weeklyData"
                        :key="day.date"
                        class="flex flex-1 flex-col items-center gap-1"
                    >
                        <div class="text-xs font-medium text-muted-foreground">
                            {{ formatNumber(day.completed) }}
                        </div>
                        <div
                            class="w-full rounded-t bg-primary/80 transition-all"
                            :style="{
                                height: `${Math.max((day.completed / maxWeekly) * 100, 4)}px`,
                            }"
                        />
                        <div class="text-[10px] text-muted-foreground">
                            {{
                                formatLocalizedDate(day.date, {
                                    weekday: 'short',
                                })
                            }}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
