<script setup lang="ts">
import { Repeat } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { useUi } from '@/composables/useUi';

defineProps<{ rule: string | null }>();
const { t } = useUi();

function formatRule(rule: string): string {
    if (!rule) {
        return '';
    }

    const map: Record<string, string> = {
        'FREQ=DAILY': t('tasks.recurring.daily'),
        'FREQ=WEEKLY': t('tasks.recurring.weekly'),
        'FREQ=MONTHLY': t('tasks.recurring.monthly'),
        'FREQ=YEARLY': t('tasks.recurring.yearly'),
        'FREQ=DAILY;INTERVAL=2': t('tasks.recurring.every_2_days'),
        'FREQ=WEEKLY;INTERVAL=2': t('tasks.recurring.every_2_weeks'),
        'FREQ=MONTHLY;INTERVAL=2': t('tasks.recurring.every_2_months'),
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
