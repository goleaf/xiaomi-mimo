<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    CalendarDays,
    CheckCircle2,
    ChevronLeft,
    ChevronRight,
    Clock3,
    ListChecks,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import WorkspaceSegmentedButton from '@/components/shared/WorkspaceSegmentedButton.vue';
import WorkspaceSegmentedControl from '@/components/shared/WorkspaceSegmentedControl.vue';
import { Button } from '@/components/ui/button';
import { useWorkspaceUi } from '@/composables/useWorkspaceUi';
import { show as todoShow } from '@/routes/todos';
import type { Project, Todo, TodoPriority } from '@/types/models';

type CalendarView = 'month' | 'week' | 'agenda';
type CalendarProject = Pick<Project, 'id' | 'name' | 'color'>;
type CalendarTodo = Pick<
    Todo,
    | 'id'
    | 'title'
    | 'status'
    | 'priority'
    | 'due_date'
    | 'is_completed'
    | 'status_definition'
    | 'priority_definition'
> & {
    project: CalendarProject | null;
};

const props = defineProps<{ todos: CalendarTodo[] }>();
const { copy, formatDate, formatNumber } = useWorkspaceUi();
const currentDate = ref(new Date());
const view = ref<CalendarView>('month');
const viewOptions: CalendarView[] = ['month', 'week', 'agenda'];

const tasksByDate = computed(() => {
    const grouped = new Map<string, CalendarTodo[]>();

    props.todos.forEach((todo) => {
        if (!todo.due_date) {
            return;
        }

        grouped.set(todo.due_date, [
            ...(grouped.get(todo.due_date) ?? []),
            todo,
        ]);
    });

    return grouped;
});

const currentPeriodLabel = computed(() => {
    if (view.value !== 'week') {
        return formatDate(currentDate.value, {
            month: 'long',
            year: 'numeric',
        });
    }

    const start = startOfWeek(currentDate.value);
    const end = new Date(start);
    end.setDate(end.getDate() + 6);

    return `${formatDate(start, { month: 'short', day: 'numeric' })} — ${formatDate(end, { month: 'short', day: 'numeric', year: 'numeric' })}`;
});

const calendarDays = computed(() => {
    const days: Array<{
        date: Date;
        dateKey: string;
        isCurrentMonth: boolean;
        todos: CalendarTodo[];
    }> = [];
    const year = currentDate.value.getFullYear();
    const month = currentDate.value.getMonth();
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    for (let offset = firstDay - 1; offset >= 0; offset -= 1) {
        const date = new Date(year, month, -offset);
        const key = toDateKey(date);
        days.push({
            date,
            dateKey: key,
            isCurrentMonth: false,
            todos: tasksByDate.value.get(key) ?? [],
        });
    }

    for (let day = 1; day <= daysInMonth; day += 1) {
        const date = new Date(year, month, day);
        const key = toDateKey(date);
        days.push({
            date,
            dateKey: key,
            isCurrentMonth: true,
            todos: tasksByDate.value.get(key) ?? [],
        });
    }

    const remaining = 42 - days.length;

    for (let day = 1; day <= remaining; day += 1) {
        const date = new Date(year, month + 1, day);
        const key = toDateKey(date);
        days.push({
            date,
            dateKey: key,
            isCurrentMonth: false,
            todos: tasksByDate.value.get(key) ?? [],
        });
    }

    return days;
});

const weekDays = computed(() => {
    const start = startOfWeek(currentDate.value);

    return Array.from({ length: 7 }, (_, index) => {
        const date = new Date(start);
        date.setDate(date.getDate() + index);
        const key = toDateKey(date);

        return {
            date,
            dateKey: key,
            todos: tasksByDate.value.get(key) ?? [],
        };
    });
});

const agendaGroups = computed(() => {
    const sorted = props.todos
        .filter((todo) => todo.due_date)
        .slice()
        .sort((first, second) =>
            (first.due_date ?? '').localeCompare(second.due_date ?? ''),
        );
    const grouped = new Map<string, CalendarTodo[]>();

    sorted.forEach((todo) => {
        const key = todo.due_date ?? '';
        grouped.set(key, [...(grouped.get(key) ?? []), todo]);
    });

    return Array.from(grouped.entries()).map(([dateKey, todos]) => ({
        dateKey,
        date: parseDateKey(dateKey),
        todos,
    }));
});

const todayTasks = computed(
    () => tasksByDate.value.get(toDateKey(new Date())) ?? [],
);
const overdueTasks = computed(
    () =>
        props.todos.filter(
            (todo) =>
                todo.due_date &&
                todo.due_date < toDateKey(new Date()) &&
                !todo.is_completed,
        ).length,
);
const upcomingTasks = computed(() =>
    props.todos
        .filter(
            (todo) =>
                todo.due_date &&
                todo.due_date >= toDateKey(new Date()) &&
                !todo.is_completed,
        )
        .slice()
        .sort((first, second) =>
            (first.due_date ?? '').localeCompare(second.due_date ?? ''),
        )
        .slice(0, 6),
);

