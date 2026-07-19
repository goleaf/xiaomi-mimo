<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center overflow-hidden bg-muted/20 p-4 sm:p-6 md:p-10"
    >
        <div
            class="pointer-events-none absolute -top-32 -right-24 size-96 rounded-full border-[72px] border-orange-500/[0.055]"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-40 -left-32 size-[30rem] rounded-full border-[88px] border-orange-500/[0.035]"
            aria-hidden="true"
        />

        <div class="relative w-full max-w-md">
            <div
                class="overflow-hidden rounded-[1.75rem] border border-border/80 bg-card shadow-[0_32px_90px_-54px_rgba(15,23,42,0.65)]"
            >
                <div
                    class="relative flex flex-col items-center gap-4 border-b border-border/70 bg-muted/20 px-6 pt-8 pb-7 sm:px-9"
                >
                    <span
                        class="absolute inset-x-0 top-0 h-1.5 bg-orange-500"
                        aria-hidden="true"
                    />
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-2 rounded-xl font-medium focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-4 focus-visible:outline-none"
                    >
                        <div
                            class="mb-1 flex size-12 items-center justify-center rounded-2xl bg-orange-600 text-white shadow-lg shadow-orange-600/20"
                        >
                            <AppLogoIcon
                                class="size-7 fill-current text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-2 text-center">
                        <h1 class="text-2xl font-semibold tracking-[-0.035em]">
                            {{ title }}
                        </h1>
                        <p
                            class="mx-auto max-w-sm text-center text-sm leading-6 text-muted-foreground"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="px-6 py-7 sm:px-9 sm:py-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
