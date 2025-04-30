import { cva } from 'class-variance-authority';

export type HeroProps = {
  variant?: "default" | "centered" | "full-width" | "gradient" | "muted";
  size?: "sm" | "default" | "lg" | "xl";
  title?: string;
  subtitle?: string;
  description?: string;
  imageUrl?: string;
  backgroundImage?: string;
  accentColor?: "primary" | "secondary" | "accent" | "muted";
};

export const heroVariants = cva(
  "w-full relative overflow-hidden",
  {
    variants: {
      variant: {
        default: "w-full bg-background relative overflow-hidden",
        centered: "w-full bg-background text-center relative overflow-hidden",
        "full-width": "w-full bg-background relative overflow-hidden",
        gradient: "w-full bg-muted relative overflow-hidden",
        muted: "w-full py-24 md:py-32 lg:py-40 bg-muted relative overflow-hidden",
      },
      size: {
        sm: "py-16",
        default: "py-24",
        lg: "py-24 md:py-32",
        xl: "py-24 md:py-32 lg:py-40",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
); 