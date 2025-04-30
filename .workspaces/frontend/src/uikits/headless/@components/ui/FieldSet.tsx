import * as React from "react";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:fieldset
const fieldsetStyles = tv({
  base: "space-y-4 disabled:opacity-50",
  variants: {}
});

// @component:legend
const legendStyles = tv({
  base: "text-sm font-medium leading-none mb-3",
  variants: {
    required: {
      true: "after:content-['*'] after:ml-0.5 after:text-destructive"
    }
  },
  defaultVariants: {
    required: false
  }
});

interface FieldSetProps extends React.FieldsetHTMLAttributes<HTMLFieldSetElement> {
  legend?: string;
  required?: boolean;
}

const FieldSet = React.forwardRef<HTMLFieldSetElement, FieldSetProps>(
  ({ legend, required, className, ...props }, ref) => (
    <fieldset
      ref={ref}
      className={twMerge(fieldsetStyles(), className)}
      {...props}
    >
      {legend && (
        <legend className={legendStyles({ required })}>
          {legend}
        </legend>
      )}
      {props.children}
    </fieldset>
  )
);

FieldSet.displayName = "FieldSet";

export { FieldSet }; 