<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { X } from "@lucide/vue"
import { reactiveOmit } from "@vueuse/core"
import {
  DialogClose,
  DialogContent,
  DialogPortal,
  useForwardPropsEmits,
} from "reka-ui"
import { useUi } from "@/composables/useUi"
import { cn } from "@/lib/utils"
import SheetOverlay from "./SheetOverlay.vue"

interface SheetContentProps extends DialogContentProps {
  class?: HTMLAttributes["class"]
  side?: "top" | "right" | "bottom" | "left"
  closeLabel?: string
}

defineOptions({
  inheritAttrs: false,
})

const props = withDefaults(defineProps<SheetContentProps>(), {
  side: "right",
})
const emits = defineEmits<DialogContentEmits>()
const { t } = useUi()

const delegatedProps = reactiveOmit(props, "class", "side", "closeLabel")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <SheetOverlay />
    <DialogContent
      data-slot="sheet-content"
      :class="cn(
        'bg-card data-[state=open]:animate-in data-[state=closed]:animate-out motion-reduce:data-[state=open]:animate-none motion-reduce:data-[state=closed]:animate-none fixed z-50 flex flex-col gap-4 shadow-[0_32px_90px_-40px_rgba(15,23,42,0.65)] transition ease-in-out motion-reduce:transition-none data-[state=closed]:duration-300 data-[state=open]:duration-500',
        side === 'right'
          && 'data-[state=closed]:slide-out-to-right data-[state=open]:slide-in-from-right inset-y-0 right-0 h-full w-3/4 border-l border-border/80 sm:max-w-sm',
        side === 'left'
          && 'data-[state=closed]:slide-out-to-left data-[state=open]:slide-in-from-left inset-y-0 left-0 h-full w-3/4 border-r border-border/80 sm:max-w-sm',
        side === 'top'
          && 'data-[state=closed]:slide-out-to-top data-[state=open]:slide-in-from-top inset-x-0 top-0 h-auto border-b border-border/80',
        side === 'bottom'
          && 'data-[state=closed]:slide-out-to-bottom data-[state=open]:slide-in-from-bottom inset-x-0 bottom-0 h-auto border-t border-border/80',
        props.class)"
      v-bind="{ ...$attrs, ...forwarded }"
    >
      <slot />

      <DialogClose
        class="absolute top-4 right-4 flex size-11 cursor-pointer items-center justify-center rounded-xl text-muted-foreground transition-colors hover:bg-orange-500/10 hover:text-orange-800 focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none disabled:pointer-events-none dark:hover:text-orange-200"
      >
        <X class="size-4" />
        <span class="sr-only">{{ closeLabel ?? t('common.actions.close') }}</span>
      </DialogClose>
    </DialogContent>
  </DialogPortal>
</template>
