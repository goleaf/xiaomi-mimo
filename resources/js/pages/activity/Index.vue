<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Activity,
    Archive,
    CheckCircle2,
    CirclePlus,
    History,
    PackageOpen,
    PenLine,
    Pin,
    RotateCcw,
    Sparkles,
    Star,
    Trash2,
    UsersRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import type { Component } from 'vue';
import EmptyState from '@/components/shared/EmptyState.vue';
import WorkspaceMetric from '@/components/shared/WorkspaceMetric.vue';
import WorkspacePageHeader from '@/components/shared/WorkspacePageHeader.vue';
import { useWorkspaceUi } from '@/composables/useWorkspaceUi';
import type { ActivityLog } from '@/types/models';

type ActivityFilter = 'all' | 'created' | 'updated' | 'completed';

const props = defineProps<{ activities: { data: ActivityLog[] } }>();
const { copy, formatDate, formatNumber } = useWorkspaceUi();
const activeFilter = ref<ActivityFilter>('all');

const filteredActivities = computed(() => {
    if (activeFilter.value === 'all') {
        return props.activities.data;
    }

    return props.activities.data.filter(
        (activity) => activity.event === activeFilter.value,
    );
});

const contributors = computed(
    () =>
        new Set(
            props.activities.data
                .map((activity) => activity.user?.id)
                .filter(Boolean),
        ).size,
);

const recentChanges = computed(() => {
    const threshold = Date.now() - 7 * 24 * 60 * 60 * 1000;

    return props.activities.data.filter(
        (activity) => new Date(activity.created_at).getTime() >= threshold,
    ).length;
});

const filters = computed(() => [
    {
        value: 'all' as const,
        label: copy.value.activity.filter_all,
        count: props.activities.data.length,
    },
    {
        value: 'created' as const,
        label: copy.value.activity.filter_created,
        count: props.activities.data.filter(
            (activity) => activity.event === 'created',
        ).length,
    },
    {
        value: 'updated' as const,
        label: copy.value.activity.filter_updated,
        count: props.activities.data.filter(
            (activity) => activity.event === 'updated',
        ).length,
    },
    {
        value: 'completed' as const,
        label: copy.value.activity.filter_completed,
        count: props.activities.data.filter(
            (activity) => activity.event === 'completed',
        ).length,
    },
]);

const groupedActivities = computed(() => {
    const groups = new Map<string, ActivityLog[]>();

    filteredActivities.value.forEach((activity) => {
        const key = dateKey(activity.created_at);
        groups.set(key, [...(groups.get(key) ?? []), activity]);
    });

    return Array.from(groups.entries()).map(([key, activities]) => ({
        key,
        label: groupLabel(activities[0]?.created_at ?? ''),
        activities,
    }));
});

function dateKey(value: string | Date): string {
    return formatDate(value, {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    });
}

function groupLabel(value: string): string {
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);

    if (dateKey(value) === dateKey(today)) {
        return copy.value.activity.today;
    }

    if (dateKey(value) === dateKey(yesterday)) {
        return copy.value.activity.yesterday;
    }

    return formatDate(value, {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    });
}

function eventLabel(event: string): string {
    const labels = copy.value.activity as unknown as Record<string, string>;

    return labels[`event_${event}`] ?? copy.value.activity.event_changed;
}

function subjectLabel(activity: ActivityLog): string {
    const subject = activity.subject_type.split('\\').pop()?.toLowerCase();
    const labels = copy.value.activity as unknown as Record<string, string>;

    return labels[`subject_${subject}`] ?? copy.value.activity.subject_item;
}

function subjectDetail(activity: ActivityLog): string | null {
    const title = activity.properties?.title;
    const name = activity.properties?.name;

    if (typeof title === 'string') {
        return title;
    }

    return typeof name === 'string' ? name : null;
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toLocaleUpperCase();
}

function eventIcon(event: string): Component {
    return (
        {
            created: CirclePlus,
            updated: PenLine,
            completed: CheckCircle2,
            uncompleted: RotateCcw,
            deleted: Trash2,
            restored: RotateCcw,
            archived: Archive,
            unarchived: PackageOpen,
            pinned: Pin,
            unpinned: Pin,
            favorited: Star,
            unfavorited: Star,
        }[event] ?? Activity
    );
}

function eventTone(event: string): string {
    return (
        {
            created: 'bg-orange-500/12 text-orange-700 dark:text-orange-300',
            updated: 'bg-sky-500/12 text-sky-700 dark:text-sky-300',
            completed:
                'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300',
            deleted: 'bg-red-500/12 text-red-700 dark:text-red-300',
            archived: 'bg-violet-500/12 text-violet-700 dark:text-violet-300',
        }[event] ?? 'bg-foreground/6 text-foreground/70'
    );
}
</script>

