import * as React from "react";
import { Slot } from "@ui-factory/ui-headless/slot";

export interface AvatarRootProps extends React.HTMLAttributes<HTMLDivElement> {
  asChild?: boolean;
}

export interface AvatarImageProps extends React.ImgHTMLAttributes<HTMLImageElement> {
  asChild?: boolean;
  onLoadingStatusChange?: (status: 'idle' | 'loading' | 'loaded' | 'error') => void;
}

export interface AvatarFallbackProps extends React.HTMLAttributes<HTMLDivElement> {
  asChild?: boolean;
  delayMs?: number;
}

const AvatarRoot = React.forwardRef<HTMLDivElement, AvatarRootProps>(
  ({ asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : "div";
    return <Comp ref={ref} {...props} />;
  }
);

const AvatarImage = React.forwardRef<HTMLImageElement, AvatarImageProps>(
  ({ asChild = false, onLoadingStatusChange, ...props }, ref) => {
    const Comp = asChild ? Slot : "img";
    
    React.useEffect(() => {
      onLoadingStatusChange?.('loading');
    }, []);

    return (
      <Comp
        ref={ref}
        onLoad={() => onLoadingStatusChange?.('loaded')}
        onError={() => onLoadingStatusChange?.('error')}
        {...props}
      />
    );
  }
);

const AvatarFallback = React.forwardRef<HTMLDivElement, AvatarFallbackProps>(
  ({ asChild = false, delayMs = 600, ...props }, ref) => {
    const [isShown, setIsShown] = React.useState(false);
    
    React.useEffect(() => {
      const timeout = setTimeout(() => setIsShown(true), delayMs);
      return () => clearTimeout(timeout);
    }, [delayMs]);

    if (!isShown) return null;
    
    const Comp = asChild ? Slot : "div";
    return <Comp ref={ref} {...props} />;
  }
);

export { AvatarRoot, AvatarImage, AvatarFallback }; 