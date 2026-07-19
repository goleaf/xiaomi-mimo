<script setup lang="ts">
defineProps<{
    eyebrow: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-[1.75rem] border border-border/80 bg-card shadow-[0_24px_70px_-44px_rgba(15,23,42,0.45)]"
    >
        <div
            class="absolute inset-y-0 left-0 w-1.5 bg-orange-500"
            aria-hidden="true"
        />
        <div
            class="absolute -top-24 -right-24 size-64 rounded-full border-[52px] border-orange-500/5"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col gap-6 px-6 py-7 sm:px-8 lg:flex-row lg:items-end lg:justify-between lg:px-10 lg:py-9"
        >
            <div class="max-w-3xl">
                <p
                    class="flex items-center gap-2 text-[0.7rem] font-semibold tracking-[0.22em] text-orange-700 uppercase dark:text-orange-300"
                >
                    <span class="size-1.5 rounded-full bg-orange-500" />
                    {{ eyebrow }}
                </p>
                <h1
                    class="mt-4 text-3xl font-semibold tracking-[-0.04em] text-balance sm:text-4xl"
                >
                    {{ title }}
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    {{ description }}
                </p>
            </div>

            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div
            v-if="$slots.metrics"
            class="relative grid divide-y border-t border-border/70 bg-muted/25 sm:auto-cols-fr sm:grid-flow-col sm:divide-x sm:divide-y-0"
        >
            <slot name="metrics" />
        </div>
    </header>
</template>
