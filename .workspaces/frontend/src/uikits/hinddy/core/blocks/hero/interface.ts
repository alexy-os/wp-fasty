import { cva } from 'class-variance-authority';

export type HeroProps = {
  variant?: "default" | "centered" | "full-width" | "content" | "gradient" | "dark";
  size?: "sm" | "default" | "lg" | "xl";
  title?: string;
  description?: string;
  titleDataPath?: string;
  descriptionDataPath?: string;
};

export const heroVariants = cva(
  "relative overflow-hidden mb-16",
  {
    variants: {
      variant: {
        default: "bg-background",
        centered: "bg-background text-center",
        "full-width": "bg-background w-full",
        content: "bg-card text-card-foreground",
        gradient: "bg-gradient-to-r from-primary/10 to-primary/5",
        dark: "bg-slate-900 text-white",
      },
      size: {
        sm: "py-8",
        default: "py-16",
        lg: "py-24",
        xl: "py-32",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);
