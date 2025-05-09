import { cva } from 'class-variance-authority';
import { TypographyBaseProps } from '../typography/types';

/**
 * Text component props interface
 */
export type TextProps = TypographyBaseProps & {
  /**
   * Text size variant
   * @default "default"
   */
  size?: "xs" | "sm" | "default" | "lg" | "xl";

  /**
   * Text weight
   * @default "default"
   */
  weight?: "light" | "default" | "medium" | "semibold" | "bold";

  /**
   * Text color variant
   * @default "default"
   */
  variant?: "default" | "muted" | "primary" | "secondary" | "destructive";
};

/**
 * Text component variants using CVA
 */
export const textVariants = cva(
  "",
  {
    variants: {
      size: {
        xs: "text-xs",
        sm: "text-sm",
        default: "text-base",
        lg: "text-lg",
        xl: "text-xl"
      },
      weight: {
        light: "font-light",
        default: "font-normal",
        medium: "font-medium",
        semibold: "font-semibold",
        bold: "font-bold"
      },
      variant: {
        default: "text-foreground",
        muted: "text-muted-foreground",
        primary: "text-primary",
        secondary: "text-secondary",
        destructive: "text-destructive"
      }
    },
    defaultVariants: {
      size: "default",
      weight: "default",
      variant: "default"
    }
  }
); 