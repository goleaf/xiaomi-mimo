<script setup lang="ts">
import { TriangleAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import WorkspaceDialogContent from '@/components/shared/WorkspaceDialogContent.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = withDefaults(
    defineProps<{
        open: boolean;
        title: string;
        description: string;
        confirmLabel: string;
        cancelLabel: string;
        processing?: boolean;
        destructive?: boolean;
        confirmationText?: string;
        confirmationLabel?: string;
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

const confirmationValue = ref('');
const confirmationMatches = computed(
    () =>
        !props.confirmationText ||
        confirmationValue.value === props.confirmationText,
);

watch(
    () => props.open,
    (open) => {
        if (open) {
            confirmationValue.value = '';
        }
    },
);

function confirm(): void {
    if (confirmationMatches.value) {
        emit('confirm');
    }
}
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
                <div v-if="confirmationText" class="space-y-2">
                    <Label for="workspace-confirmation-text">
                        {{ confirmationLabel ?? confirmationText }}
                    </Label>
                    <Input
                        id="workspace-confirmation-text"
                        v-model="confirmationValue"
                        :disabled="processing"
                        :autocomplete="'off'"
                    />
                </div>
                <DialogFooter
                    class="gap-2 border-t border-border/70 pt-5 sm:gap-2"
                >
                    <Button
                        type="button"
                        variant="outline"
                        size="lg"
                        :disabled="processing"
                        @click="emit('update:open', false)"
                    >
                        {{ cancelLabel }}
                    </Button>
                    <Button
                        type="button"
                        :variant="destructive ? 'destructive' : 'default'"
                        size="lg"
                        :disabled="processing || !confirmationMatches"
                        @click="confirm"
                    >
                        <Spinner v-if="processing" />
                        {{ confirmLabel }}
                    </Button>
                </DialogFooter>
            </div>
        </WorkspaceDialogContent>
    </Dialog>
</template>
