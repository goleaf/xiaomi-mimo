<script setup lang="ts">
import { CircleAlert } from '@lucide/vue';

defineProps<{
    message?: string;
}>();
</script>

<template>
    <p
        v-if="message"
        class="flex items-start gap-1.5 text-sm font-medium text-destructive"
        role="alert"
        aria-live="polite"
        aria-atomic="true"
    >
        <CircleAlert class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
        <span>{{ message }}</span>
    </p>
</template>