<template>
    <Head :title="copy.activity.title" />

    <main class="min-h-full bg-muted/20 px-4 py-5 sm:p-6 lg:p-8">
        <div class="mx-auto flex max-w-[1480px] flex-col gap-6">
            <WorkspacePageHeader
                :eyebrow="copy.common.workspace_intelligence"
                :title="copy.activity.title"
                :description="copy.activity.description"
            >
                <template #metrics>
                    <WorkspaceMetric
                        :label="copy.activity.total_actions"
                        :value="formatNumber(activities.data.length)"
                        :icon="History"
                        tone="orange"
                    />
                    <WorkspaceMetric
                        :label="copy.activity.contributors"
                        :value="formatNumber(contributors)"
                        :icon="UsersRound"
                        tone="blue"
                    />
                    <WorkspaceMetric
                        :label="copy.activity.recent_changes"
                        :value="formatNumber(recentChanges)"
                        :icon="Sparkles"
                        tone="emerald"
                    />
                </template>
            </WorkspacePageHeader>

            <div
                class="grid min-w-0 grid-cols-1 items-start gap-6 lg:grid-cols-[17rem_minmax(0,1fr)]"
            >
                <aside
                    class="min-w-0 overflow-hidden rounded-[1.5rem] border border-border/80 bg-card p-3 lg:sticky lg:top-6"
                    :aria-label="copy.common.filters"
                >
                    <div
                        class="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-muted p-1 lg:flex-col"
                    >
                        <button
                            v-for="filter in filters"
                            :key="filter.value"
                            type="button"
                            :aria-pressed="activeFilter === filter.value"
                            :class="[
                                'flex min-h-11 min-w-max cursor-pointer items-center justify-between gap-5 rounded-xl px-4 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:outline-none motion-reduce:transition-none lg:w-full',
                                activeFilter === filter.value
                                    ? 'bg-card text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            ]"
                            @click="activeFilter = filter.value"
                        >
                            <span>{{ filter.label }}</span>
                            <span
                                class="text-xs text-muted-foreground tabular-nums opacity-65"
                            >
                                {{ formatNumber(filter.count) }}
                            </span>
                        </button>
                    </div>
                </aside>

                <section
                    class="min-w-0 rounded-[1.5rem] border border-border/80 bg-card px-4 py-5 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)] sm:px-6 sm:py-7"
                    aria-live="polite"
                >
                    <div
                        class="mb-7 flex flex-wrap items-center justify-between gap-3 border-b border-border/70 pb-5"
                    >
                        <p class="text-sm font-medium">
                            {{ copy.activity.showing }}
                            <span class="text-orange-600 dark:text-orange-400">
                                {{ formatNumber(filteredActivities.length) }}
                            </span>
                        </p>
                        <div
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span class="size-2 rounded-full bg-emerald-500" />
                            {{ copy.activity.recent_changes }}
                        </div>
                    </div>

                    <div v-if="groupedActivities.length" class="space-y-9">
                        <section
                            v-for="group in groupedActivities"
                            :key="group.key"
                            class="grid gap-4 md:grid-cols-[8rem_minmax(0,1fr)]"
                        >
                            <h2
                                class="pt-1 text-xs font-semibold tracking-[0.14em] text-muted-foreground uppercase"
                            >
                                {{ group.label }}
                            </h2>

                            <div
                                class="relative space-y-3 before:absolute before:top-5 before:bottom-5 before:left-5 before:w-px before:bg-border"
                            >
                                <article
                                    v-for="activityItem in group.activities"
                                    :key="activityItem.id"
                                    class="group relative grid animate-in grid-cols-[2.5rem_minmax(0,1fr)] gap-3 rounded-2xl border border-transparent p-2 transition-colors duration-200 fade-in slide-in-from-bottom-2 hover:border-border hover:bg-muted/35 motion-reduce:animate-none sm:grid-cols-[2.5rem_minmax(0,1fr)_auto] sm:items-center"
                                >
                                    <div
                                        :class="[
                                            'relative z-10 flex size-10 items-center justify-center rounded-2xl ring-4 ring-card',
                                            eventTone(activityItem.event),
                                        ]"
                                    >
                                        <component
                                            :is="eventIcon(activityItem.event)"
                                            class="size-4.5"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div class="min-w-0 py-0.5">
                                        <p
                                            class="text-sm leading-6 break-words text-foreground/90"
                                        >
                                            <span
                                                class="font-semibold text-foreground"
                                            >
                                                {{
                                                    activityItem.user?.name ??
                                                    copy.common.system
                                                }}
                                            </span>
                                            {{ eventLabel(activityItem.event) }}
                                            <span class="font-medium">
                                                {{ subjectLabel(activityItem) }}
                                            </span>
                                            <span
                                                v-if="
                                                    subjectDetail(activityItem)
                                                "
                                                class="text-muted-foreground"
                                            >
                                                “{{
                                                    subjectDetail(activityItem)
                                                }}”
                                            </span>
                                        </p>
                                        <p
                                            class="mt-0.5 text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    activityItem.created_at,
                                                    {
                                                        hour: '2-digit',
                                                        minute: '2-digit',
                                                    },
                                                )
                                            }}
                                        </p>
                                    </div>

                                    <div
                                        class="col-start-2 flex items-center gap-2 sm:col-start-auto"
                                    >
                                        <span
                                            class="rounded-full border border-border/80 bg-background px-2.5 py-1 text-[0.68rem] font-semibold tracking-wide text-muted-foreground uppercase"
                                        >
                                            {{ eventLabel(activityItem.event) }}
                                        </span>
                                        <span
                                            class="hidden size-7 items-center justify-center rounded-full bg-muted text-[0.65rem] font-semibold text-muted-foreground sm:flex"
                                            :title="
                                                activityItem.user?.name ??
                                                copy.common.system
                                            "
                                        >
                                            {{
                                                initials(
                                                    activityItem.user?.name ??
                                                        copy.common.system,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>

                    <EmptyState
                        v-else
                        :title="copy.activity.empty_title"
                        :description="copy.activity.empty_description"
                    >
                        <template #icon>
                            <History class="size-7" aria-hidden="true" />
                        </template>
                    </EmptyState>
                </section>
            </div>
        </div>
    </main>
</template>
