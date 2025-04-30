import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

// Context for Select
type SelectContextValue = {
  value?: string;
  onChange?: (value: string) => void;
  open?: boolean;
  onOpenChange?: (open: boolean) => void;
  disabled?: boolean;
};

const SelectContext = React.createContext<SelectContextValue>({});

// Interfaces
export interface SelectRootProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'onChange'> {
  asChild?: boolean;
  value?: string;
  onChange?: (value: string) => void;
  disabled?: boolean;
  defaultOpen?: boolean;
}

export interface SelectTriggerProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  asChild?: boolean;
}

export interface SelectContentProps extends React.HTMLAttributes<HTMLDivElement> {
  asChild?: boolean;
}

export interface SelectItemProps extends React.HTMLAttributes<HTMLDivElement> {
  asChild?: boolean;
  value: string;
}

// Components
const SelectRoot = React.forwardRef<HTMLDivElement, SelectRootProps>(
  ({ asChild, value, onChange, disabled, defaultOpen = false, ...props }, ref) => {
    const [open, setOpen] = React.useState(defaultOpen);
    const Comp = asChild ? Slot : "div";

    return (
      <SelectContext.Provider 
        value={{ 
          value, 
          onChange, 
          open, 
          onOpenChange: setOpen,
          disabled 
        }}
      >
        <Comp ref={ref} {...props} />
      </SelectContext.Provider>
    );
  }
);

const SelectRootTrigger = React.forwardRef<HTMLButtonElement, SelectTriggerProps>(
  ({ asChild, ...props }, ref) => {
    const { open, onOpenChange, disabled } = React.useContext(SelectContext);
    const Comp = asChild ? Slot : "button";

    return (
      <Comp
        ref={ref}
        type="button"
        role="combobox"
        aria-expanded={open}
        disabled={disabled}
        onClick={() => onOpenChange?.(!open)}
        {...props}
      />
    );
  }
);

const SelectRootContent = React.forwardRef<HTMLDivElement, SelectContentProps>(
  ({ asChild, ...props }, ref) => {
    const { open } = React.useContext(SelectContext);
    const Comp = asChild ? Slot : "div";

    if (!open) return null;

    return (
      <Comp
        ref={ref}
        role="listbox"
        {...props}
      />
    );
  }
);

const SelectRootItem = React.forwardRef<HTMLDivElement, SelectItemProps>(
  ({ asChild, value, ...props }, ref) => {
    const { onChange, value: selectedValue } = React.useContext(SelectContext);
    const Comp = asChild ? Slot : "div";

    return (
      <Comp
        ref={ref}
        role="option"
        aria-selected={value === selectedValue}
        onClick={() => onChange?.(value)}
        {...props}
      />
    );
  }
);

export { SelectRoot, SelectRootTrigger, SelectRootContent, SelectRootItem }; 