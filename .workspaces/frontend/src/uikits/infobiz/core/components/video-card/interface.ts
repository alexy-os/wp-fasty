import { cva } from 'class-variance-authority';

export type VideoCardProps = {
  variant?: "default" | "featured";
  title: string;
  description?: string;
  author: string;
  authorImage: string;
  authorUrl: string;
  image: string;
  duration?: string;
  platform?: string;
  views?: string;
  publishedAt?: string;
  url: string;
};

export const videoCardVariants = cva(
  "rounded-lg border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden",
  {
    variants: {
      variant: {
        default: "rounded-lg border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden",
        featured: "rounded-lg border-2 border-primary/30 bg-background text-foreground shadow-md hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
); 