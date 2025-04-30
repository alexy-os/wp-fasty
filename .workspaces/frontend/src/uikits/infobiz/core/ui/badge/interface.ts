import { cva } from 'class-variance-authority';

export type BadgeProps = {
  variant?: "default" | "primary" | "secondary" | "outline" | "tag";
  size?: "sm" | "default" | "lg";
};

export const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors",
  {
    variants: {
      variant: {
        default: "bg-accent text-accent-foreground hover:bg-accent/80",
        primary: "bg-primary/10 text-primary hover:bg-primary hover:text-primary-foreground",
        secondary: "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        outline: "border border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground",
        tag: "bg-accent px-2.5 py-0.5 text-xs font-medium text-accent-foreground transition-colors hover:bg-accent/80",
      },
      size: {
        default: "h-6",
        sm: "h-5 text-[10px]",
        lg: "h-7 px-3 text-sm",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
); 