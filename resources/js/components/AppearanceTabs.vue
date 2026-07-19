<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { useAppearance } from '@/composables/useAppearance';
import { useUi } from '@/composables/useUi';

const { appearance, updateAppearance } = useAppearance();
const { t } = useUi();

const tabs = [
    { value: 'light', Icon: Sun, labelKey: 'common.appearance.light' },
    { value: 'dark', Icon: Moon, labelKey: 'common.appearance.dark' },
    { value: 'system', Icon: Monitor, labelKey: 'common.appearance.system' },
] as const;
</script>

<template>
    <div
        class="inline-flex max-w-full gap-1 overflow-x-auto rounded-xl bg-muted p-1"
    >
        <button
            v-for="{ value, Icon, labelKey } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            :class="[
                'flex min-h-10 min-w-max cursor-pointer items-center gap-2 rounded-lg px-3.5 text-sm font-medium transition-all focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none motion-reduce:transition-none',
                appearance === value
                    ? 'bg-card text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
            ]"
        >
            <component :is="Icon" class="size-4" aria-hidden="true" />
            <span>{{ t(labelKey) }}</span>
        </button>
    </div>
</template>
