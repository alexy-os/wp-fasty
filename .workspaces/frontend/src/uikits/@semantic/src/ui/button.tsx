import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cn } from "@/lib/utils";
import { buttonVariants } from "@n4shadcn/ui/button";
import { type VariantProps } from "class-variance-authority"

function Button({
  className,
  variant,
  size,
  asChild = false,
  ...props
}: React.ComponentProps<"button"> &
  VariantProps<typeof buttonVariants> & {
    asChild?: boolean
  }) {
  const Comp = asChild ? Slot : "button"

  return (
    <Comp
      data-slot="button"
      className={cn(
        "button",
        variant && `button-${variant}`,
        size && `button-${size}`,
        className
      )}
      {...props}
    />
  );
}

Button.displayName = "Button";

export { Button };
