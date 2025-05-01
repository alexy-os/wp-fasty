import { cva } from 'class-variance-authority';

/**
 * Quiz Option component props interface
 * Follows 8px design system and shadcn color palette
 */
export type QuizOptionProps = {
  /**
   * Quiz option visual style variant
   * @default "default"
   */
  variant?: "default" | "outline" | "primary";

  /**
   * Size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Is option selected
   * @default false
   */
  selected?: boolean;

  /**
   * Option content
   */
  children?: React.ReactNode;

  /**
   * Option value
   */
  value?: string;

  /**
   * Click handler
   */
  onClick?: () => void;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Quiz option component variants using CVA
 * Uses shadcn oklch color variables
 */
export const quizOptionVariants = cva(
  "inline-flex justify-center items-center gap-x-2 rounded-lg border align-middle text-sm transition-colors",
  {
    variants: {
      variant: {
        default: "border-blue-600 bg-white text-blue-600 hover:bg-blue-50 focus:outline-hidden focus:bg-blue-50 dark:bg-neutral-900 dark:text-blue-500 dark:border-blue-500 dark:hover:text-blue-400 dark:hover:border-blue-400 dark:focus:text-blue-400 dark:focus:border-blue-400",
        outline: "border-input bg-background hover:bg-accent hover:text-accent-foreground focus:outline-hidden dark:hover:bg-accent/10",
        primary: "border-transparent bg-primary text-primary-foreground hover:bg-primary/90 focus:outline-hidden"
      },
      size: {
        sm: "py-1.5 px-2.5",
        default: "py-2 px-3",
        lg: "py-2.5 px-4"
      },
      selected: {
        true: "",
        false: ""
      }
    },
    compoundVariants: [
      {
        variant: "default",
        selected: true,
        className: "bg-blue-50 dark:bg-blue-900/20"
      },
      {
        variant: "outline",
        selected: true,
        className: "bg-accent text-accent-foreground dark:bg-accent/20"
      },
      {
        variant: "primary",
        selected: true,
        className: "bg-primary/80 text-primary-foreground"
      }
    ],
    defaultVariants: {
      variant: "default",
      size: "default",
      selected: false
    }
  }
); 