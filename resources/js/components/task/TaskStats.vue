<script setup lang="ts">
import {
    CheckCircle,
    Clock,
    AlertTriangle,
    TrendingUp,
    List,
} from '@lucide/vue';
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Todo } from '@/types/models';

const props = defineProps<{ todos: Todo[] }>();

const stats = computed(() => {
    const total = props.todos.length;
    const completed = props.todos.filter(
        (t) => t.status === 'completed',
    ).length;
    const inProgress = props.todos.filter(
        (t) => t.status === 'in_progress',
    ).length;
    const pending = props.todos.filter((t) => t.status === 'pending').length;
    const today = new Date().toISOString().split('T')[0];
    const overdue = props.todos.filter(
        (t) => t.due_date && t.due_date < today && t.status !== 'completed',
    ).length;

    return {
        total,
        completed,
        inProgress,
        pending,
        overdue,
        completionRate: total > 0 ? Math.round((completed / total) * 100) : 0,
    };
});
</script>

<template>
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Total</CardTitle>
                <List class="h-4 w-4 text-muted-foreground" />
            </CardHeader>
            <CardContent
                ><div class="text-2xl font-bold">
                    {{ stats.total }}
                </div></CardContent
            >
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Pending</CardTitle>
                <Clock class="h-4 w-4 text-blue-500" />
            </CardHeader>
            <CardContent
                ><div class="text-2xl font-bold">
                    {{ stats.pending }}
                </div></CardContent
            >
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">In Progress</CardTitle>
                <TrendingUp class="h-4 w-4 text-orange-500" />
            </CardHeader>
            <CardContent
                ><div class="text-2xl font-bold">
                    {{ stats.inProgress }}
                </div></CardContent
            >
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Completed</CardTitle>
                <CheckCircle class="h-4 w-4 text-green-500" />
            </CardHeader>
            <CardContent
                ><div class="text-2xl font-bold">
                    {{ stats.completed }}
                </div></CardContent
            >
        </Card>
        <Card>
            <CardHeader
                class="flex flex-row items-center justify-between space-y-0 pb-2"
            >
                <CardTitle class="text-sm font-medium">Overdue</CardTitle>
                <AlertTriangle class="h-4 w-4 text-red-500" />
            </CardHeader>
            <CardContent
                ><div class="text-2xl font-bold">
                    {{ stats.overdue }}
                </div></CardContent
            >
        </Card>
    </div>
</template>
