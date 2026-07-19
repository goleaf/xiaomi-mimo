<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header
        :class="
            variant === 'small'
                ? ''
                : 'relative mb-8 overflow-hidden rounded-2xl border border-border/80 bg-card px-5 py-5 shadow-[0_18px_50px_-42px_rgba(15,23,42,0.55)] sm:px-6'
        "
    >
        <span
            v-if="variant !== 'small'"
            class="absolute inset-y-0 left-0 w-1 bg-orange-500"
            aria-hidden="true"
        />
        <span
            v-if="variant !== 'small'"
            class="absolute -top-12 -right-12 size-32 rounded-full border-[28px] border-orange-500/5"
            aria-hidden="true"
        />
        <div
            :class="[
                'relative',
                variant === 'small'
                    ? ''
                    : 'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between',
            ]"
        >
            <div>
                <h2
                    :class="
                        variant === 'small'
                            ? 'mb-0.5 text-base font-medium'
                            : 'text-xl font-semibold tracking-[-0.025em] sm:text-2xl'
                    "
                >
                    {{ title }}
                </h2>
                <p
                    v-if="description"
                    class="mt-1.5 max-w-2xl text-sm leading-6 text-muted-foreground"
                >
                    {{ description }}
                </p>
            </div>
            <div v-if="$slots.actions" class="flex shrink-0 flex-wrap gap-2">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
