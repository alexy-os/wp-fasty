import * as React from "react";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:message
const messageStyles = tv({
  base: "text-sm",
  variants: {
    variant: {
      default: "text-muted-foreground",
      error: "text-destructive",
      success: "text-success",
      warning: "text-warning"
    }
  },
  defaultVariants: {
    variant: "default"
  }
});

interface FormMessageProps extends React.HTMLAttributes<HTMLParagraphElement> {
  variant?: "default" | "error" | "success" | "warning";
}

const FormMessage = React.forwardRef<HTMLParagraphElement, FormMessageProps>(
  ({ variant, className, ...props }, ref) => (
    <p
      ref={ref}
      className={twMerge(messageStyles({ variant }), className)}
      {...props}
    />
  )
);

FormMessage.displayName = "FormMessage";

export { FormMessage }; 