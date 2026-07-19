<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import type { Todo } from '@/types/models';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChevronLeft, ChevronRight } from '@lucide/vue';

const props = defineProps<{ todos: Todo[] }>();

const currentDate = ref(new Date());
const view = ref<'month' | 'week' | 'agenda'>('month');

const monthName = computed(() =>
    currentDate.value.toLocaleDateString('en-US', { month: 'long', year: 'numeric' })
);

const daysInMonth = computed(() =>
    new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 0).getDate()
);

const firstDayOfMonth = computed(() =>
    new Date(currentDate.value.getFullYear(), currentDate.value.getMonth(), 1).getDay()
);

const calendarDays = computed(() => {
    const days: Array<{ date: Date; isCurrentMonth: boolean; todos: Todo[] }> = [];
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();

    for (let i = firstDayOfMonth.value - 1; i >= 0; i--) {
        days.push({ date: new Date(year, month, -i), isCurrentMonth: false, todos: [] });
    }

    for (let i = 1; i <= daysInMonth.value; i++) {
        const d = new Date(year, month, i);
        const dateStr = d.toISOString().split('T')[0];
        days.push({ date: d, isCurrentMonth: true, todos: props.todos.filter((t) => t.due_date === dateStr) });
    }

    const remaining = 42 - days.length;
    for (let i = 1; i <= remaining; i++) {
        days.push({ date: new Date(year, month + 1, i), isCurrentMonth: false, todos: [] });
    }

    return days;
});

const weekTodos = computed(() => {
    const start = new Date(currentDate.value);
    start.setDate(start.getDate() - start.getDay());
    const end = new Date(start);
    end.setDate(end.getDate() + 6);
    const startStr = start.toISOString().split('T')[0];
    const endStr = end.toISOString().split('T')[0];
    return props.todos.filter((t) => t.due_date && t.due_date >= startStr && t.due_date <= endStr);
});

const agendaTodos = computed(() => {
    const today = new Date().toISOString().split('T')[0];
    return props.todos
        .filter((t) => t.due_date && t.due_date >= today)
        .sort((a, b) => (a.due_date ?? '').localeCompare(b.due_date ?? ''));
});

function prevMonth() { currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1); }
function nextMonth() { currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1); }
function today() { currentDate.value = new Date(); }
function isToday(date: Date): boolean { return date.toDateString() === new Date().toDateString(); }
function priorityColor(p: string): string { return { urgent: '#ef4444', high: '#f97316', medium: '#eab308', low: '#3b82f6', none: '#9ca3af' }[p] ?? '#9ca3af'; }
function formatDate(d: string | null): string { return d ? new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : ''; }
const weekDays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
</script>

<template>
    <Head title="Calendar" />
    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Calendar</h1>
            <div class="flex items-center gap-2">
                <div class="flex border rounded-md">
                    <button :class="['px-3 py-1 text-xs', view === 'month' ? 'bg-muted' : '']" @click="view = 'month'">Month</button>
                    <button :class="['px-3 py-1 text-xs', view === 'week' ? 'bg-muted' : '']" @click="view = 'week'">Week</button>
                    <button :class="['px-3 py-1 text-xs', view === 'agenda' ? 'bg-muted' : '']" @click="view = 'agenda'">Agenda</button>
                </div>
                <Button variant="outline" size="sm" @click="today">Today</Button>
                <Button variant="ghost" size="sm" @click="prevMonth"><ChevronLeft class="h-4 w-4" /></Button>
                <span class="text-sm font-medium min-w-[140px] text-center">{{ monthName }}</span>
                <Button variant="ghost" size="sm" @click="nextMonth"><ChevronRight class="h-4 w-4" /></Button>
            </div>
        </div>

        <!-- Month View -->
        <div v-if="view === 'month'" class="grid grid-cols-7 border rounded-lg overflow-hidden">
            <div v-for="day in weekDays" :key="day" class="p-2 text-center text-xs font-medium text-muted-foreground border-b bg-muted/50">{{ day }}</div>
            <div v-for="(day, index) in calendarDays" :key="index"
                :class="['min-h-[100px] p-2 border-b border-r last:border-r-0', day.isCurrentMonth ? '' : 'bg-muted/30 text-muted-foreground']">
                <div :class="['text-xs mb-1', isToday(day.date) ? 'bg-primary text-primary-foreground rounded-full w-6 h-6 flex items-center justify-center font-bold' : '']">
                    {{ day.date.getDate() }}
                </div>
                <div class="space-y-1">
                    <div v-for="todo in day.todos.slice(0, 3)" :key="todo.id"
                        class="rounded px-1.5 py-0.5 text-xs truncate cursor-pointer hover:opacity-80"
                        :style="{ backgroundColor: priorityColor(todo.priority) + '20', color: priorityColor(todo.priority) }">
                        {{ todo.title }}
                    </div>
                    <div v-if="day.todos.length > 3" class="text-xs text-muted-foreground">+{{ day.todos.length - 3 }} more</div>
                </div>
            </div>
        </div>

        <!-- Week View -->
        <div v-if="view === 'week'" class="space-y-2">
            <div v-for="todo in weekTodos" :key="todo.id" class="flex items-center gap-3 rounded-lg border p-3 hover:bg-muted/50">
                <div class="h-2 w-2 rounded-full shrink-0" :style="{ backgroundColor: priorityColor(todo.priority) }" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium">{{ todo.title }}</p>
                    <p class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }} · {{ todo.project?.name ?? '' }}</p>
                </div>
                <Badge :variant="todo.status === 'completed' ? 'default' : 'outline'">{{ todo.status }}</Badge>
            </div>
            <div v-if="weekTodos.length === 0" class="text-center py-8 text-muted-foreground">No tasks this week</div>
        </div>

        <!-- Agenda View -->
        <div v-if="view === 'agenda'" class="space-y-2">
            <div v-for="todo in agendaTodos" :key="todo.id" class="flex items-center gap-3 rounded-lg border p-3 hover:bg-muted/50">
                <div class="h-2 w-2 rounded-full shrink-0" :style="{ backgroundColor: priorityColor(todo.priority) }" />
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium">{{ todo.title }}</p>
                    <p class="text-xs text-muted-foreground">{{ formatDate(todo.due_date) }} · {{ todo.project?.name ?? '' }}</p>
                </div>
                <Badge :variant="todo.status === 'completed' ? 'default' : 'outline'">{{ todo.status }}</Badge>
            </div>
            <div v-if="agendaTodos.length === 0" class="text-center py-8 text-muted-foreground">No upcoming tasks</div>
        </div>
    </div>
</template>
