<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { Loader2Icon } from "@lucide/vue"
import { useUi } from "@/composables/useUi"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const { t } = useUi()
</script>

<template>
  <Loader2Icon
    role="status"
    :aria-label="t('common.states.loading')"
    :class="cn('size-4 animate-spin motion-reduce:animate-none', props.class)"
  />
</template>
