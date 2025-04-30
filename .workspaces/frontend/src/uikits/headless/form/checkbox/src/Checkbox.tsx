import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

export interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
  asChild?: boolean;
  indeterminate?: boolean;
}

const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ asChild, indeterminate, ...props }, ref) => {
    const inputRef = React.useRef<HTMLInputElement>(null);
    
    React.useEffect(() => {
      if (inputRef.current) {
        inputRef.current.indeterminate = indeterminate ?? false;
      }
    }, [indeterminate]);

    const Comp = asChild ? Slot : "input";
    
    return (
      <Comp
        ref={(node: HTMLInputElement | null) => {
          // Handle both refs
          inputRef.current = node;
          if (typeof ref === 'function') ref(node);
          else if (ref) ref.current = node;
        }}
        type="checkbox"
        {...props}
      />
    );
  }
); 

export { Checkbox };