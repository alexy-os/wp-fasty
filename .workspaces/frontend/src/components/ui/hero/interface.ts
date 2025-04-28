import { cva } from 'class-variance-authority';

export type HeroProps = {
  variant?: "default" | "centered" | "full-width";
  size?: "sm" | "default" | "lg";
  title?: string;
  description?: string;
  titleDataPath?: string;
  descriptionDataPath?: string;
};

export const heroVariants = cva(
  "relative overflow-hidden",
  {
    variants: {
      variant: {
        default: "bg-background",
        centered: "bg-background text-center",
        "full-width": "bg-background w-full",
      },
      size: {
        sm: "py-8",
        default: "py-16",
        lg: "py-24",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);
