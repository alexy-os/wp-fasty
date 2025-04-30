import { cva } from 'class-variance-authority';

export type BusinessmanCardProps = {
  variant?: "default" | "featured" | "compact";
  rank?: number;
  name: string;
  category: string;
  revenue: string;
  subscribers: string;
  tags: string[];
  image: string;
  socialLinks?: Record<string, string>;
  profileUrl: string;
};

export const businessmanCardVariants = cva(
  "group rounded-xl border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden",
  {
    variants: {
      variant: {
        default: "group rounded-xl border border-border bg-background text-foreground shadow-sm hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden",
        featured: "group rounded-xl border-2 border-primary/30 bg-background text-foreground shadow-md hover:shadow-xl transition-all duration-100 ease-in-out transform hover:-translate-y-1 overflow-hidden",
        compact: "group rounded-lg border border-border bg-background text-foreground shadow-sm hover:shadow-md transition-all duration-100 ease-in-out transform hover:-translate-y-0.5 overflow-hidden",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
); 