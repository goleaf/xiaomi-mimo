<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useUi } from '@/composables/useUi';

const props = defineProps<{
    data: Array<{ date: string; completed: number; created: number }>;
}>();
const { formatDate: formatLocalizedDate, t } = useUi();

const maxVal = computed(() =>
    Math.max(...props.data.map((d) => Math.max(d.completed, d.created)), 1),
);

function barHeight(value: number): string {
    return `${Math.max((value / maxVal.value) * 100, 4)}px`;
}

function formatDate(dateStr: string): string {
    return formatLocalizedDate(dateStr, { weekday: 'short' });
}
</script>

<template>
    <Card>
        <CardHeader
            ><CardTitle class="text-base">{{
                t('dashboard.weekly_productivity')
            }}</CardTitle></CardHeader
        >
        <CardContent>
            <div class="flex h-32 items-end gap-3">
                <div
                    v-for="day in data"
                    :key="day.date"
                    class="flex flex-1 flex-col items-center gap-1"
                >
                    <div class="flex h-24 items-end gap-1">
                        <div
                            class="w-3 rounded-t bg-primary/80 transition-all"
                            :style="{ height: barHeight(day.completed) }"
                        />
                        <div
                            class="w-3 rounded-t bg-primary/30 transition-all"
                            :style="{ height: barHeight(day.created) }"
                        />
                    </div>
                    <div class="text-[10px] text-muted-foreground">
                        {{ formatDate(day.date) }}
                    </div>
                </div>
            </div>
            <div
                class="mt-3 flex items-center gap-4 text-xs text-muted-foreground"
            >
                <span class="flex items-center gap-1"
                    ><span class="h-2 w-2 rounded bg-primary/80" />
                    {{ t('tasks.stats.completed') }}</span
                >
                <span class="flex items-center gap-1"
                    ><span class="h-2 w-2 rounded bg-primary/30" />
                    {{ t('dashboard.created') }}</span
                >
            </div>
        </CardContent>
    </Card>
</template>
