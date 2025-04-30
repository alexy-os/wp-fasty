import * as React from "react";
import {
  RadioGroup,
  Radio as HeadlessRadio,
  type RadioGroupProps,
  type RadioProps,
} from "@ui-factory/ui-headless/form";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:group
const groupStyles = tv({
  base: "flex gap-2",
  variants: {}
});

// @component:radio
const radioStyles = tv({
  base: "aspect-square h-4 w-4 rounded-full border border-primary text-primary shadow focus:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50",
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

// Context for sharing size prop
const RadioContext = React.createContext<{ size?: "sm" | "md" | "lg" }>({});

interface RadioGroupExtendedProps extends RadioGroupProps {
  size?: "sm" | "md" | "lg";
}

interface RadioItemProps extends RadioProps {
  label?: string;
}

const Radio = React.forwardRef<HTMLDivElement, RadioGroupExtendedProps>(
  ({ size = "md", className, children, ...props }, ref) => (
    <RadioContext.Provider value={{ size }}>
      <RadioGroup 
        ref={ref} 
        className={twMerge(groupStyles(), className)} 
        {...props}
      >
        {children}
      </RadioGroup>
    </RadioContext.Provider>
  )
);

const RadioItem = React.forwardRef<HTMLInputElement, RadioItemProps>(
  ({ className, label, ...props }, ref) => {
    const { size } = React.useContext(RadioContext);
    
    return (
      <div className={wrapperStyles()}>
        <HeadlessRadio
          ref={ref}
          className={twMerge(radioStyles({ size }), className)}
          {...props}
        />
        {label && (
          <label className={labelStyles({ size })}>
            {label}
          </label>
        )}
      </div>
    );
  }
);

Radio.displayName = "Radio";
RadioItem.displayName = "RadioItem";

export { Radio, RadioItem }; 