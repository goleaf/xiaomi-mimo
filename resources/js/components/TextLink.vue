<script setup lang="ts">
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';

type Props = {
    href: LinkComponentBaseProps['href'];
    method?: Method;
    as?: string;
};

defineProps<Props>();
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        class="rounded-sm font-medium text-orange-700 underline decoration-orange-500/35 underline-offset-4 transition-colors duration-200 hover:text-orange-800 hover:decoration-orange-500 focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 focus-visible:outline-none dark:text-orange-300 dark:hover:text-orange-200"
    >
        <slot />
    </Link>
</template>
