import { cva } from 'class-variance-authority';

/**
 * Progress component props interface
 * Follows 8px design system and shadcn color palette
 */
export type ProgressProps = {
  /**
   * Progress bar visual style variant
   * @default "default"
   */
  variant?: "default" | "primary" | "secondary" | "success" | "warning" | "destructive";

  /**
   * Progress bar size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Current value (0-100)
   */
  value: number;

  /**
   * Max value
   * @default 100
   */
  max?: number;

  /**
   * Label to show current progress (e.g., "3/5")
   */
  label?: string;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Progress component variants using CVA
 * Uses shadcn oklch color variables
 */
export const progressVariants = cva(
  "flex w-full rounded-full overflow-hidden",
  {
    variants: {
      variant: {
        default: "bg-secondary",
        primary: "bg-primary/20",
        secondary: "bg-secondary/30",
        success: "bg-green-100 dark:bg-green-900/30",
        warning: "bg-orange-100 dark:bg-orange-900/30",
        destructive: "bg-destructive/20"
      },
      size: {
        sm: "h-1",
        default: "h-1.5",
        lg: "h-2"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);

/**
 * Progress indicator variants using CVA
 */
export const progressIndicatorVariants = cva(
  "flex flex-col justify-center overflow-hidden h-full",
  {
    variants: {
      variant: {
        default: "bg-foreground",
        primary: "bg-primary",
        secondary: "bg-secondary",
        success: "bg-green-600 dark:bg-green-500",
        warning: "bg-orange-600 dark:bg-orange-500",
        destructive: "bg-destructive"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
); 