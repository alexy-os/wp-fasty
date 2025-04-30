import * as React from "react";
import {
  Textarea as HeadlessTextarea,
  type TextareaProps as HeadlessTextareaProps,
} from "@ui-factory/ui-headless/form";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:textarea
const textareaStyles = tv({
  base: "flex min-h-[60px] w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive aria-invalid:ring-destructive",
  variants: {
    size: {
      sm: "text-xs px-2 py-1",
      md: "text-sm px-3 py-2",
      lg: "text-base px-4 py-3",
    },
    resize: {
      none: "resize-none",
      vertical: "resize-y",
      horizontal: "resize-x",
      both: "resize",
    }
  },
  defaultVariants: {
    size: "md",
    resize: "vertical"
  }
});

// Extend the base props, excluding size from the headless component
type TextareaProps = Omit<HeadlessTextareaProps, 'size'> & {
  size?: "sm" | "md" | "lg";
  resize?: "none" | "vertical" | "horizontal" | "both";
};

const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, size, resize, autoResize, ...props }, ref) => {
    // If autoResize is enabled, force resize to none
    const finalResize = autoResize ? "none" : resize;

    return (
      <HeadlessTextarea
        ref={ref}
        className={twMerge(textareaStyles({ size, resize: finalResize }), className)}
        autoResize={autoResize}
        {...props}
      />
    );
  }
);

Textarea.displayName = "Textarea";

export { Textarea, type TextareaProps }; 