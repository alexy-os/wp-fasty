import { cva } from 'class-variance-authority';

/**
 * Typography base props interface
 */
export type TypographyBaseProps = {
  /**
   * Content to display
   */
  children?: React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

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