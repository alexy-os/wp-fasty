import React from 'react';
import { CardProps } from './interface';

/**
 * Card component with semantic class names
 * Follows the 8px design system
 */
export const Card: React.FC<CardProps> = ({
  variant = "default",
  elevation = "default",
  children,
  header,
  footer,
  className = "",
}) => {
  return (
    <div
      className={`card card-${variant} ${elevation !== "default" ? `card-${elevation}` : ""} ${className}`}
    >
      {header && (
        <div className={`card-header card-header-${variant}`}>
          {header}
        </div>
      )}
      <div className={`card-content card-content-${variant}`}>
        {children}
      </div>
      {footer && (
        <div className={`card-footer card-footer-${variant}`}>
          {footer}
        </div>
      )}
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
    <h3 className={`card-title text-base font-medium ${className}`}>
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
    <p className={`card-description text-sm text-muted-foreground ${className}`}>
      {children}
    </p>
  );
}; 