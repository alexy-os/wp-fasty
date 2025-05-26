import * as React from "react";
import { Slot } from "@radix-ui/react-slot";
import { cn } from "@/lib/utils";
import { getTheme } from "@/utils/theme";
import { Button as ButtonShadcn, buttonVariants } from "@n4shadcn/ui/button";
import { type VariantProps } from "class-variance-authority"

export function Button({
  children,
  variant = 'default',
  size = 'default',
  className,
  ...props
}: React.ComponentProps<"button"> & {
  variant?: "default" | "link" | "destructive" | "outline" | "secondary" | "ghost" | null;
  size?: "default" | "sm" | "lg" | "icon" | null;
}) {
  const theme = getTheme();

  // Select button component based on theme
  const ThemedButton =
    theme === "semantic"
      ? SemanticButton
      : ButtonShadcn;

  return (
    <ThemedButton
      variant={variant}
      size={size}
      className={className}
      {...props}
    >
      {children}
    </ThemedButton>
  );
}

function SemanticButton({
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