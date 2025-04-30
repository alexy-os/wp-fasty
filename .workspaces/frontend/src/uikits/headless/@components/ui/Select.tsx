import * as React from "react";
import {
  SelectRoot,
  SelectRootTrigger,
  SelectRootContent,
  SelectRootItem,
  type SelectRootProps,
  type SelectTriggerProps,
  type SelectContentProps,
  type SelectItemProps,
} from "@ui-factory/ui-headless/form";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:root
const rootStyles = tv({
  base: "relative",
  variants: {}
});

// @component:trigger
const triggerStyles = tv({
  base: "flex w-full items-center justify-between rounded-md border bg-transparent text-sm shadow-sm ring-offset-background focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50",
  variants: {
    size: {
      sm: "h-8 px-2.5 py-1.5 text-xs",
      md: "h-9 px-3 py-2 text-sm",
      lg: "h-10 px-4 py-2 text-base",
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// @component:content
const contentStyles = tv({
  base: "relative z-50 min-w-[8rem] overflow-hidden rounded-md border bg-popover text-popover-foreground shadow-md animate-in fade-in-80 data-[side=bottom]:slide-in-from-top-2 data-[side=top]:slide-in-from-bottom-2",
  variants: {
    size: {
      sm: "rounded-sm",
      md: "rounded-md",
      lg: "rounded-lg",
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// @component:item
const itemStyles = tv({
  base: "relative flex w-full cursor-default select-none items-center rounded-sm outline-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 focus:bg-accent focus:text-accent-foreground",
  variants: {
    size: {
      sm: "px-1.5 py-1 text-xs",
      md: "px-2 py-1.5 text-sm",
      lg: "px-3 py-2 text-base",
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// Context for sharing size prop
const SelectContext = React.createContext<{ size?: "sm" | "md" | "lg" }>({});

// Extended interfaces
interface SelectProps extends SelectRootProps {
  size?: "sm" | "md" | "lg";
}

const Select = React.forwardRef<HTMLDivElement, SelectProps>(
  ({ size = "md", className, children, ...props }, ref) => (
    <SelectContext.Provider value={{ size }}>
      <SelectRoot
        ref={ref}
        className={twMerge(rootStyles(), className)}
        {...props}
      >
        {children}
      </SelectRoot>
    </SelectContext.Provider>
  )
);

const SelectTrigger = React.forwardRef<HTMLButtonElement, SelectTriggerProps>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(SelectContext);
    return (
      <SelectRootTrigger
        ref={ref}
        className={twMerge(triggerStyles({ size }), className)}
        {...props}
      />
    );
  }
);

const SelectContent = React.forwardRef<HTMLDivElement, SelectContentProps>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(SelectContext);
    return (
      <SelectRootContent
        ref={ref}
        className={twMerge(contentStyles({ size }), className)}
        {...props}
      />
    );
  }
);

const SelectItem = React.forwardRef<HTMLDivElement, SelectItemProps>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(SelectContext);
    return (
      <SelectRootItem
        ref={ref}
        className={twMerge(itemStyles({ size }), className)}
        {...props}
      />
    );
  }
);

Select.displayName = "Select";
SelectTrigger.displayName = "SelectTrigger";
SelectContent.displayName = "SelectContent";
SelectItem.displayName = "SelectItem";

export { Select, SelectTrigger, SelectContent, SelectItem }; 