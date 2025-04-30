import { cva } from 'class-variance-authority';

export type CardProps = {
  variant?: "default" | "hover" | "interactive" | "gradient" | "outline";
  elevation?: "flat" | "sm" | "md" | "lg" | "xl";
  radius?: "none" | "sm" | "default" | "lg" | "xl";
  hover?: boolean;
};

export const cardVariants = cva(
  "relative border border-border bg-background text-foreground",
  {
    variants: {
      variant: {
        default: "border border-border bg-background text-foreground shadow-sm",
        hover: "border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1",
        interactive: "border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 cursor-pointer",
        gradient: "border-transparent bg-gradient-to-br from-primary/10 via-transparent to-transparent",
        outline: "border-2 border-border bg-transparent",
      },
      elevation: {
        flat: "shadow-none",
        sm: "shadow-sm",
        md: "shadow-md",
        lg: "shadow-lg",
        xl: "shadow-xl",
      },
      radius: {
        none: "rounded-none",
        sm: "rounded-md",
        default: "rounded-lg",
        lg: "rounded-xl",
        xl: "rounded-2xl",
      },
    },
    defaultVariants: {
      variant: "default",
      elevation: "sm",
      radius: "lg",
    },
  }
); 