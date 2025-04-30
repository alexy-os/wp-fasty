import * as React from "react";
import {
  AvatarRoot,
  AvatarImage,
  AvatarFallback,
  type AvatarRootProps,
  type AvatarImageProps,
  type AvatarFallbackProps,
} from "@ui-factory/ui-headless/avatar";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:root
const rootStyles = tv({
  base: "relative flex shrink-0 overflow-hidden rounded-full",
  variants: {
    size: {
      sm: "size-6",
      md: "size-8",
      lg: "size-12",
      xl: "size-16",
    },
    variant: {
      circle: "rounded-full",
      square: "rounded-md"
    }
  },
  defaultVariants: {
    size: "md",
    variant: "circle"
  },
});

// @component:image
const imageStyles = tv({
  base: "aspect-square size-full object-cover",
  variants: {}
});

// @component:fallback
const fallbackStyles = tv({
  base: "flex size-full items-center justify-center rounded-full bg-muted text-muted-foreground",
  variants: {
    size: {
      sm: "text-xs",
      md: "text-sm",
      lg: "text-base",
      xl: "text-lg",
    }
  },
  defaultVariants: {
    size: "md"
  }
});

// Context for sharing size prop
const AvatarContext = React.createContext<{ size?: "sm" | "md" | "lg" | "xl" }>({});

// Extended interfaces
interface AvatarProps extends AvatarRootProps {
  size?: "sm" | "md" | "lg" | "xl";
  variant?: "circle" | "square";
}

// Components
const Avatar = React.forwardRef<HTMLDivElement, AvatarProps>(
  ({ size, variant, className, children, ...props }, ref) => (
    <AvatarContext.Provider value={{ size }}>
      <AvatarRoot
        ref={ref}
        className={twMerge(rootStyles({ size, variant }), className)}
        {...props}
      >
        {children}
      </AvatarRoot>
    </AvatarContext.Provider>
  )
);

const AvatarImg = React.forwardRef<HTMLImageElement, AvatarImageProps>(
  ({ className, ...props }, ref) => (
    <AvatarImage
      ref={ref}
      className={twMerge(imageStyles(), className)}
      {...props}
    />
  )
);

const AvatarPlaceholder = React.forwardRef<HTMLDivElement, AvatarFallbackProps>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(AvatarContext);
    
    return (
      <AvatarFallback
        ref={ref}
        className={twMerge(fallbackStyles({ size }), className)}
        {...props}
      />
    );
  }
);

// Setting display names
Avatar.displayName = "Avatar";
AvatarImg.displayName = "AvatarImg";
AvatarPlaceholder.displayName = "AvatarPlaceholder";

export { Avatar, AvatarImg, AvatarPlaceholder }; 