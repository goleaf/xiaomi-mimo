<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CheckCircle2, Archive, Trash2, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';
import { useUi } from '@/composables/useUi';
import { bulk } from '@/routes/todos';

const props = defineProps<{
    selectedIds: string[];
    workspaceId: string;
}>();
const emit = defineEmits<{ clear: [] }>();
const toast = useToast();
const { formatNumber, t } = useUi();

function bulkAction(action: string) {
    router.post(
        bulk(props.workspaceId).url,
        {
            ids: props.selectedIds,
            action,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    t(`tasks.index.bulk_${action}d`, {
                        count: formatNumber(props.selectedIds.length),
                    }),
                );
                emit('clear');
            },
        },
    );
}
</script>

<template>
    <div class="flex items-center gap-3 rounded-lg border bg-muted/50 p-3">
        <span class="text-sm font-medium">{{
            t('common.states.selected', {
                count: formatNumber(selectedIds.length),
            })
        }}</span>
        <Button variant="outline" size="sm" @click="bulkAction('complete')">
            <CheckCircle2 class="mr-1 h-3 w-3" />{{
                t('common.actions.complete')
            }}
        </Button>
        <Button variant="outline" size="sm" @click="bulkAction('archive')">
            <Archive class="mr-1 h-3 w-3" />{{ t('common.actions.archive') }}
        </Button>
        <Button variant="destructive" size="sm" @click="bulkAction('delete')">
            <Trash2 class="mr-1 h-3 w-3" />{{ t('common.actions.delete') }}
        </Button>
        <Button variant="ghost" size="sm" @click="emit('clear')">
            <X class="h-3 w-3" />
        </Button>
    </div>
</template>
