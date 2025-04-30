import * as React from "react";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:label
const labelStyles = tv({
  base: "text-sm font-medium leading-none",
  variants: {
    required: {
      true: "after:content-['*'] after:ml-0.5 after:text-destructive"
    },
    disabled: {
      true: "cursor-not-allowed opacity-70"
    }
  },
  defaultVariants: {
    required: false,
    disabled: false
  }
});

interface LabelProps extends React.LabelHTMLAttributes<HTMLLabelElement> {
  required?: boolean;
  disabled?: boolean;
}

const Label = React.forwardRef<HTMLLabelElement, LabelProps>(
  ({ required, disabled, className, ...props }, ref) => (
    <label
      ref={ref}
      className={twMerge(labelStyles({ required, disabled }), className)}
      {...props}
    />
  )
);

Label.displayName = "Label";

export { Label, type LabelProps };