import React from 'react';
import { ListProps, ListItemProps } from './interface';

/**
 * List component with semantic class names
 * Follows the 8px design system
 */
export const List: React.FC<ListProps> = ({
  variant = "default",
  spacing = "default",
  className = "",
  children,
}) => {
  // Determine the HTML element based on the variant
  const Component = variant === "disc" || variant === "decimal" ? "ul" : "div";

  return (
    <Component
      className={`list list-${variant} ${spacing !== "default" ? `list-${spacing}` : ""} ${className}`}
    >
      {children}
    </Component>
  );
};

/**
 * List item component
 */
export const ListItem: React.FC<ListItemProps & { variant?: ListProps["variant"] }> = ({
  variant = "default",
  className = "",
  children,
}) => {
  // Determine the HTML element based on the list variant
  const Component = variant === "disc" || variant === "decimal" ? "li" : "div";

  return (
    <Component className={`list-item ${className}`}>
      {children}
    </Component>
  );
}; 