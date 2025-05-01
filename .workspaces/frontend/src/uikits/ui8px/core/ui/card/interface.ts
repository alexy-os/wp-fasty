import { cva } from 'class-variance-authority';

/**
 * Card component props interface
 * Follows 8px design system and shadcn color palette
 */
export type CardProps = {
  /**
   * Card visual style variant
   * @default "default"
   */
  variant?: "default" | "outline" | "primary" | "secondary" | "accent" | "user" | "ai";

  /**
   * Card elevation level
   * @default "default"
   */
  elevation?: "flat" | "default" | "raised";

  /**
   * Card content
   */
  children?: React.ReactNode;

  /**
   * Card header content
   */
  header?: React.ReactNode;

  /**
   * Card footer content
   */
  footer?: React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Card component variants using CVA
 * Uses shadcn oklch color variables
 */
export const cardVariants = cva(
  "rounded-lg border border-border overflow-hidden",
  {
    variants: {
      variant: {
        default: "bg-card text-card-foreground",
        outline: "bg-background text-foreground border-2",
        primary: "bg-primary text-primary-foreground",
        secondary: "bg-secondary text-secondary-foreground",
        accent: "bg-accent text-accent-foreground",
        user: "bg-accent/10 text-foreground",
        ai: "bg-white border-gray-200 dark:bg-neutral-900 dark:border-neutral-700"
      },
      elevation: {
        flat: "",
        default: "shadow-sm",
        raised: "shadow-md"
      }
    },
    defaultVariants: {
      variant: "default",
      elevation: "default"
    }
  }
);

/**
 * Card header variants
 */
export const cardHeaderVariants = cva(
  "flex flex-col space-y-1.5 p-4",
  {
    variants: {
      variant: {
        default: "",
        outline: "",
        primary: "",
        secondary: "",
        accent: "",
        user: "",
        ai: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Card content variants
 */
export const cardContentVariants = cva(
  "p-4 pt-0",
  {
    variants: {
      variant: {
        default: "",
        outline: "",
        primary: "",
        secondary: "",
        accent: "",
        user: "",
        ai: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Card footer variants
 */
export const cardFooterVariants = cva(
  "flex items-center p-4 pt-0",
  {
    variants: {
      variant: {
        default: "",
        outline: "",
        primary: "",
        secondary: "",
        accent: "",
        user: "",
        ai: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
); 