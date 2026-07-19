<script setup lang="ts">
import { X } from '@lucide/vue';
import { computed } from 'vue';
import {
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = withDefaults(
    defineProps<{
        title: string;
        description?: string;
        closeLabel: string;
        maxWidthClass?: string;
        accent?: 'orange' | 'red';
    }>(),
    {
        description: undefined,
        maxWidthClass: 'sm:max-w-lg',
        accent: 'orange',
    },
);

const accentClasses = computed(() =>
    props.accent === 'red'
        ? {
              rail: 'bg-destructive',
              ornament: 'border-destructive/20 bg-destructive/[0.05]',
              focus: 'focus-visible:ring-destructive',
          }
        : {
              rail: 'bg-orange-500',
              ornament: 'border-orange-500/20 bg-orange-500/[0.05]',
              focus: 'focus-visible:ring-orange-500',
          },
);
</script>

<template>
    <DialogContent
        :show-close-button="false"
        :class="[
            'flex max-h-[calc(100svh-1.5rem)] w-[calc(100vw-1.5rem)] flex-col gap-0 overflow-hidden rounded-[1.75rem] border-border/80 p-0 shadow-[0_32px_90px_-45px_rgba(15,23,42,0.75)]',
            maxWidthClass,
        ]"
    >
        <div
            class="relative shrink-0 overflow-hidden border-b border-border/70 bg-muted/30 px-6 py-6 sm:px-8"
        >
            <span
                class="absolute inset-y-0 left-0 w-1.5"
                :class="accentClasses.rail"
                aria-hidden="true"
            />
            <span
                class="absolute -right-9 -bottom-16 size-36 rounded-full border-[18px]"
                :class="accentClasses.ornament"
                aria-hidden="true"
            />
            <DialogHeader class="relative pr-10 text-left">
                <DialogTitle class="text-xl tracking-tight">
                    {{ title }}
                </DialogTitle>
                <DialogDescription
                    v-if="description"
                    class="max-w-md leading-6"
                >
                    {{ description }}
                </DialogDescription>
            </DialogHeader>
            <DialogClose
                class="absolute top-4.5 right-4.5 flex size-11 cursor-pointer items-center justify-center rounded-xl text-muted-foreground transition-colors hover:bg-background hover:text-foreground focus-visible:ring-2 focus-visible:outline-none"
                :class="accentClasses.focus"
            >
                <X class="size-4.5" aria-hidden="true" />
                <span class="sr-only">{{ closeLabel }}</span>
            </DialogClose>
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <slot />
        </div>
    </DialogContent>
</template>
