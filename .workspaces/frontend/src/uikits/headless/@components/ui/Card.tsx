import * as React from "react";
import {
  CardRoot,
  CardHeader,
  CardFooter,
  CardContent,
  CardTitle,
  CardDescription,
  CardAction,
  type CardRootProps,
} from "@ui-factory/ui-headless/card";
import { tv } from "tailwind-variants";
import { twMerge } from "tailwind-merge";

// @component:root
const rootStyles = tv({
  base: "flex flex-col rounded-xl border py-6 bg-card text-card-foreground shadow-sm",
  variants: {
    variant: {
      default: "",
      outline: "border-2",
      ghost: "border-none shadow-none",
    },
    size: {
      sm: "gap-4 py-4",
      md: "gap-6 py-6",
      lg: "gap-8 py-8",
    }
  },
  defaultVariants: {
    variant: "default",
    size: "md"
  }
});

// @component:header
const headerStyles = tv({
  base: "grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 has-[data-action]:grid-cols-[1fr_auto]",
  variants: {
    size: {
      sm: "px-4",
      md: "px-6",
      lg: "px-8"
    },
    border: {
      true: "pb-6"
    }
  }
});

// @component:title
const titleStyles = tv({
  base: "font-semibold leading-none"
});

// @component:description
const descriptionStyles = tv({
  base: "text-sm text-muted-foreground"
});

// @component:action
const actionStyles = tv({
  base: "col-start-2 row-span-2 row-start-1 self-start justify-self-end"
});

// @component:content
const contentStyles = tv({
  base: "",
  variants: {
    size: {
      sm: "px-4",
      md: "px-6",
      lg: "px-8"
    }
  }
});

// @component:footer
const footerStyles = tv({
  base: "flex items-center",
  variants: {
    size: {
      sm: "px-4",
      md: "px-6",
      lg: "px-8"
    },
    border: {
      true: "pt-6"
    }
  }
});

// Context for sharing size prop
const CardContext = React.createContext<{ size?: "sm" | "md" | "lg" }>({});

interface CardProps extends CardRootProps {
  variant?: "default" | "outline" | "ghost";
  size?: "sm" | "md" | "lg";
}

const Card = React.forwardRef<HTMLDivElement, CardProps>(
  ({ variant, size = "md", className, children, ...props }, ref) => (
    <CardContext.Provider value={{ size }}>
      <CardRoot
        ref={ref}
        className={twMerge(rootStyles({ variant, size }), className)}
        {...props}
      >
        {children}
      </CardRoot>
    </CardContext.Provider>
  )
);

const CardHead = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(CardContext);
    const hasBorder = className?.includes("border-b");
    
    return (
      <CardHeader
        ref={ref}
        className={twMerge(headerStyles({ size, border: hasBorder }), className)}
        {...props}
      />
    );
  }
);

const CardHeading = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <CardTitle
      ref={ref}
      className={twMerge(titleStyles(), className)}
      {...props}
    />
  )
);

const CardText = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <CardDescription
      ref={ref}
      className={twMerge(descriptionStyles(), className)}
      {...props}
    />
  )
);

const CardActions = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => (
    <CardAction
      ref={ref}
      data-action
      className={twMerge(actionStyles(), className)}
      {...props}
    />
  )
);

const CardBody = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(CardContext);
    return (
      <CardContent
        ref={ref}
        className={twMerge(contentStyles({ size }), className)}
        {...props}
      />
    );
  }
);

const CardFoot = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(
  ({ className, ...props }, ref) => {
    const { size } = React.useContext(CardContext);
    const hasBorder = className?.includes("border-t");
    
    return (
      <CardFooter
        ref={ref}
        className={twMerge(footerStyles({ size, border: hasBorder }), className)}
        {...props}
      />
    );
  }
);

Card.displayName = "Card";
CardHead.displayName = "CardHead";
CardHeading.displayName = "CardHeading";
CardText.displayName = "CardText";
CardActions.displayName = "CardActions";
CardBody.displayName = "CardBody";
CardFoot.displayName = "CardFoot";

export {
  Card,
  CardHead,
  CardHeading,
  CardText,
  CardActions,
  CardBody,
  CardFoot,
  type CardProps
};