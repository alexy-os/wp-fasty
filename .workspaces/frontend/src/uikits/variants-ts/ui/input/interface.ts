import { cva } from 'class-variance-authority';

/**
 * Input component props interface
 * Follows 8px design system and shadcn color palette
 */
export type InputProps = {
  /**
   * Input visual style variant
   * @default "default"
   */
  variant?: "default" | "filled" | "outline" | "ghost";

  /**
   * Input size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Input status variant
   * @default "default"
   */
  status?: "default" | "error" | "success" | "warning";

  /**
   * Is input disabled
   * @default false
   */
  disabled?: boolean;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Input component variants using CVA
 * Uses shadcn oklch color variables
 */
export const inputVariants = cva(
  "flex rounded-md border border-input text-foreground bg-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "",
        filled: "bg-muted border-transparent",
        outline: "bg-transparent",
        ghost: "border-transparent shadow-none"
      },
      size: {
        sm: "h-8 px-3 text-xs",
        default: "h-9 px-4 py-2 text-sm",
        lg: "h-10 px-4 py-2 text-base"
      },
      status: {
        default: "",
        error: "border-destructive focus-visible:ring-destructive",
        success: "border-green-600 focus-visible:ring-green-600 dark:border-green-500 dark:focus-visible:ring-green-500",
        warning: "border-orange-600 focus-visible:ring-orange-600 dark:border-orange-500 dark:focus-visible:ring-orange-500"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default",
      status: "default"
    }
  }
); 