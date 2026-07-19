<script setup lang="ts">
import type { Component } from 'vue';

withDefaults(
    defineProps<{
        label: string;
        value: string | number;
        icon: Component;
        tone?: 'orange' | 'blue' | 'emerald' | 'slate';
    }>(),
    {
        tone: 'slate',
    },
);

const toneClasses = {
    orange: 'bg-orange-500/12 text-orange-700 dark:text-orange-300',
    blue: 'bg-sky-500/12 text-sky-700 dark:text-sky-300',
    emerald: 'bg-emerald-500/12 text-emerald-700 dark:text-emerald-300',
    slate: 'bg-foreground/6 text-foreground/70',
};
</script>

<template>
    <div class="flex min-w-0 items-center gap-3 px-5 py-4">
        <div
            :class="[
                'flex size-10 shrink-0 items-center justify-center rounded-2xl',
                toneClasses[tone],
            ]"
        >
            <component :is="icon" class="size-4.5" aria-hidden="true" />
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-muted-foreground">
                {{ label }}
            </p>
            <p class="mt-0.5 text-xl font-semibold tracking-tight">
                {{ value }}
            </p>
        </div>
    </div>
</template>
