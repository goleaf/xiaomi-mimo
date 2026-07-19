<script setup lang="ts">
import { ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';

withDefaults(
    defineProps<{
        title: string;
        description?: string;
        actionLabel?: string;
        compact?: boolean;
    }>(),
    {
        description: undefined,
        actionLabel: undefined,
        compact: false,
    },
);

const emit = defineEmits<{ action: [] }>();
</script>

<template>
    <div
        class="relative flex flex-col items-center justify-center overflow-hidden px-6 text-center"
        :class="compact ? 'min-h-56 py-10' : 'min-h-80 py-16'"
    >
        <span
            class="absolute -right-12 -bottom-20 size-52 rounded-full border-[28px] border-orange-500/[0.035]"
            aria-hidden="true"
        />
        <div
            class="relative mb-5 flex size-16 items-center justify-center rounded-2xl border border-orange-500/15 bg-orange-500/[0.08] text-orange-700 shadow-sm dark:text-orange-300"
        >
            <slot name="icon">
                <svg
                    class="size-7"
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
        <h3 class="relative text-lg font-semibold tracking-tight">
            {{ title }}
        </h3>
        <p
            v-if="description"
            class="relative mt-2 max-w-md text-sm leading-6 text-muted-foreground"
        >
            {{ description }}
        </p>
        <Button
            v-if="actionLabel"
            class="relative mt-5 min-h-11 cursor-pointer rounded-xl bg-orange-600 text-white hover:bg-orange-700 focus-visible:ring-orange-500"
            @click="emit('action')"
        >
            {{ actionLabel }}
            <ArrowRight class="size-4" aria-hidden="true" />
        </Button>
    </div>
</template>
