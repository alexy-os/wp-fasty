import * as React from "react";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:control
const controlStyles = tv({
  base: "space-y-2",
  variants: {
    state: {
      default: "",
      error: "[&_input]:border-destructive [&_textarea]:border-destructive [&_select]:border-destructive",
      success: "[&_input]:border-success [&_textarea]:border-success [&_select]:border-success",
    }
  },
  defaultVariants: {
    state: "default"
  }
});

interface FormControlProps extends React.HTMLAttributes<HTMLDivElement> {
  state?: "default" | "error" | "success";
}

const FormControl = React.forwardRef<HTMLDivElement, FormControlProps>(
  ({ state, className, ...props }, ref) => (
    <div
      ref={ref}
      className={twMerge(controlStyles({ state }), className)}
      {...props}
    />
  )
);

FormControl.displayName = "FormControl";

export { FormControl }; 