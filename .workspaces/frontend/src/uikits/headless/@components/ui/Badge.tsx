import * as React from "react"
import { Badge as HeadlessBadge, type BadgeProps as HeadlessBadgeProps } from "@ui-factory/ui-headless/badge"
import { twMerge } from "tailwind-merge"
import { tv } from "tailwind-variants"

// Define badge variants using tailwind-variants
const badgeStyles = tv({
  base: "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2",
  variants: {
    variant: {
      default: "border-transparent bg-primary text-primary-foreground hover:bg-primary/80",
      secondary: "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
      destructive: "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
      outline: "text-foreground",
      success: "border-transparent bg-success text-success-foreground hover:bg-success/80",
      warning: "border-transparent bg-warning text-warning-foreground hover:bg-warning/80",
    },
  },
  defaultVariants: {
    variant: "default",
  },
})

// Extended Badge props
interface BadgeProps extends HeadlessBadgeProps {
  variant?: "default" | "secondary" | "destructive" | "outline" | "success" | "warning"
  className?: string
}

// Molecule Badge component using tailwind styles
const Badge = React.forwardRef<HTMLSpanElement, BadgeProps>(
  (props, ref) => {
    const {
      variant = "default",
      className,
      children,
      asChild = false,
      ...badgeProps
    } = props

    // Generate the class name using tailwind-variants
    const variantClasses = badgeStyles({ variant })

    // Merge with any additional classNames
    const combinedClassNames = className ? twMerge(variantClasses, className) : variantClasses

    return (
      <HeadlessBadge
        ref={ref}
        className={combinedClassNames}
        asChild={asChild}
        ignoreBaseStyle={true}
        {...badgeProps}
      >
        {children}
      </HeadlessBadge>
    )
  }
)

Badge.displayName = "Badge"

export { Badge, type BadgeProps }
