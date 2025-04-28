import { cva } from 'class-variance-authority';

export type CardProps = {
  variant?: "default" | "outline" | "primary" | "secondary";
  size?: "default" | "sm" | "lg";
  title?: string;
  description?: string;
  badges?: { text: string; variant?: string }[];
};

export const cardVariants = cva(
  "card",
  {
    variants: {
      variant: {
        default: "",
        outline: "",
        primary: "",
        secondary: "",
      },
      size: {
        default: "",
        sm: "",
        lg: "",
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
