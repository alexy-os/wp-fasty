import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

// Context for passing state between card components
type CardContextValue = {
  isCollapsible?: boolean;
  isCollapsed?: boolean;
  onCollapseToggle?: () => void;
};

const CardContext = React.createContext<CardContextValue>({});

// Base interfaces
export interface CardRootProps extends React.HTMLAttributes<HTMLDivElement> {
  asChild?: boolean;
  isCollapsible?: boolean;
  defaultCollapsed?: boolean;
}

export interface CardSectionProps extends React.HTMLAttributes<HTMLDivElement> {
  asChild?: boolean;
}

// Root component
const CardRoot = React.forwardRef<HTMLDivElement, CardRootProps>(
  ({ asChild = false, isCollapsible, defaultCollapsed = false, ...props }, ref) => {
    const [isCollapsed, setIsCollapsed] = React.useState(defaultCollapsed);
    const Comp = asChild ? Slot : "div";
    
    const contextValue = React.useMemo(
      () => ({
        isCollapsible,
        isCollapsed,
        onCollapseToggle: () => setIsCollapsed(prev => !prev),
      }),
      [isCollapsible, isCollapsed]
    );

    return (
      <CardContext.Provider value={contextValue}>
        <Comp ref={ref} {...props} />
      </CardContext.Provider>
    );
  }
);

// Components of sections
const createCardSection = (displayName: string) => {
  displayName = `Card${displayName}`;
  return React.forwardRef<HTMLDivElement, CardSectionProps>(
    ({ asChild = false, ...props }, ref) => {
      const Comp = asChild ? Slot : "div";
      return <Comp ref={ref} {...props} />;
    }
  );
};

const CardHeader = createCardSection("CardHeader");
const CardFooter = createCardSection("CardFooter");
const CardContent = createCardSection("CardContent");
const CardTitle = createCardSection("CardTitle");
const CardDescription = createCardSection("CardDescription");

// Special component for actions
const CardAction = React.forwardRef<HTMLDivElement, CardSectionProps>(
  ({ asChild = false, ...props }, ref) => {
    const { isCollapsible, onCollapseToggle } = React.useContext(CardContext);
    const Comp = asChild ? Slot : "div";

    return (
      <Comp 
        ref={ref} 
        onClick={(e) => {
          if (isCollapsible) {
            e.stopPropagation();
            onCollapseToggle?.();
          }
        }}
        {...props} 
      />
    );
  }
);

export {
  CardRoot,
  CardHeader,
  CardFooter,
  CardContent,
  CardTitle,
  CardDescription,
  CardAction,
};

