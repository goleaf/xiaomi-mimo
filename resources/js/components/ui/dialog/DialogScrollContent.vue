<script setup lang="ts">
import type { DialogContentEmits, DialogContentProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { X } from "@lucide/vue"
import { reactiveOmit } from "@vueuse/core"
import {
  DialogClose,
  DialogContent,
  DialogOverlay,
  DialogPortal,
  useForwardPropsEmits,
} from "reka-ui"
import { useUi } from "@/composables/useUi"
import { cn } from "@/lib/utils"

defineOptions({
  inheritAttrs: false,
})

const props = defineProps<DialogContentProps & { class?: HTMLAttributes["class"] }>()
const emits = defineEmits<DialogContentEmits>()
const { t } = useUi()

const delegatedProps = reactiveOmit(props, "class")

const forwarded = useForwardPropsEmits(delegatedProps, emits)
</script>

<template>
  <DialogPortal>
    <DialogOverlay
      class="fixed inset-0 z-50 grid place-items-center overflow-y-auto bg-black/65 backdrop-blur-[2px] data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 motion-reduce:data-[state=open]:animate-none motion-reduce:data-[state=closed]:animate-none"
    >
      <DialogContent
        :class="
          cn(
            'relative z-50 my-8 grid w-full max-w-[calc(100%-2rem)] gap-4 rounded-[1.5rem] border border-border/80 bg-card p-6 shadow-[0_32px_90px_-36px_rgba(15,23,42,0.55)] duration-200 md:w-full lg:max-w-lg',
            props.class,
          )
        "
        v-bind="{ ...$attrs, ...forwarded }"
        @pointer-down-outside="(event) => {
          const originalEvent = event.detail.originalEvent;
          const target = originalEvent.target as HTMLElement;
          if (originalEvent.offsetX > target.clientWidth || originalEvent.offsetY > target.clientHeight) {
            event.preventDefault();
          }
        }"
      >
        <slot />

        <DialogClose
          class="absolute top-4 right-4 flex size-11 cursor-pointer items-center justify-center rounded-xl text-muted-foreground transition-colors hover:bg-orange-500/10 hover:text-orange-800 focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:outline-none dark:hover:text-orange-200"
        >
          <X class="w-4 h-4" />
          <span class="sr-only">{{ t('common.actions.close') }}</span>
        </DialogClose>
      </DialogContent>
    </DialogOverlay>
  </DialogPortal>
</template>
