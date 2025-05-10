import { cva } from 'class-variance-authority';

/**
 * List component props interface
 * Follows 8px design system and shadcn color palette
 */
export type ListProps = {
  /**
   * List style variant
   * @default "default"
   */
  variant?: "default" | "disc" | "decimal" | "none";

  /**
   * List spacing variant
   * @default "default"
   */
  spacing?: "compact" | "default" | "relaxed";

  /**
   * List items
   */
  children?: React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * List item props interface
 */
export type ListItemProps = {
  /**
   * List item content
   */
  children?: React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * List component variants using CVA
 */
export const listVariants = cva(
  "text-foreground",
  {
    variants: {
      variant: {
        default: "",
        disc: "list-disc list-outside",
        decimal: "list-decimal list-outside",
        none: "list-none"
      },
      spacing: {
        compact: "space-y-1",
        default: "space-y-1.5",
        relaxed: "space-y-2"
      }
    },
    defaultVariants: {
      variant: "default",
      spacing: "default"
    }
  }
);

/**
 * List item component variants
 */
export const listItemVariants = cva(
  "text-sm",
  {
    variants: {
      variant: {
        default: "",
        disc: "marker:text-muted-foreground",
        decimal: "marker:text-muted-foreground",
        none: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
); 