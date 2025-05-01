import { cva } from 'class-variance-authority';

/**
 * Chat Input component props interface
 * Follows 8px design system and shadcn color palette
 */
export type ChatInputProps = {
  /**
   * Chat input visual style variant
   * @default "default"
   */
  variant?: "default" | "bordered" | "minimal";

  /**
   * Placeholder text
   * @default "Type a message..."
   */
  placeholder?: string;

  /**
   * Input value
   */
  value?: string;

  /**
   * Input change handler
   */
  onChange?: (value: string) => void;

  /**
   * Send handler
   */
  onSend?: () => void;

  /**
   * Show mic button
   * @default true
   */
  showMicButton?: boolean;

  /**
   * Show attachment button
   * @default true
   */
  showAttachmentButton?: boolean;

  /**
   * Mic button click handler
   */
  onMicClick?: () => void;

  /**
   * Attachment button click handler
   */
  onAttachmentClick?: () => void;

  /**
   * Is input disabled
   * @default false
   */
  disabled?: boolean;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Chat input component variants using CVA
 * Uses shadcn oklch color variables
 */
export const chatInputVariants = cva(
  "relative",
  {
    variants: {
      variant: {
        default: "",
        bordered: "border-t border-border",
        minimal: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat input textarea variants
 */
export const chatInputTextareaVariants = cva(
  "p-3 sm:p-4 pb-12 sm:pb-12 block w-full rounded-lg text-sm focus:border-primary focus:ring-primary disabled:opacity-50 disabled:pointer-events-none resize-none",
  {
    variants: {
      variant: {
        default: "border-border bg-background dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600",
        bordered: "border-border bg-background dark:bg-neutral-900 dark:border-neutral-700 dark:text-neutral-400 dark:placeholder-neutral-500 dark:focus:ring-neutral-600",
        minimal: "border-transparent bg-muted dark:bg-neutral-800 dark:border-transparent"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat input toolbar variants
 */
export const chatInputToolbarVariants = cva(
  "absolute bottom-px inset-x-px p-2 rounded-b-lg",
  {
    variants: {
      variant: {
        default: "bg-background dark:bg-neutral-900",
        bordered: "bg-background dark:bg-neutral-900",
        minimal: "bg-muted dark:bg-neutral-800"
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
); 