<script setup lang="ts">
import { Repeat } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

defineProps<{ rule: string | null }>();

function formatRule(rule: string): string {
    if (!rule) {
        return '';
    }

    const map: Record<string, string> = {
        'FREQ=DAILY': 'Daily',
        'FREQ=WEEKLY': 'Weekly',
        'FREQ=MONTHLY': 'Monthly',
        'FREQ=YEARLY': 'Yearly',
        'FREQ=DAILY;INTERVAL=2': 'Every 2 days',
        'FREQ=WEEKLY;INTERVAL=2': 'Every 2 weeks',
        'FREQ=MONTHLY;INTERVAL=2': 'Every 2 months',
    };

    return map[rule] ?? rule.replace('FREQ=', '').split(';')[0].toLowerCase();
}
</script>

<template>
    <Badge v-if="rule" variant="outline" class="gap-1 text-xs">
        <Repeat class="h-3 w-3" />
        {{ formatRule(rule) }}
    </Badge>
</template>
