import { cva } from 'class-variance-authority';

/**
 * Badge component props interface
 * Follows 8px design system and shadcn color palette
 */
export type BadgeProps = {
  /**
   * Badge visual style variant
   * @default "default"
   */
  variant?: "default" | "primary" | "secondary" | "outline" | "success" | "warning" | "destructive";

  /**
   * Badge size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Children content
   */
  children?: React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Badge component variants using CVA
 * Uses shadcn oklch color variables
 */
export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full font-medium",
  {
    variants: {
      variant: {
        default: "bg-muted text-muted-foreground",
        primary: "bg-primary text-primary-foreground",
        secondary: "bg-secondary text-secondary-foreground",
        outline: "border border-input bg-background text-foreground",
        success: "bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200",
        warning: "bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200",
        destructive: "bg-destructive text-destructive-foreground"
      },
      size: {
        sm: "text-xs px-2 py-0.5",
        default: "text-xs px-2.5 py-0.5",
        lg: "text-sm px-3 py-1"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
); 