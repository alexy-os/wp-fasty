import React, { createContext, useContext } from 'react';
import { CardProps } from './interface';

// Create a context to pass card settings to child components
const CardContext = createContext<{ variant: CardProps['variant']; elevation: CardProps['elevation'] }>({
  variant: 'default',
  elevation: 'default'
});

/**
 * Card component with semantic class names
 * Follows the 8px design system
 */
export const Card: React.FC<Omit<CardProps, 'partial'> & {
  children: React.ReactNode
}> = ({
  variant = "default",
  elevation = "default",
  children,
  className = "",
}) => {
    return (
      <CardContext.Provider value={{ variant, elevation }}>
        <div
          className={`card card-${variant} ${elevation !== "default" ? `card-${elevation}` : ""} ${className}`}
        >
          {children}
        </div>
      </CardContext.Provider>
    );
  };

/**
 * Card Header component
 */
export const CardHeader: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({ children, className = "" }) => {
  const { variant } = useContext(CardContext);
  return (
    <div className={`card-header card-${variant} ${className}`}>
      {children}
    </div>
  );
};

/**
 * Card Content component
 */
export const CardContent: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({ children, className = "" }) => {
  const { variant } = useContext(CardContext);
  return (
    <div className={`card-content card-${variant} ${className}`}>
      {children}
    </div>
  );
};

/**
 * Card Footer component
 */
export const CardFooter: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({ children, className = "" }) => {
  const { variant } = useContext(CardContext);
  return (
    <div className={`card-footer card-${variant} ${className}`}>
      {children}
    </div>
  );
};

/**
 * Card Title component
 */
export const CardTitle: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({ children, className = "" }) => {
  return (
    <h3 className={`card-title ${className}`}>
      {children}
    </h3>
  );
};

/**
 * Card Description component
 */
export const CardDescription: React.FC<{
  children: React.ReactNode;
  className?: string;
}> = ({ children, className = "" }) => {
  return (
    <p className={`card-description ${className}`}>
      {children}
    </p>
  );
}; 