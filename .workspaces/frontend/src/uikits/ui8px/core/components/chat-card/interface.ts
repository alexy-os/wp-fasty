import { cva } from 'class-variance-authority';

/**
 * Chat Card component props interface
 * Follows 8px design system and shadcn color palette
 */
export type ChatCardProps = {
  /**
   * Card visual style variant
   * @default "default"
   */
  variant?: "default" | "primary" | "code" | "file" | "table";

  /**
   * Card content
   */
  children?: React.ReactNode;

  /**
   * Card title
   */
  title?: React.ReactNode;

  /**
   * Card actions
   */
  actions?: React.ReactNode;

  /**
   * Language for code variant
   * Only applicable for code variant
   */
  language?: string;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Chat card component variants using CVA
 * Uses shadcn oklch color variables
 */
export const chatCardVariants = cva(
  "inline-block rounded-lg border overflow-hidden",
  {
    variants: {
      variant: {
        default: "bg-card border-border text-card-foreground dark:bg-neutral-900 dark:border-neutral-700",
        primary: "bg-primary text-primary-foreground border-transparent",
        code: "bg-muted text-muted-foreground font-mono border-border dark:bg-neutral-800 dark:border-neutral-700",
        file: "bg-card border-border text-card-foreground dark:bg-neutral-900 dark:border-neutral-700",
        table: "bg-card border-border text-card-foreground dark:bg-neutral-900 dark:border-neutral-700 w-full"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat card header variants
 */
export const chatCardHeaderVariants = cva(
  "p-4",
  {
    variants: {
      variant: {
        default: "border-b border-border dark:border-neutral-700",
        primary: "border-b border-primary-foreground/10",
        code: "border-b border-border dark:border-neutral-700 bg-muted-foreground/5",
        file: "border-b border-border dark:border-neutral-700 bg-muted-foreground/5",
        table: "bg-muted dark:bg-neutral-800 text-foreground"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat card content variants
 */
export const chatCardContentVariants = cva(
  "p-4 space-y-3",
  {
    variants: {
      variant: {
        default: "",
        primary: "",
        code: "p-0",
        file: "",
        table: "p-0 overflow-x-auto"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat card footer variants
 */
export const chatCardFooterVariants = cva(
  "border-t p-3 flex justify-between items-center",
  {
    variants: {
      variant: {
        default: "border-border bg-muted/40 dark:border-neutral-700 dark:bg-neutral-800/40",
        primary: "border-primary-foreground/10 bg-primary-foreground/5",
        code: "border-border bg-muted-foreground/5 dark:border-neutral-700",
        file: "border-border bg-muted/40 dark:border-neutral-700 dark:bg-neutral-800/40",
        table: "border-border bg-muted/40 dark:border-neutral-700 dark:bg-neutral-800/40"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
); 