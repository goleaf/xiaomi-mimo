<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useUi } from '@/composables/useUi';

const props = defineProps<{
    data: Array<{ date: string; completed: number; created: number }>;
}>();
const { formatDate: formatLocalizedDate, formatNumber, t } = useUi();

const maxVal = computed(() =>
    Math.max(...props.data.map((d) => Math.max(d.completed, d.created)), 1),
);

function barHeight(value: number): string {
    return `${Math.max((value / maxVal.value) * 100, 4)}px`;
}

function formatDate(dateStr: string): string {
    return formatLocalizedDate(dateStr, { weekday: 'short' });
}

function formatFullDate(dateStr: string): string {
    return formatLocalizedDate(dateStr, {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
}
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader class="border-b border-border/70">
            <CardTitle class="text-base">
                {{ t('dashboard.weekly_overview') }}
            </CardTitle>
        </CardHeader>
        <CardContent class="pt-4">
            <figure>
                <figcaption class="sr-only">
                    {{ t('dashboard.weekly_overview') }}
                </figcaption>
                <div
                    class="grid grid-cols-7 items-end gap-1.5 sm:gap-3"
                    role="list"
                >
                    <div
                        v-for="day in data"
                        :key="day.date"
                        class="flex min-w-0 flex-col items-center gap-1.5"
                        role="listitem"
                    >
                        <span class="sr-only">
                            {{
                                t('dashboard.weekly_day_summary', {
                                    date: formatFullDate(day.date),
                                    completed: formatNumber(day.completed),
                                    created: formatNumber(day.created),
                                })
                            }}
                        </span>
                        <div
                            class="flex h-32 items-end justify-center gap-1 sm:gap-2"
                            aria-hidden="true"
                        >
                            <div class="flex h-full flex-col justify-end gap-1">
                                <span
                                    class="text-center text-[10px] font-medium text-muted-foreground tabular-nums"
                                >
                                    {{ formatNumber(day.completed) }}
                                </span>
                                <div
                                    class="w-2.5 rounded-t bg-orange-500/85 transition-[height] duration-300 motion-reduce:transition-none sm:w-4"
                                    :style="{
                                        height: barHeight(day.completed),
                                    }"
                                />
                            </div>
                            <div class="flex h-full flex-col justify-end gap-1">
                                <span
                                    class="text-center text-[10px] font-medium text-muted-foreground tabular-nums"
                                >
                                    {{ formatNumber(day.created) }}
                                </span>
                                <div
                                    class="w-2.5 rounded-t bg-sky-500/70 transition-[height] duration-300 motion-reduce:transition-none sm:w-4"
                                    :style="{ height: barHeight(day.created) }"
                                />
                            </div>
                        </div>
                        <div
                            class="truncate text-[10px] text-muted-foreground"
                            aria-hidden="true"
                        >
                            {{ formatDate(day.date) }}
                        </div>
                    </div>
                </div>
                <div
                    class="mt-4 flex flex-wrap items-center gap-4 text-xs text-muted-foreground"
                >
                    <span class="flex items-center gap-1"
                        ><span class="size-2 rounded bg-orange-500/85" />
                        {{ t('tasks.stats.completed') }}</span
                    >
                    <span class="flex items-center gap-1"
                        ><span class="size-2 rounded bg-sky-500/70" />
                        {{ t('dashboard.created') }}</span
                    >
                </div>
            </figure>
        </CardContent>
    </Card>
</template>
