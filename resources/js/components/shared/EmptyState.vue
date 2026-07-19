<script setup lang="ts">
import { Button } from '@/components/ui/button';

defineProps<{
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
}>();

const emit = defineEmits<{ action: [] }>();
</script>

<template>
    <div class="flex flex-col items-center justify-center py-16 text-center">
        <div
            class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted"
        >
            <slot name="icon">
                <svg
                    class="h-8 w-8 text-muted-foreground"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                    />
                </svg>
            </slot>
        </div>
        <h3 class="mb-1 text-lg font-semibold">{{ title }}</h3>
        <p class="mb-4 max-w-sm text-sm text-muted-foreground">
            {{ description }}
        </p>
        <Button v-if="actionLabel" @click="emit('action')">{{
            actionLabel
        }}</Button>
    </div>
</template>
