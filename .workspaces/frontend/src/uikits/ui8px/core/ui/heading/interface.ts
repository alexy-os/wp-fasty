import { cva } from 'class-variance-authority';
import { TypographyBaseProps } from '../typography/types';

/**
 * Heading component props interface
 */
export type HeadingProps = TypographyBaseProps & {
  /**
   * Heading level
   * @default "h2"
   */
  level?: "h1" | "h2" | "h3" | "h4" | "h5" | "h6";

  /**
   * Heading weight
   * @default "default"
   */
  weight?: "light" | "default" | "medium" | "semibold" | "bold";
};

/**
 * Heading component variants using CVA
 */
export const headingVariants = cva(
  "text-foreground scroll-m-20",
  {
    variants: {
      level: {
        h1: "text-3xl font-semibold tracking-tight lg:text-4xl",
        h2: "text-2xl font-semibold tracking-tight",
        h3: "text-xl font-semibold tracking-tight",
        h4: "text-lg font-semibold tracking-tight",
        h5: "text-base font-semibold tracking-tight",
        h6: "text-sm font-semibold tracking-tight"
      },
      weight: {
        light: "font-light",
        default: "",
        medium: "font-medium",
        semibold: "font-semibold",
        bold: "font-bold"
      }
    },
    defaultVariants: {
      level: "h2",
      weight: "default"
    }
  }
);