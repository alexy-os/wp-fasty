import { cva } from 'class-variance-authority';

/**
 * Avatar component props interface
 * Follows 8px design system
 */
export type AvatarProps = {
  /**
   * Avatar size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg" | "xl";

  /**
   * Avatar display type
   * @default "image"
   */
  variant?: "image" | "initials";

  /**
   * Image source for image variant
   */
  src?: string;

  /**
   * Alt text for image
   */
  alt?: string;

  /**
   * Initials to display (max 2 characters)
   */
  initials?: string;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * Avatar component variants using CVA
 */
export const avatarVariants = cva(
  "inline-flex items-center justify-center rounded-full overflow-hidden bg-muted text-muted-foreground",
  {
    variants: {
      size: {
        sm: "w-6 h-6 text-xs",
        default: "w-8 h-8 text-sm",
        lg: "w-10 h-10 text-base",
        xl: "w-12 h-12 text-lg"
      }
    },
    defaultVariants: {
      size: "default"
    }
  }
); 