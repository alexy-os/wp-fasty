import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

// Context for RadioGroup
type RadioGroupContextValue = {
  value?: string;
  onChange?: (value: string) => void;
  disabled?: boolean;
  name?: string;
};

const RadioGroupContext = React.createContext<RadioGroupContextValue>({});

// Interfaces
export interface RadioGroupProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'onChange'> {
  asChild?: boolean;
  value?: string;
  onChange?: (value: string) => void;
  disabled?: boolean;
  name?: string;
}

export interface RadioProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type' | 'value'> {
  asChild?: boolean;
  value: string;
}

// Components
const RadioGroup = React.forwardRef<HTMLDivElement, RadioGroupProps>(
  ({ asChild, value, onChange, disabled, name, ...props }, ref) => {
    const Comp = asChild ? Slot : "div";

    return (
      <RadioGroupContext.Provider value={{ value, onChange, disabled, name }}>
        <Comp ref={ref} role="radiogroup" {...props} />
      </RadioGroupContext.Provider>
    );
  }
);

const Radio = React.forwardRef<HTMLInputElement, RadioProps>(
  ({ asChild, value, disabled: itemDisabled, ...props }, ref) => {
    const { value: groupValue, onChange, disabled: groupDisabled, name } = React.useContext(RadioGroupContext);
    const Comp = asChild ? Slot : "input";
    const isDisabled = groupDisabled || itemDisabled;

    return (
      <Comp
        ref={ref}
        type="radio"
        role="radio"
        name={name}
        value={value}
        checked={value === groupValue}
        disabled={isDisabled}
        onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
          if (e.target.checked) {
            onChange?.(value);
          }
        }}
        {...props}
      />
    );
  }
);

export { RadioGroup, Radio }; 