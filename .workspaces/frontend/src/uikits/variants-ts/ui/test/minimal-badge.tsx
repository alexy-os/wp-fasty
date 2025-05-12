import * as React from "react";
import { cn } from "@ui8px/source/lib/utils";

function Badge({ className, ...props }: React.ComponentProps<"span">) {
  return (
    <span
      data-slot="badge"
      className={cn("badge", className)}
      {...props} />);
}

Badge.displayName = "Badge";

export { Badge };
