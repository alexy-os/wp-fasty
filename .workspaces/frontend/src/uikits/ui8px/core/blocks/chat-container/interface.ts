import { cva } from 'class-variance-authority';

/**
 * Chat message type
 */
export type ChatMessage = {
  /**
   * Unique message ID
   */
  id: string;

  /**
   * Message sender
   */
  sender: "user" | "ai";

  /**
   * Message content
   */
  content: React.ReactNode;

  /**
   * Optional avatar content
   */
  avatar?: React.ReactNode;

  /**
   * Optional message actions
   */
  actions?: React.ReactNode;

  /**
   * Is message part of a thread
   * @default false
   */
  isThread?: boolean;

  /**
   * Timestamp of the message
   */
  timestamp?: string | Date;
};

/**
 * Chat Container component props interface
 * Follows 8px design system and shadcn color palette
 */
export type ChatContainerProps = {
  /**
   * Container layout variant
   * @default "default"
   */
  variant?: "default" | "bordered" | "minimal";

  /**
   * Chat messages
   */
  messages: ChatMessage[];

  /**
   * Loading state indicator
   * @default false
   */
  isLoading?: boolean;

  /**
   * Error message
   */
  error?: string;

  /**
   * Chat input component
   */
  inputComponent?: React.ReactNode;

  /**
   * Welcome component
   */
  welcomeComponent?: React.ReactNode;

  /**
   * Custom message renderer
   */
  renderMessage?: (message: ChatMessage) => React.ReactNode;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Chat container component variants using CVA
 * Uses shadcn oklch color variables
 */
export const chatContainerVariants = cva(
  "relative flex flex-col h-full",
  {
    variants: {
      variant: {
        default: "",
        bordered: "border border-border rounded-lg shadow-sm",
        minimal: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat messages container variants
 */
export const chatMessagesVariants = cva(
  "flex-1 overflow-y-auto px-4 py-6 space-y-5",
  {
    variants: {
      variant: {
        default: "",
        bordered: "",
        minimal: ""
      }
    },
    defaultVariants: {
      variant: "default"
    }
  }
);

/**
 * Chat input container variants
 */
export const chatInputContainerVariants = cva(
  "sticky bottom-0 z-10 bg-background px-4 pt-2 pb-4 sm:pt-4 sm:pb-6",
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