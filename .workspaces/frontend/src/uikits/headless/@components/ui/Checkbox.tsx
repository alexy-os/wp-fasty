import * as React from "react";
import {
  Checkbox as HeadlessCheckbox,
  type CheckboxProps as HeadlessCheckboxProps,
} from "@ui-factory/ui-headless/form";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:checkbox
const checkboxStyles = tv({
  base: "aspect-square peer shrink-0 rounded-sm border border-primary ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground",
  variants: {
    size: {
      sm: "h-3 w-3",
      md: "h-4 w-4",
      lg: "h-5 w-5"
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// @component:wrapper
const wrapperStyles = tv({
  base: "flex items-center space-x-2 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50",
  variants: {}
});

// @component:label
const labelStyles = tv({
  base: "text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70",
  variants: {
    size: {
      sm: "text-xs",
      md: "text-sm",
      lg: "text-base"
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// Extend the base props, excluding size from the headless component
type CheckboxProps = Omit<HeadlessCheckboxProps, 'size'> & {
  label?: string;
  size?: "sm" | "md" | "lg";
};

const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, label, size = "md", ...props }, ref) => (
    <div className={wrapperStyles()}>
      <HeadlessCheckbox
        ref={ref}
        className={twMerge(checkboxStyles({ size }), className)}
        {...props}
      />
      {label && (
        <label className={labelStyles({ size })}>
          {label}
        </label>
      )}
    </div>
  )
);

Checkbox.displayName = "Checkbox";

export { Checkbox, type CheckboxProps }; 