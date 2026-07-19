<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { useToast } from '@/composables/useToast';
import { Button } from '@/components/ui/button';
import { CheckCircle2, Archive, Trash2, X } from '@lucide/vue';

const props = defineProps<{
    selectedIds: string[];
    workspaceId: string;
}>();
const emit = defineEmits<{ clear: [] }>();
const toast = useToast();

function bulkAction(action: string) {
    router.post(route('todos.bulk', props.workspaceId), {
        ids: props.selectedIds,
        action,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(`${props.selectedIds.length} task(s) ${action === 'delete' ? 'deleted' : action + 'd'}`);
            emit('clear');
        },
    });
}
</script>

<template>
    <div class="flex items-center gap-3 rounded-lg border bg-muted/50 p-3">
        <span class="text-sm font-medium">{{ selectedIds.length }} selected</span>
        <Button variant="outline" size="sm" @click="bulkAction('complete')">
            <CheckCircle2 class="mr-1 h-3 w-3" />Complete
        </Button>
        <Button variant="outline" size="sm" @click="bulkAction('archive')">
            <Archive class="mr-1 h-3 w-3" />Archive
        </Button>
        <Button variant="destructive" size="sm" @click="bulkAction('delete')">
            <Trash2 class="mr-1 h-3 w-3" />Delete
        </Button>
        <Button variant="ghost" size="sm" @click="emit('clear')">
            <X class="h-3 w-3" />
        </Button>
    </div>
</template>
