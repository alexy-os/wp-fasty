import { cva } from 'class-variance-authority';

export type CardProps = {
  variant?: "default" | "outline" | "destructive" | "primary" | "secondary" | "ghost" | "gradient";
  size?: "sm" | "default" | "lg" | "xl";
  title?: string;
  description?: string;
  badges?: { text: string; variant?: string }[];
  elevation?: "flat" | "sm" | "md" | "lg";
  radius?: "none" | "sm" | "default" | "lg" | "full";
};

export const cardVariants = cva(
  "bg-card text-card-foreground rounded-xl border shadow",
  {
    variants: {
      variant: {
        default: "bg-card text-card-foreground border shadow-sm",
        outline: "bg-transparent border-2",
        destructive: "bg-destructive text-destructive-foreground",
        primary: "bg-primary text-primary-foreground",
        secondary: "bg-secondary text-secondary-foreground",
        ghost: "bg-transparent border-0 shadow-none",
        gradient: "bg-gradient-to-br from-primary to-primary/70 text-primary-foreground border-none",
      },
      size: {
        sm: "p-4",
        default: "p-6",
        lg: "p-8",
        xl: "p-10",
      },
      elevation: {
        flat: "shadow-none",
        sm: "shadow-sm",
        md: "shadow-md",
        lg: "shadow-lg",
      },
      radius: {
        none: "rounded-none",
        sm: "rounded-sm",
        default: "rounded-xl",
        lg: "rounded-2xl",
        full: "rounded-3xl",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
      elevation: "sm",
      radius: "default",
    },
  }
);
