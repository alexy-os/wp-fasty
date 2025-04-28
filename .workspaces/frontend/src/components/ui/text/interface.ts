import { cva } from 'class-variance-authority';

export type TextProps = {
  variant?: "default" | "title" | "subtitle" | "muted" | "error";
  size?: "xs" | "sm" | "default" | "lg" | "xl";
  weight?: "normal" | "medium" | "bold";
};

export const textVariants = cva(
  "text-foreground", // базовые стили
  {
    variants: {
      variant: {
        default: "",
        title: "font-heading",
        subtitle: "text-muted-foreground",
        muted: "text-muted-foreground",
        error: "text-destructive",
      },
      size: {
        xs: "text-xs",
        sm: "text-sm",
        default: "text-base",
        lg: "text-lg",
        xl: "text-xl",
      },
      weight: {
        normal: "font-normal",
        medium: "font-medium",
        bold: "font-bold",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
      weight: "normal",
    },
  }
);

export const TextVariantsConfig = {
  componentName: 'text',
  // ... остальная конфигурация
};

