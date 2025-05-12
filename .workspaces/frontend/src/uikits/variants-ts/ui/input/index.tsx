import React from 'react';
import { InputProps } from './interface';

/**
 * Input component with semantic class names
 * Follows the 8px design system
 */
export const Input: React.FC<InputProps & React.InputHTMLAttributes<HTMLInputElement>> = ({
  variant = "default",
  size = "default",
  status = "default",
  disabled = false,
  className = "",
  ...props
}) => {
  return (
    <input
      className={`input input-${variant} ${size !== "default" ? `input-${size}` : ""} ${status !== "default" ? `input-${status}` : ""} ${className}`}
      disabled={disabled}
      {...props}
    />
  );
};

/**
 * Textarea component with similar styling as Input
 */
export const Textarea: React.FC<InputProps & React.TextareaHTMLAttributes<HTMLTextAreaElement>> = ({
  variant = "default",
  size = "default",
  status = "default",
  disabled = false,
  className = "",
  ...props
}) => {
  return (
    <textarea
      className={`textarea textarea-${variant} ${size !== "default" ? `textarea-${size}` : ""} ${status !== "default" ? `textarea-${status}` : ""} ${className}`}
      disabled={disabled}
      {...props}
    />
  );
}; 