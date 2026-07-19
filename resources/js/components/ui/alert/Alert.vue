<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import type { AlertVariants } from "."
import { computed } from "vue"
import { cn } from "@/lib/utils"
import { alertVariants } from "."

const props = defineProps<{
  class?: HTMLAttributes["class"]
  variant?: AlertVariants["variant"]
  role?: "alert" | "status"
}>()

const resolvedRole = computed(() => props.role ?? (props.variant === "success" ? "status" : "alert"))
</script>

<template>
  <div
    data-slot="alert"
    :class="cn(alertVariants({ variant }), props.class)"
    :role="resolvedRole"
  >
    <slot />
  </div>
</template>
