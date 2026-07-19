<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { useUi } from "@/composables/useUi"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { t } = useUi()
</script>

<template>
  <nav
    :aria-label="t('common.navigation.breadcrumb')"
    data-slot="breadcrumb"
    :class="props.class"
  >
    <slot />
  </nav>
</template>
