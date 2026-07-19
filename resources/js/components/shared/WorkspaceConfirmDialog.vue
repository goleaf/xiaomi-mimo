<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter } from '@/components/ui/dialog';

withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description: string;
        confirmLabel: string;
        cancelLabel: string;
        processing?: boolean;
        destructive?: boolean;
    }>(),
    {
        processing: false,
        destructive: true,
    },
);

const emit = defineEmits<{
    'update:open': [open: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <WorkspaceDialogContent
            :title="title"
            :description="description"
            :close-label="cancelLabel"
            :accent="destructive ? 'red' : 'orange'"
            max-width-class="sm:max-w-md"
        >
            <div class="space-y-6 px-6 py-6 sm:px-8">
                <div
                    class="flex size-11 items-center justify-center rounded-2xl border"
                    :class="
                        destructive
                            ? 'border-destructive/15 bg-destructive/10 text-destructive'
                            : 'border-orange-500/15 bg-orange-500/10 text-orange-700 dark:text-orange-300'
                    "
                >
                    <slot name="icon">
                        <TriangleAlert class="size-5" aria-hidden="true" />
                    </slot>
                </div>
                <DialogFooter
                    class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11 cursor-pointer rounded-xl"
                        :disabled="processing"
                        @click="emit('update:open', false)"
                    >
                        {{ cancelLabel }}
                    </Button>
                    <Button
                        type="button"
                        :variant="destructive ? 'destructive' : 'default'"
                        class="min-h-11 cursor-pointer rounded-xl"
                        :class="
                            destructive
                                ? ''
                                : 'bg-orange-600 text-white hover:bg-orange-700 focus-visible:ring-orange-500'
                        "
                        :disabled="processing"
                        @click="emit('confirm')"
                    >
                        {{ confirmLabel }}
                    </Button>
                </DialogFooter>
            </div>
        </WorkspaceDialogContent>
    </Dialog>
</template>
