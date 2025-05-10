import { cva } from 'class-variance-authority';

/**
 * Icon component props interface
 * Follows 8px design system and shadcn color palette
 * Uses lucide-react icons
 */
export type IconProps = {
  /**
   * Icon visual style variant
   * @default "default"
   */
  variant?: "default" | "primary" | "secondary" | "muted" | "accent" | "destructive";

  /**
   * Icon size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg" | "xl";

  /**
   * Stroke width (0-2)
   * @default 1.5
   */
  strokeWidth?: number;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Icon component variants using CVA
 * Uses shadcn oklch color variables
 */
export const iconVariants = cva(
  "",
  {
    variants: {
      variant: {
        default: "text-foreground",
        primary: "text-primary",
        secondary: "text-secondary",
        muted: "text-muted-foreground",
        accent: "text-accent-foreground",
        destructive: "text-destructive"
      },
      size: {
        sm: "w-3.5 h-3.5",
        default: "w-4 h-4",
        lg: "w-5 h-5",
        xl: "w-6 h-6"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
); 