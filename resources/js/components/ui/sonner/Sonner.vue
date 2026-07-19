<script setup lang="ts">
import type { CSSProperties } from "vue"
import type { ToasterProps } from "vue-sonner"
import { CircleCheckIcon, InfoIcon, Loader2Icon, OctagonXIcon, TriangleAlertIcon, XIcon } from "@lucide/vue"
import { reactiveOmit } from "@vueuse/core"
import { computed } from "vue"
import { Toaster as Sonner } from "vue-sonner"
import { useAppearance } from "@/composables/useAppearance"
import { useUi } from "@/composables/useUi"
import { cn } from "@/lib/utils"

import "vue-sonner/style.css"

const props = defineProps<ToasterProps>()
type ToastOptions = NonNullable<ToasterProps["toastOptions"]>
const delegatedProps = reactiveOmit(props, "class", "containerAriaLabel", "style", "theme", "toastOptions")
const { resolvedAppearance } = useAppearance()
const { t } = useUi()

const toasterStyle = computed<CSSProperties>(() => ({
  "--normal-bg": "var(--popover)",
  "--normal-text": "var(--popover-foreground)",
  "--normal-border": "color-mix(in oklab, var(--border) 80%, transparent)",
  "--border-radius": "var(--radius)",
  ...props.style,
}))

const toastOptions = computed<ToastOptions>(() => ({
  ...props.toastOptions,
  closeButtonAriaLabel: props.toastOptions?.closeButtonAriaLabel ?? t("common.toast.close"),
  style: {
    borderRadius: "var(--radius)",
    boxShadow: "0 24px 70px -36px rgba(15, 23, 42, 0.65)",
    ...props.toastOptions?.style,
  },
  classes: {
    ...props.toastOptions?.classes,
    actionButton: cn("!h-8 !rounded-lg !bg-orange-600 !px-3 !text-white focus-visible:!shadow-[0_0_0_3px_rgba(249,115,22,0.3)]", props.toastOptions?.classes?.actionButton),
    cancelButton: cn("!h-8 !rounded-lg !px-3 focus-visible:!shadow-[0_0_0_3px_rgba(249,115,22,0.3)]", props.toastOptions?.classes?.cancelButton),
    closeButton: cn("focus-visible:!shadow-[0_0_0_3px_rgba(249,115,22,0.3)]", props.toastOptions?.classes?.closeButton),
    toast: cn("focus-visible:!shadow-[0_24px_70px_-36px_rgba(15,23,42,0.65),0_0_0_3px_rgba(249,115,22,0.3)]", props.toastOptions?.classes?.toast),
  },
}))
</script>

<template>
  <Sonner
    v-bind="delegatedProps"
    :class="cn('toaster group', props.class)"
    :container-aria-label="props.containerAriaLabel ?? t('common.toast.notifications')"
    :style="toasterStyle"
    :theme="props.theme ?? resolvedAppearance"
    :toast-options="toastOptions"
  >
    <template #success-icon>
      <CircleCheckIcon class="size-4" />
    </template>
    <template #info-icon>
      <InfoIcon class="size-4" />
    </template>
    <template #warning-icon>
      <TriangleAlertIcon class="size-4" />
    </template>
    <template #error-icon>
      <OctagonXIcon class="size-4" />
    </template>
    <template #loading-icon>
      <div>
        <Loader2Icon class="size-4 animate-spin motion-reduce:animate-none" />
      </div>
    </template>
    <template #close-icon>
      <XIcon class="size-4" />
    </template>
  </Sonner>
</template>
