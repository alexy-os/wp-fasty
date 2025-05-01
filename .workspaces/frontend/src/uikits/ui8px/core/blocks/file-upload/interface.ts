import { cva } from 'class-variance-authority';

/**
 * File item props
 */
export type FileItem = {
  /**
   * Unique file ID
   */
  id: string;

  /**
   * File name
   */
  name: string;

  /**
   * File size in bytes
   */
  size: number;

  /**
   * File type/MIME type
   */
  type: string;

  /**
   * Upload progress (0-100)
   */
  progress?: number;

  /**
   * Error message
   */
  error?: string;

  /**
   * Is file uploaded successfully
   */
  uploaded?: boolean;
};

/**
 * File Upload component props interface
 * Follows 8px design system and shadcn color palette
 */
export type FileUploadProps = {
  /**
   * Upload area visual style variant
   * @default "default"
   */
  variant?: "default" | "outlined" | "minimal";

  /**
   * Upload area size variant
   * @default "default"
   */
  size?: "sm" | "default" | "lg";

  /**
   * Upload area label
   * @default "Drop files here or click to upload"
   */
  label?: React.ReactNode;

  /**
   * Upload area description
   */
  description?: React.ReactNode;

  /**
   * Accepted file types
   */
  accept?: string;

  /**
   * Maximum file size in bytes
   */
  maxSize?: number;

  /**
   * Multiple files allowed
   * @default false
   */
  multiple?: boolean;

  /**
   * Current files
   */
  files?: FileItem[];

  /**
   * File change handler
   */
  onFilesChange?: (files: FileItem[]) => void;

  /**
   * Is component disabled
   * @default false
   */
  disabled?: boolean;

  /**
   * Additional CSS classes
   */
  className?: string;
};

/**
 * File upload component variants using CVA
 * Uses shadcn oklch color variables
 */
export const fileUploadVariants = cva(
  "flex flex-col items-center justify-center rounded-lg",
  {
    variants: {
      variant: {
        default: "bg-muted/50 hover:bg-muted dark:bg-muted/30 dark:hover:bg-muted/50",
        outlined: "border-2 border-dashed border-muted-foreground/30 hover:border-muted-foreground/50 dark:border-muted-foreground/20 dark:hover:border-muted-foreground/30",
        minimal: "bg-transparent hover:bg-muted/30 dark:hover:bg-muted/20"
      },
      size: {
        sm: "p-4 gap-1",
        default: "p-6 gap-2",
        lg: "p-8 gap-3"
      }
    },
    defaultVariants: {
      variant: "default",
      size: "default"
    }
  }
);

/**
 * File item component variants
 */
export const fileItemVariants = cva(
  "flex items-center justify-between p-3 rounded-md",
  {
    variants: {
      status: {
        uploading: "bg-muted/50 border border-border",
        error: "bg-destructive/10 border border-destructive/30 dark:bg-destructive/20",
        success: "bg-muted/70 border border-border"
      }
    },
    defaultVariants: {
      status: "uploading"
    }
  }
); 