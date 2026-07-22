<script setup lang="ts">
import { Archive, CheckCircle2, RotateCcw, Trash2, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { useUi } from '@/composables/useUi';

defineProps<{ selectedIds: string[]; processing: boolean }>();
const emit = defineEmits<{
    action: [action: 'archive' | 'complete' | 'delete' | 'uncomplete'];
    clear: [];
}>();
const { formatNumber, t } = useUi();
</script>

<template>
    <div
        class="mt-4 flex flex-wrap items-center gap-2 rounded-xl border border-orange-500/15 bg-orange-500/[0.06] p-3"
        aria-live="polite"
    >
        <span class="mr-auto text-sm font-medium">
            {{
                t('common.states.selected', {
                    count: formatNumber(selectedIds.length),
                })
            }}
        </span>
        <Button
            variant="outline"
            size="sm"
            :disabled="processing"
            @click="emit('action', 'complete')"
        >
            <Spinner v-if="processing" />
            <CheckCircle2 v-else class="size-4" aria-hidden="true" />
            {{ t('common.actions.complete') }}
        </Button>
        <Button
            variant="outline"
            size="sm"
            :disabled="processing"
            @click="emit('action', 'uncomplete')"
        >
            <RotateCcw class="size-4" aria-hidden="true" />
            {{ t('common.actions.reopen') }}
        </Button>
        <Button
            variant="outline"
            size="sm"
            :disabled="processing"
            @click="emit('action', 'archive')"
        >
            <Archive class="size-4" aria-hidden="true" />
            {{ t('common.actions.archive') }}
        </Button>
        <Button
            variant="destructive"
            size="sm"
            :disabled="processing"
            @click="emit('action', 'delete')"
        >
            <Trash2 class="size-4" aria-hidden="true" />
            {{ t('common.actions.delete') }}
        </Button>
        <Button
            variant="ghost"
            size="icon-sm"
            :aria-label="t('tasks.index.clear_selection')"
            :disabled="processing"
            @click="emit('clear')"
        >
            <X class="size-4" aria-hidden="true" />
        </Button>
    </div>
</template>
