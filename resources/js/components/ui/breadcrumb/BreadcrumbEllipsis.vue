<script lang="ts" setup>
import type { HTMLAttributes } from "vue"
import { MoreHorizontal } from "@lucide/vue"
import { useUi } from "@/composables/useUi"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
const { t } = useUi()
</script>

<template>
  <span
    data-slot="breadcrumb-ellipsis"
    role="presentation"
    aria-hidden="true"
    :class="cn('flex size-9 items-center justify-center', props.class)"
  >
    <slot>
      <MoreHorizontal class="size-4" />
    </slot>
    <span class="sr-only">{{ t('common.navigation.more') }}</span>
  </span>
</template>
