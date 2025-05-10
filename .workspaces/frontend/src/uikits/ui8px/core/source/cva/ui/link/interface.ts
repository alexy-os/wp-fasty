import { cva } from 'class-variance-authority';

/**
 * Link component props interface
 * Follows 8px design system and shadcn color palette
 */
export type LinkProps = {
  /**
   * Link visual style variant
   * @default "default"
   */
  variant?: "default" | "primary" | "secondary" | "destructive" | "muted";

  /**
   * Link size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Link destination
   */
  href?: string;

  /**
   * Link content
   */
  children?: React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Link component variants using CVA
 * Uses shadcn oklch color variables
 */
export const linkVariants = cva(
  "inline-flex items-center font-medium underline-offset-4 focus-visible:outline-none focus-visible:underline hover:underline",
  {
    variants: {
      variant: {
        default: "text-foreground",
        primary: "text-primary",
        secondary: "text-secondary-foreground",
        destructive: "text-destructive",
        muted: "text-muted-foreground"
      },
      size: {
        sm: "text-xs",
        default: "text-sm",
        lg: "text-base"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
); 