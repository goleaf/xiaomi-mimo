<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Todo } from '@/types/models';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import TaskStats from '@/components/task/TaskStats.vue';
import { Clock, AlertTriangle, Calendar } from '@lucide/vue';

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

const allTodos = computed(() => [...props.todayTasks, ...props.overdueTasks, ...props.upcomingTasks]);

function priorityColor(priority: string): string {
    return { urgent: 'bg-red-500', high: 'bg-orange-500', medium: 'bg-yellow-500', low: 'bg-blue-500', none: 'bg-gray-300' }[priority] ?? 'bg-gray-300';
}

function formatDate(date: string | null): string {
    if (!date) return '';
    return new Date(date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

const maxWeekly = computed(() => Math.max(...props.weeklyData.map(d => d.completed), 1));
</script>

<template>
    <Head title="Dashboard" />
    <div class="space-y-6 p-6">
        <div>
            <h1 class="text-2xl font-bold">Dashboard</h1>
            <p class="text-muted-foreground">Welcome back! Here's your task overview.</p>
        </div>

        <TaskStats :todos="allTodos" />

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <AlertTriangle class="h-4 w-4 text-red-500" />
                    <CardTitle class="text-base">Overdue Tasks</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="overdueTasks.length === 0" class="text-sm text-muted-foreground py-4">No overdue tasks</div>
                    <div v-else class="space-y-2">
                        <div v-for="todo in overdueTasks" :key="todo.id" class="flex items-center gap-3 rounded-lg border p-3">
                            <div :class="['h-2 w-2 rounded-full shrink-0', priorityColor(todo.priority)]" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ todo.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</p>
                            </div>
                            <Badge variant="destructive">overdue</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center gap-2">
                    <Calendar class="h-4 w-4 text-blue-500" />
                    <CardTitle class="text-base">Upcoming Tasks</CardTitle>
                </CardHeader>
                <CardContent>
                    <div v-if="upcomingTasks.length === 0" class="text-sm text-muted-foreground py-4">No upcoming tasks</div>
                    <div v-else class="space-y-2">
                        <div v-for="todo in upcomingTasks" :key="todo.id" class="flex items-center gap-3 rounded-lg border p-3">
                            <div :class="['h-2 w-2 rounded-full shrink-0', priorityColor(todo.priority)]" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ todo.title }}</p>
                                <p class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }}</p>
                            </div>
                            <Badge variant="outline">{{ todo.status }}</Badge>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader><CardTitle class="text-base">Weekly Overview</CardTitle></CardHeader>
            <CardContent>
                <div class="flex items-end gap-2 h-32">
                    <div v-for="day in weeklyData" :key="day.date" class="flex-1 flex flex-col items-center gap-1">
                        <div class="text-xs text-muted-foreground font-medium">{{ day.completed }}</div>
                        <div class="w-full bg-primary/80 rounded-t transition-all" :style="{ height: `${Math.max((day.completed / maxWeekly) * 100, 4)}px` }" />
                        <div class="text-[10px] text-muted-foreground">{{ new Date(day.date).toLocaleDateString('en-US', { weekday: 'short' }) }}</div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
