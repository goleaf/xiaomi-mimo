import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Alert } from "./Alert.vue"
export { default as AlertDescription } from "./AlertDescription.vue"
export { default as AlertTitle } from "./AlertTitle.vue"

export const alertVariants = cva(
  "relative grid w-full grid-cols-[0_1fr] items-start gap-y-0.5 rounded-xl border border-border/80 px-4 py-3.5 text-sm shadow-[0_14px_36px_-32px_rgba(15,23,42,0.5)] has-[>svg]:grid-cols-[calc(var(--spacing)*4)_1fr] has-[>svg]:gap-x-3 [&>svg]:size-4 [&>svg]:translate-y-0.5 [&>svg]:text-current",
  {
    variants: {
      variant: {
        default: "bg-card text-card-foreground",
        destructive:
          "border-destructive/20 bg-destructive/[0.06] text-destructive [&>svg]:text-current *:data-[slot=alert-description]:text-destructive/90",
        success:
          "border-emerald-500/20 bg-emerald-500/[0.07] text-emerald-800 [&>svg]:text-emerald-700 *:data-[slot=alert-description]:text-emerald-800/90 dark:text-emerald-200 dark:[&>svg]:text-emerald-300 dark:*:data-[slot=alert-description]:text-emerald-200/90",
        warning:
          "border-amber-500/25 bg-amber-500/[0.08] text-amber-950 [&>svg]:text-amber-700 *:data-[slot=alert-description]:text-amber-950/85 dark:text-amber-100 dark:[&>svg]:text-amber-300 dark:*:data-[slot=alert-description]:text-amber-100/85",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  },
)

export type AlertVariants = VariantProps<typeof alertVariants>
