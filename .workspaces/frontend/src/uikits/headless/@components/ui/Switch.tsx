import * as React from "react";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:switch
const switchStyles = tv({
  base: "peer inline-flex h-5 w-9 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:opacity-50 data-[state=checked]:bg-primary data-[state=unchecked]:bg-input",
  variants: {
    size: {
      sm: "h-4 w-7",
      md: "h-5 w-9",
      lg: "h-6 w-11"
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// @component:thumb
const thumbStyles = tv({
  base: "pointer-events-none block rounded-full bg-background shadow-lg ring-0 transition-transform data-[state=checked]:translate-x-4 data-[state=unchecked]:translate-x-0",
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

interface SwitchProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  checked?: boolean;
  onCheckedChange?: (checked: boolean) => void;
  size?: "sm" | "md" | "lg";
}

const Switch = React.forwardRef<HTMLButtonElement, SwitchProps>(
  ({ checked, onCheckedChange, size, className, ...props }, ref) => (
    <button
      ref={ref}
      role="switch"
      aria-checked={checked}
      data-state={checked ? "checked" : "unchecked"}
      className={twMerge(switchStyles({ size }), className)}
      onClick={() => onCheckedChange?.(!checked)}
      {...props}
    >
      <span className={thumbStyles({ size })} />
    </button>
  )
);

Switch.displayName = "Switch";

export { Switch, type SwitchProps };