import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

export interface BadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
  asChild?: boolean;
  ignoreBaseStyle?: boolean;
}

const Badge = React.forwardRef<HTMLSpanElement, BadgeProps>(
  ({ asChild = false, ignoreBaseStyle = false, ...props }, ref) => {
    const Comp = asChild ? Slot : "span";
    
    return (
      <Comp
        ref={ref}
        {...props}
      />
    );
  }
);

export { Badge }; 