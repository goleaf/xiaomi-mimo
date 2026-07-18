<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Todo } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { CheckCircle, Clock, AlertTriangle, TrendingUp } from '@lucide/vue';

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

const statCards = computed(() => [
    { title: "Today's Tasks", value: props.stats.today_count, icon: Clock, color: 'text-blue-500' },
    { title: 'Overdue', value: props.stats.overdue_count, icon: AlertTriangle, color: 'text-red-500' },
    { title: 'Completed Today', value: props.stats.completed_today, icon: CheckCircle, color: 'text-green-500' },
    { title: 'Completion Rate', value: `${props.stats.completion_rate}%`, icon: TrendingUp, color: 'text-purple-500' },
]);

function priorityColor(priority: string): string {
    return { urgent: 'bg-red-500', high: 'bg-orange-500', medium: 'bg-yellow-500', low: 'bg-blue-500', none: 'bg-gray-300' }[priority] ?? 'bg-gray-300';
}

function formatDate(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-muted-foreground">Welcome back! Here's your task overview.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <Card v-for="stat in statCards" :key="stat.title">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">{{ stat.title }}</CardTitle>
                    <component :is="stat.icon" :class="['h-4 w-4', stat.color]" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stat.value }}</div>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Overdue Tasks</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="overdueTasks.length === 0" class="text-sm text-muted-foreground">No overdue tasks</div>
                    <div v-else class="space-y-2">
                        <div v-for="todo in overdueTasks" :key="todo.id" class="flex items-center gap-3 rounded-lg border p-3">
                            <div :class="['h-2 w-2 rounded-full', priorityColor(todo.priority)]" />
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ todo.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</p>
                            </div>
                            <Badge variant="outline">{{ todo.priority }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Upcoming Tasks</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="upcomingTasks.length === 0" class="text-sm text-muted-foreground">No upcoming tasks</div>
                    <div v-else class="space-y-2">
                        <div v-for="todo in upcomingTasks" :key="todo.id" class="flex items-center gap-3 rounded-lg border p-3">
                            <div :class="['h-2 w-2 rounded-full', priorityColor(todo.priority)]" />
                            <div class="flex-1">
                                <p class="text-sm font-medium">{{ todo.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</p>
                            </div>
                            <Badge variant="outline">{{ todo.status }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Weekly Overview</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="flex items-end gap-2 h-40">
                    <div
                        v-for="day in weeklyData"
                        :key="day.date"
                        class="flex-1 flex flex-col items-center gap-1"
                    >
                        <div class="text-xs text-muted-foreground">{{ day.completed }}</div>
                        <div
                            class="w-full bg-primary rounded-t"
                            :style="{ height: `${Math.max((day.completed / Math.max(...weeklyData.map(d => d.completed), 1)) * 100, 4)}px` }"
                        />
                        <div class="text-xs text-muted-foreground">
                            {{ new Date(day.date).toLocaleDateString('en-US', { weekday: 'short' }) }}
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
