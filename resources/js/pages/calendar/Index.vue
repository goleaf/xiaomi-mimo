<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { Todo } from '@/types/models';

const props = defineProps<{ todos: Todo[] }>();

const currentDate = ref(new Date());
const view = ref<'month' | 'week' | 'day'>('month');

const monthName = computed(() =>
    currentDate.value.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    }),
);

const daysInMonth = computed(() => {
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    return new Date(year, month + 1, 0).getDate();
});

const firstDayOfMonth = computed(() =>
    new Date(
        currentDate.value.getFullYear(),
        currentDate.value.getMonth(),
        1,
    ).getDay(),
);

const calendarDays = computed(() => {
    const days: Array<{ date: Date; isCurrentMonth: boolean; todos: Todo[] }> =
        [];
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    const startPadding = firstDayOfMonth.value;

    for (let i = startPadding - 1; i >= 0; i--) {
        const d = new Date(year, month, -i);
        days.push({ date: d, isCurrentMonth: false, todos: [] });
    }

    for (let i = 1; i <= daysInMonth.value; i++) {
        const d = new Date(year, month, i);
        const dateStr = d.toISOString().split('T')[0];
        const dayTodos = props.todos.filter((t) => t.due_date === dateStr);
        days.push({ date: d, isCurrentMonth: true, todos: dayTodos });
    }

    const remaining = 42 - days.length;

    for (let i = 1; i <= remaining; i++) {
        const d = new Date(year, month + 1, i);
        days.push({ date: d, isCurrentMonth: false, todos: [] });
    }

    return days;
});

function prevMonth() {
    const d = new Date(currentDate.value);
    d.setMonth(d.getMonth() - 1);
    currentDate.value = d;
}

function nextMonth() {
    const d = new Date(currentDate.value);
    d.setMonth(d.getMonth() + 1);
    currentDate.value = d;
}

function today() {
    currentDate.value = new Date();
}

function isToday(date: Date): boolean {
    const today = new Date();

    return date.toDateString() === today.toDateString();
}

function priorityColor(priority: string): string {
    return (
        {
            urgent: '#ef4444',
            high: '#f97316',
            medium: '#eab308',
            low: '#3b82f6',
            none: '#9ca3af',
        }[priority] ?? '#9ca3af'
    );
}

const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
</script>

<template>
    <Head title="Calendar" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <h1 class="text-2xl font-bold">Calendar</h1>
                <div class="flex items-center gap-1">
                    <Button
                        size="sm"
                        :variant="view === 'month' ? 'default' : 'outline'"
                        @click="view = 'month'"
                        >Month</Button
                    >
                    <Button
                        size="sm"
                        :variant="view === 'week' ? 'default' : 'outline'"
                        @click="view = 'week'"
                        >Week</Button
                    >
                    <Button
                        size="sm"
                        :variant="view === 'day' ? 'default' : 'outline'"
                        @click="view = 'day'"
                        >Day</Button
                    >
                </div>
            </div>
            <div class="flex items-center gap-2">
                <Button variant="outline" size="sm" @click="today"
                    >Today</Button
                >
                <Button variant="ghost" size="sm" @click="prevMonth"
                    ><ChevronLeft class="h-4 w-4"
                /></Button>
                <span class="min-w-[140px] text-center text-sm font-medium">{{
                    monthName
                }}</span>
                <Button variant="ghost" size="sm" @click="nextMonth"
                    ><ChevronRight class="h-4 w-4"
                /></Button>
            </div>
        </div>

        <div class="grid grid-cols-7 overflow-hidden rounded-lg border">
            <div
                v-for="day in weekDays"
                :key="day"
                class="border-b bg-muted/50 p-2 text-center text-xs font-medium text-muted-foreground"
            >
                {{ day }}
            </div>
            <div
                v-for="(day, index) in calendarDays"
                :key="index"
                :class="[
                    'min-h-[100px] border-r border-b p-2 last:border-r-0',
                    day.isCurrentMonth
                        ? ''
                        : 'bg-muted/30 text-muted-foreground',
                ]"
            >
                <div
                    :class="[
                        'mb-1 text-xs',
                        isToday(day.date)
                            ? 'flex h-6 w-6 items-center justify-center rounded-full bg-primary font-bold text-primary-foreground'
                            : '',
                    ]"
                >
                    {{ day.date.getDate() }}
                </div>
                <div class="space-y-1">
                    <div
                        v-for="todo in day.todos.slice(0, 3)"
                        :key="todo.id"
                        class="cursor-pointer truncate rounded px-1.5 py-0.5 text-xs hover:opacity-80"
                        :style="{
                            backgroundColor:
                                priorityColor(todo.priority) + '20',
                            color: priorityColor(todo.priority),
                        }"
                    >
                        {{ todo.title }}
                    </div>
                    <div
                        v-if="day.todos.length > 3"
                        class="text-xs text-muted-foreground"
                    >
                        +{{ day.todos.length - 3 }} more
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
