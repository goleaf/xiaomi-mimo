import type { VariantProps } from "class-variance-authority"
import { cva } from "class-variance-authority"

export { default as Button } from "./Button.vue"

export const buttonVariants = cva(
  "inline-flex cursor-pointer items-center justify-center gap-2 whitespace-nowrap rounded-xl text-sm font-medium transition-all motion-reduce:transition-none disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*='size-'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
  {
    variants: {
      variant: {
        default:
          "bg-orange-600 text-white shadow-sm hover:bg-orange-700 focus-visible:ring-orange-500/30 dark:bg-orange-600 dark:hover:bg-orange-500",
        destructive:
          "bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/20 dark:focus-visible:ring-destructive/40 dark:bg-destructive/60",
        outline:
          "border border-border/80 bg-background shadow-xs hover:border-orange-500/25 hover:bg-orange-500/5 hover:text-orange-800 focus-visible:ring-orange-500/25 dark:bg-input/30 dark:border-input dark:hover:bg-orange-500/10 dark:hover:text-orange-200",
        secondary:
          "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost:
          "hover:bg-orange-500/8 hover:text-orange-800 focus-visible:ring-orange-500/25 dark:hover:bg-orange-500/10 dark:hover:text-orange-200",
        link: "text-orange-700 underline-offset-4 hover:underline focus-visible:ring-orange-500/25 dark:text-orange-300",
      },
      size: {
        "default": "h-10 px-4 py-2 has-[>svg]:px-3",
        "sm": "h-9 rounded-lg gap-1.5 px-3 has-[>svg]:px-2.5",
        "lg": "h-11 px-6 has-[>svg]:px-4",
        "icon": "size-10",
        "icon-sm": "size-9",
        "icon-lg": "size-11",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  },
)
export type ButtonVariants = VariantProps<typeof buttonVariants>
