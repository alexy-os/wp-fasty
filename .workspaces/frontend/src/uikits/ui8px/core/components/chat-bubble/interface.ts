import { cva } from 'class-variance-authority';

/**
 * Chat bubble component props interface
 * Follows 8px design system and shadcn color palette
 */
export type ChatBubbleProps = {
  /**
   * Sender type to determine style
   * @default "user"
   */
  sender?: "user" | "ai";

  /**
   * Content of the bubble
   */
  children?: React.ReactNode;

  /**
   * User avatar content
   */
  avatar?: React.ReactNode;

  /**
   * Optional actions for the bubble
   */
  actions?: React.ReactNode;

  /**
   * Is this message part of a thread
   * @default false
   */
  isThread?: boolean;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Chat bubble component variants using CVA
 * Uses shadcn oklch color variables
 */
export const chatBubbleVariants = cva(
  "flex gap-x-2 sm:gap-x-4",
  {
    variants: {
      sender: {
        user: "max-w-2xl ms-auto flex justify-end",
        ai: ""
      }
    },
    defaultVariants: {
      sender: "user"
    }
  }
);

/**
 * Chat bubble content variants
 */
export const chatBubbleContentVariants = cva(
  "inline-block rounded-lg p-4",
  {
    variants: {
      sender: {
        user: "bg-primary text-primary-foreground shadow-sm",
        ai: "bg-card border border-border text-card-foreground dark:bg-neutral-900 dark:border-neutral-700"
      }
    },
    defaultVariants: {
      sender: "user"
    }
  }
);

/**
 * Chat bubble container variants
 */
export const chatBubbleContainerVariants = cva(
  "space-y-3",
  {
    variants: {
      sender: {
        user: "grow text-end",
        ai: "grow max-w-[90%] md:max-w-2xl w-full"
      }
    },
    defaultVariants: {
      sender: "user"
    }
  }
); 