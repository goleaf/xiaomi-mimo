<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        label: string;
        role?: 'group' | 'tablist';
        vertical?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        role: 'tablist',
        vertical: false,
        class: undefined,
    },
);
</script>

<template>
    <div
        :role="role"
        :aria-label="label"
        :class="
            cn(
                'flex w-fit max-w-full gap-1 overflow-x-auto rounded-xl bg-muted p-1',
                vertical ? 'w-full lg:flex-col' : 'inline-flex',
                props.class,
            )
        "
    >
        <slot />
    </div>
</template>
