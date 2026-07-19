<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

defineOptions({ inheritAttrs: false });

const props = withDefaults(
    defineProps<{
        active: boolean;
        wide?: boolean;
        class?: HTMLAttributes['class'];
    }>(),
    {
        wide: false,
        class: undefined,
    },
);
</script>

<template>
    <button
        v-bind="$attrs"
        type="button"
        data-slot="workspace-segmented-button"
        :data-active="active"
        :class="
            cn(
                'flex min-h-10 min-w-max cursor-pointer items-center gap-2 rounded-lg px-3.5 text-sm font-medium transition-all focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none motion-reduce:transition-none',
                wide &&
                    'min-h-11 justify-between gap-5 rounded-xl px-4 focus-visible:ring-offset-2 lg:w-full',
                active
                    ? 'bg-card text-foreground shadow-sm'
                    : cn(
                          'text-muted-foreground hover:text-foreground',
                          wide && 'hover:bg-muted',
                      ),
                props.class,
            )
        "
    >
        <slot />
    </button>
</template>
