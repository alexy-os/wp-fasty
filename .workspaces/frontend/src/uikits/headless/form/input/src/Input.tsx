import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

export interface InputProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'size'> {
  asChild?: boolean;
  size?: 'sm' | 'md' | 'lg';
  invalid?: boolean;
}

const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ asChild, size, invalid, ...props }, ref) => {
    const Comp = asChild ? Slot : "input";
    
    return (
      <Comp
        ref={ref}
        data-size={size}
        aria-invalid={invalid}
        {...props}
      />
    );
  }
); 

//Input.displayName = "Input";

export { Input };
