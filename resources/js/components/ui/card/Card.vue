<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <div
    data-slot="card"
    :class="
      cn(
        'bg-card text-card-foreground flex flex-col gap-6 rounded-[1.5rem] border border-border/80 py-6 shadow-[0_20px_60px_-52px_rgba(15,23,42,0.6)]',
        props.class,
      )
    "
  >
    <slot />
  </div>
</template>
