import React from 'react';
import { BadgeProps } from './interface';

/**
 * Badge component for status indicators
 * Follows the 8px design system
 */
export const Badge: React.FC<BadgeProps> = ({
  variant = "default",
  size = "default",
  className = "",
  children,
}) => {
  return (
    <span
      className={`badge badge-${variant} ${size !== "default" ? `badge-${size}` : ""} ${className}`}
    >
      {children}
    </span>
  );
}; 