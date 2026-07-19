<script setup lang="ts">
import { computed } from 'vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const props = defineProps<{
    data: Array<{ date: string; completed: number; created: number }>;
}>();

const maxVal = computed(() => Math.max(...props.data.map(d => Math.max(d.completed, d.created)), 1));

function barHeight(value: number): string {
    return `${Math.max((value / maxVal.value) * 100, 4)}px`;
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('en-US', { weekday: 'short' });
}
</script>

<template>
    <Card>
        <CardHeader><CardTitle class="text-base">Weekly Productivity</CardTitle></CardHeader>
        <CardContent>
            <div class="flex items-end gap-3 h-32">
                <div v-for="day in data" :key="day.date" class="flex-1 flex flex-col items-center gap-1">
                    <div class="flex gap-1 items-end h-24">
                        <div class="w-3 bg-primary/80 rounded-t transition-all" :style="{ height: barHeight(day.completed) }" />
                        <div class="w-3 bg-primary/30 rounded-t transition-all" :style="{ height: barHeight(day.created) }" />
                    </div>
                    <div class="text-[10px] text-muted-foreground">{{ formatDate(day.date) }}</div>
                </div>
            </div>
            <div class="flex items-center gap-4 mt-3 text-xs text-muted-foreground">
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded bg-primary/80" /> Completed</span>
                <span class="flex items-center gap-1"><span class="h-2 w-2 rounded bg-primary/30" /> Created</span>
            </div>
        </CardContent>
    </Card>
</template>
