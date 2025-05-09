import React from 'react';
import { ButtonProps } from './interface';

/**
 * Button component with semantic class names
 * Following the 8px design system
 */
export const Button: React.FC<ButtonProps & React.ButtonHTMLAttributes<HTMLButtonElement>> = ({
  variant = "primary",
  size = "default",
  disabled = false,
  className = "",
  children,
  ...props
}) => {
  return (
    <button
      className={`button button-${variant} ${size !== "default" ? `button-${size}` : ""} ${className}`}
      disabled={disabled}
      {...props}
    >
      {children}
    </button>
  );
}; 