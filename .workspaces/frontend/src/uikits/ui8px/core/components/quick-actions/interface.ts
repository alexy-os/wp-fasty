import { cva } from 'class-variance-authority';

/**
 * Action item props interface
 */
export type ActionItem = {
  /**
   * Action identifier
   */
  id: string;

  /**
   * Display label
   */
  label: string;

  /**
   * Optional icon component
   */
  icon?: React.ReactNode;

  /**
   * Is this action disabled
   * @default false
   */
  disabled?: boolean;
};

/**
 * Quick Actions component props interface
 * Follows 8px design system and shadcn color palette
 */
export type QuickActionsProps = {
  /**
   * Actions layout variant
   * @default "horizontal"
   */
  variant?: "horizontal" | "vertical" | "inline";

  /**
   * Size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Visual style variant
   * @default "default"
   */
  style?: "default" | "outline" | "ghost";

  /**
   * List of action items
   */
  actions: ActionItem[];

  /**
   * Click handler
   */
  onActionClick?: (actionId: string) => void;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Quick actions component variants using CVA
 * Uses shadcn oklch color variables
 */
export const quickActionsVariants = cva(
  "flex",
  {
    variants: {
      variant: {
        horizontal: "flex-row items-center flex-wrap gap-1.5",
        vertical: "flex-col items-start gap-1",
        inline: "flex-row items-center"
      }
    },
    defaultVariants: {
      variant: "horizontal"
    }
  }
);

/**
 * Quick action button variants using CVA
 */
export const quickActionButtonVariants = cva(
  "inline-flex items-center gap-x-2 rounded-full border border-transparent transition-colors disabled:opacity-50 disabled:pointer-events-none text-sm",
  {
    variants: {
      style: {
        default: "text-muted-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none focus:bg-accent dark:hover:bg-neutral-800 dark:focus:bg-neutral-800",
        outline: "border-input hover:bg-accent hover:text-accent-foreground focus:outline-none focus:bg-accent dark:hover:bg-neutral-800 dark:focus:bg-neutral-800",
        ghost: "hover:bg-accent hover:text-accent-foreground focus:outline-none focus:bg-accent dark:hover:bg-neutral-800 dark:focus:bg-neutral-800"
      },
      size: {
        sm: "py-1 px-2",
        default: "py-2 px-3",
        lg: "py-2.5 px-4"
      }
    },
    defaultVariants: {
      style: "default",
      size: "default"
    }
  }
); 