import * as React from "react";
import { cn } from "@/lib/utils"
import { Slot } from "@radix-ui/react-slot";
import { badgeVariants } from "@ui8px/source/cva/ui/badge";
import { type VariantProps } from "class-variance-authority"

function Badge({
  className,
  variant = "default",
  asChild = false,
  ...props
}: React.ComponentProps<"span"> & VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span";

  return (
    <Comp
      data-slot="badge"
      className={cn(
        "badge",
        variant && `badge-${variant}`,
        className
      )}
      {...props}
    />
  );
}

Badge.displayName = "Badge";

export { Badge };
