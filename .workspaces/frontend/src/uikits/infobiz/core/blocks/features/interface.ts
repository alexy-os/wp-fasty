import { cva } from 'class-variance-authority';

export type FeaturesProps = {
  variant?: "default" | "muted" | "split" | "grid";
  title?: string;
  description?: string;
  features?: Array<{
    title: string;
    description: string;
    icon?: string;
    iconColor?: string;
  }>;
};

export const featuresVariants = cva(
  "w-full py-24 md:py-32",
  {
    variants: {
      variant: {
        default: "w-full py-24 md:py-32 bg-background",
        muted: "w-full py-24 md:py-32 bg-muted",
        split: "w-full py-24 md:py-32 bg-background",
        grid: "w-full py-24 md:py-32 bg-background",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
); 