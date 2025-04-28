import { cva } from 'class-variance-authority';

export type CardProps = {
  variant?: "default" | "outline" | "destructive";
  size?: "sm" | "default" | "lg";
  title?: string;
  description?: string;
  badges?: { text: string; variant?: string }[];
};

export const cardVariants = cva(
  "bg-card text-card-foreground rounded-xl border shadow",
  {
    variants: {
      variant: {
        default: "",
        outline: "bg-transparent border-2",
        destructive: "bg-destructive text-destructive-foreground",
      },
      size: {
        sm: "p-4",
        default: "p-6",
        lg: "p-8",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
);

export const CardVariantsConfig = {
  componentName: 'card',
  inputPath: './src/components/ui/card/interface.ts',
  outputPath: './src/components/ui/card/Card.css',
};