function startOfWeek(date: Date): Date {
    const start = new Date(date);
    start.setHours(12, 0, 0, 0);
    start.setDate(start.getDate() - start.getDay());

    return start;
}

function toDateKey(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

function parseDateKey(dateKey: string): Date {
    return new Date(`${dateKey}T12:00:00`);
}

function isToday(dateKey: string): boolean {
    return dateKey === toDateKey(new Date());
}

function previousPeriod(): void {
    const date = new Date(currentDate.value);

    if (view.value === 'week') {
        date.setDate(date.getDate() - 7);
    } else {
        date.setMonth(date.getMonth() - 1);
    }

    currentDate.value = date;
}

function nextPeriod(): void {
    const date = new Date(currentDate.value);

    if (view.value === 'week') {
        date.setDate(date.getDate() + 7);
    } else {
        date.setMonth(date.getMonth() + 1);
    }

    currentDate.value = date;
}

function goToday(): void {
    currentDate.value = new Date();
}

function priorityDot(priority: TodoPriority): string {
    return (
        {
            urgent: 'bg-red-500',
            high: 'bg-orange-500',
            medium: 'bg-amber-500',
            low: 'bg-sky-500',
            none: 'bg-slate-400',
        }[priority] ?? 'bg-slate-400'
    );
}

function prioritySurface(priority: TodoPriority): string {
    return (
        {
            urgent: 'border-red-500/20 bg-red-500/[0.07] text-red-800 dark:text-red-200',
            high: 'border-orange-500/20 bg-orange-500/[0.07] text-orange-800 dark:text-orange-200',
            medium: 'border-amber-500/20 bg-amber-500/[0.07] text-amber-800 dark:text-amber-200',
            low: 'border-sky-500/20 bg-sky-500/[0.07] text-sky-800 dark:text-sky-200',
            none: 'border-border bg-muted/60 text-muted-foreground',
        }[priority] ?? 'border-border bg-muted/60 text-muted-foreground'
    );
}

function priorityLabel(todo: CalendarTodo): string {
    return todo.priority_definition?.name ?? todo.priority;
}

function statusLabel(todo: CalendarTodo): string {
    return todo.status_definition?.name ?? todo.status;
}
</script>

<template>
    <Head :title="copy.calendar.title" />

    <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
        <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
            <WorkspacePageHeader
                :eyebrow="copy.common.workspace_intelligence"
                :title="copy.calendar.title"
                :description="copy.calendar.description"
            >
                <template #metrics>
                    <WorkspaceMetric
                        :label="copy.calendar.scheduled"
                        :value="formatNumber(todos.length)"
                        :icon="CalendarDays"
                        tone="orange"
                    />
                    <WorkspaceMetric
                        :label="copy.calendar.due_today"
                        :value="formatNumber(todayTasks.length)"
                        :icon="Clock3"
                        tone="blue"
                    />
                    <WorkspaceMetric
                        :label="copy.calendar.overdue"
                        :value="formatNumber(overdueTasks)"
                        :icon="AlertCircle"
                        tone="slate"
                    />
                </template>
            </WorkspacePageHeader>

            <section
                class="rounded-[1.5rem] border border-border/80 bg-card p-3 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:p-4"
            >
                <div
                    class="flex flex-col gap-4 border-b border-border/70 px-1 pb-4 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <WorkspaceSegmentedControl :label="copy.common.filters">
                            <WorkspaceSegmentedButton
                                v-for="option in viewOptions"
                                :key="option"
                                role="tab"
                                :aria-selected="view === option"
                                :active="view === option"
                                @click="view = option"
                            >
                                {{ copy.calendar[option] }}
                            </WorkspaceSegmentedButton>
                        </WorkspaceSegmentedControl>
                        <Button variant="outline" size="lg" @click="goToday">
                            {{ copy.calendar.go_today }}
                        </Button>
                    </div>

                    <div
                        v-if="view !== 'agenda'"
                        class="flex items-center justify-between gap-2 sm:justify-end"
                    >
                        <Button
                            variant="ghost"
                            size="icon-lg"
                            class="cursor-pointer rounded-xl"
                            :aria-label="copy.calendar.previous_period"
                            @click="previousPeriod"
                        >
                            <ChevronLeft class="size-5" aria-hidden="true" />
                        </Button>
                        <h2
                            class="min-w-44 text-center text-sm font-semibold capitalize sm:min-w-56 sm:text-base"
                        >
                            {{ currentPeriodLabel }}
                        </h2>
                        <Button
                            variant="ghost"
                            size="icon-lg"
                            class="cursor-pointer rounded-xl"
                            :aria-label="copy.calendar.next_period"
                            @click="nextPeriod"
                        >
                            <ChevronRight class="size-5" aria-hidden="true" />
                        </Button>
                    </div>
                    <h2 v-else class="text-sm font-semibold sm:text-base">
                        {{ copy.calendar.agenda }}
                    </h2>
                </div>

                <div
                    :class="[
                        'mt-4 grid gap-4',
                        view === 'agenda'
                            ? 'grid-cols-1'
                            : 'xl:grid-cols-[minmax(0,1fr)_19rem]',
                    ]"
                >
                    <div class="min-w-0">
                        <div
                            v-if="view === 'month'"
                            class="overflow-hidden rounded-2xl border border-border/80"
                        >
                            <div
                                class="grid grid-cols-7 border-b border-border/80 bg-muted/45"
                            >
                                <div
                                    v-for="weekday in copy.calendar.weekdays"
                                    :key="weekday"
                                    class="px-1 py-3 text-center text-[0.65rem] font-semibold tracking-[0.1em] text-muted-foreground uppercase sm:text-xs"
                                >
                                    {{ weekday }}
                                </div>
                            </div>

                            <div class="grid grid-cols-7 gap-px bg-border/80">
                                <div
                                    v-for="day in calendarDays"
                                    :key="day.dateKey"
                                    :class="[
                                        'min-h-20 bg-card p-1.5 sm:min-h-28 sm:p-2 lg:min-h-32',
                                        day.isCurrentMonth
                                            ? ''
                                            : 'bg-muted/30 text-muted-foreground',
                                    ]"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span
                                            :class="[
                                                'flex size-7 items-center justify-center rounded-full text-xs font-medium tabular-nums',
                                                isToday(day.dateKey)
                                                    ? 'bg-orange-500 font-semibold text-white shadow-sm'
                                                    : '',
                                            ]"
                                        >
                                            {{ day.date.getDate() }}
                                        </span>
                                        <span
                                            v-if="day.todos.length"
                                            class="text-[0.62rem] font-semibold text-muted-foreground tabular-nums sm:hidden"
                                        >
                                            {{ day.todos.length }}
                                        </span>
                                    </div>

                                    <div
                                        class="mt-1.5 flex flex-wrap gap-1 sm:block sm:space-y-1"
                                    >
                                        <Link
                                            v-for="todo in day.todos.slice(
                                                0,
                                                3,
                                            )"
                                            :key="todo.id"
                                            :href="todoShow(todo)"
                                            prefetch
                                            :class="[
                                                'group/task block cursor-pointer rounded-md border transition-colors focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none',
                                                prioritySurface(todo.priority),
                                                'size-2.5 sm:h-auto sm:w-full sm:px-1.5 sm:py-1',
                                            ]"
                                            :aria-label="todo.title"
                                        >
                                            <span
                                                class="hidden truncate text-[0.68rem] font-medium sm:block"
                                            >
                                                {{ todo.title }}
                                            </span>
                                        </Link>
                                        <p
                                            v-if="day.todos.length > 3"
                                            class="hidden px-1 text-[0.68rem] font-medium text-muted-foreground sm:block"
                                        >
                                            +{{ day.todos.length - 3 }}
                                            {{ copy.calendar.more }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="view === 'week'"
                            class="grid gap-3 md:grid-cols-2 xl:grid-cols-7"
                        >
                            <section
                                v-for="day in weekDays"
                                :key="day.dateKey"
                                :class="[
                                    'min-h-56 rounded-2xl border p-3',
                                    isToday(day.dateKey)
                                        ? 'border-orange-500/40 bg-orange-500/[0.035]'
                                        : 'border-border/80 bg-background',
                                ]"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <div>
                                        <p
                                            class="text-[0.65rem] font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                        >
                                            {{
                                                formatDate(day.date, {
                                                    weekday: 'short',
                                                })
                                            }}
                                        </p>
                                        <p
                                            class="mt-1 text-xl font-semibold tabular-nums"
                                        >
                                            {{ day.date.getDate() }}
                                        </p>
                                    </div>
                                    <span
                                        class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                    >
                                        {{ day.todos.length }}
                                    </span>
                                </div>

                                <div class="mt-4 space-y-2">
                                    <Link
                                        v-for="todo in day.todos"
                                        :key="todo.id"
                                        :href="todoShow(todo)"
                                        prefetch
                                        :class="[
                                            'block cursor-pointer rounded-xl border p-2.5 transition-colors focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none',
                                            prioritySurface(todo.priority),
                                        ]"
                                    >
                                        <p
                                            class="text-xs leading-5 font-semibold"
                                        >
                                            {{ todo.title }}
                                        </p>
                                        <p
                                            class="mt-1 text-[0.65rem] opacity-75"
                                        >
                                            {{ statusLabel(todo) }}
                                        </p>
                                    </Link>
                                    <p
                                        v-if="day.todos.length === 0"
                                        class="py-6 text-center text-xs leading-5 text-muted-foreground"
                                    >
                                        {{ copy.calendar.no_tasks }}
                                    </p>
                                </div>
                            </section>
                        </div>

                        <div v-else class="space-y-7 px-1 py-2 sm:px-3">
                            <section
                                v-for="group in agendaGroups"
                                :key="group.dateKey"
                                class="grid gap-3 md:grid-cols-[10rem_minmax(0,1fr)]"
                            >
                                <div class="pt-1">
                                    <p
                                        class="text-xs font-semibold tracking-[0.12em] text-muted-foreground uppercase"
                                    >
                                        {{
                                            formatDate(group.date, {
                                                weekday: 'long',
                                            })
                                        }}
                                    </p>
                                    <p
                                        class="mt-1 text-sm font-semibold capitalize"
                                    >
                                        {{
                                            formatDate(group.date, {
                                                month: 'long',
                                                day: 'numeric',
                                            })
                                        }}
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <Link
                                        v-for="todo in group.todos"
                                        :key="todo.id"
                                        :href="todoShow(todo)"
                                        prefetch
                                        class="group flex cursor-pointer items-center gap-4 rounded-2xl border border-border/80 bg-background p-4 transition-colors hover:border-orange-500/30 hover:bg-orange-500/[0.025] focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none"
                                    >
                                        <span
                                            :class="[
                                                'size-2.5 shrink-0 rounded-full',
                                                priorityDot(todo.priority),
                                            ]"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p
                                                class="truncate text-sm font-semibold group-hover:text-orange-700 dark:group-hover:text-orange-300"
                                            >
                                                {{ todo.title }}
                                            </p>
                                            <p
                                                class="mt-1 text-xs text-muted-foreground"
                                            >
                                                {{ priorityLabel(todo) }}
                                                ·
                                                {{ statusLabel(todo) }}
                                            </p>
                                        </div>
                                        <CheckCircle2
                                            v-if="todo.is_completed"
                                            class="size-4 shrink-0 text-emerald-600"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                </div>
                            </section>

                            <div
                                v-if="agendaGroups.length === 0"
                                class="flex min-h-72 flex-col items-center justify-center text-center"
                            >
                                <ListChecks
                                    class="size-8 text-muted-foreground"
                                    aria-hidden="true"
                                />
                                <p class="mt-4 text-sm text-muted-foreground">
                                    {{ copy.calendar.no_upcoming }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <aside
                        v-if="view !== 'agenda'"
                        class="rounded-2xl border border-border/80 bg-muted/20 p-4"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"
                            >
                                <Clock3 class="size-4.5" aria-hidden="true" />
                            </div>
                            <div>
                                <h3 class="text-sm font-semibold">
                                    {{ copy.calendar.next_up }}
                                </h3>
                                <p
                                    class="mt-1 text-xs leading-5 text-muted-foreground"
                                >
                                    {{ copy.calendar.next_up_description }}
                                </p>
                            </div>
                        </div>

                        <div v-if="upcomingTasks.length" class="mt-5 space-y-2">
                            <Link
                                v-for="todo in upcomingTasks"
                                :key="todo.id"
                                :href="todoShow(todo)"
                                prefetch
                                class="group flex cursor-pointer gap-3 rounded-xl border border-transparent p-2.5 transition-colors hover:border-border hover:bg-card focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none"
                            >
                                <span
                                    :class="[
                                        'mt-1.5 size-2 shrink-0 rounded-full',
                                        priorityDot(todo.priority),
                                    ]"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="text-xs leading-5 font-semibold group-hover:text-orange-700 dark:group-hover:text-orange-300"
                                    >
                                        {{ todo.title }}
                                    </p>
                                    <p
                                        class="mt-0.5 text-[0.68rem] text-muted-foreground"
                                    >
                                        {{
                                            formatDate(
                                                parseDateKey(
                                                    todo.due_date ?? '',
                                                ),
                                                {
                                                    month: 'short',
                                                    day: 'numeric',
                                                },
                                            )
                                        }}
                                    </p>
                                </div>
                            </Link>
                        </div>
                        <p
                            v-else
                            class="mt-6 rounded-xl border border-dashed border-border p-4 text-center text-xs text-muted-foreground"
                        >
                            {{ copy.calendar.no_upcoming }}
                        </p>
                    </aside>
                </div>
            </section>
        </div>
    </main>
</template>
