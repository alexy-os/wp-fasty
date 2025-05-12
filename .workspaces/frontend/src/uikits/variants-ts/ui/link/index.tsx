import React from 'react';
import { LinkProps } from './interface';

/**
 * Link component with semantic class names
 * Follows the 8px design system
 */
export const Link: React.FC<LinkProps & React.AnchorHTMLAttributes<HTMLAnchorElement>> = ({
  variant = "default",
  size = "default",
  href = "#",
  className = "",
  children,
  ...props
}) => {
  return (
    <a
      href={href}
      className={`link link-${variant} ${size !== "default" ? `link-${size}` : ""} ${className}`}
      {...props}
    >
      {children}
    </a>
  );
}; 