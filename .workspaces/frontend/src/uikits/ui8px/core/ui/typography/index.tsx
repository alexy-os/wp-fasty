import React from 'react';
import { HeadingProps, TextProps } from './interface';

/**
 * Heading component with semantic class names
 * Follows the 8px design system
 */
export const Heading: React.FC<HeadingProps> = ({
  level = "h2",
  weight = "default",
  className = "",
  children,
}) => {
  const Component = level;

  return (
    <Component
      className={`heading heading-${level} ${weight !== "default" ? `heading-${weight}` : ""} ${className}`}
    >
      {children}
    </Component>
  );
};

/**
 * Text component with semantic class names
 */
export const Text: React.FC<TextProps> = ({
  size = "default",
  weight = "default",
  variant = "default",
  className = "",
  children,
}) => {
  return (
    <p
      className={`text text-${variant} ${size !== "default" ? `text-${size}` : ""} ${weight !== "default" ? `text-${weight}` : ""} ${className}`}
    >
      {children}
    </p>
  );
};

/**
 * Specialized text components
 */

export const Label: React.FC<Omit<TextProps, 'size'> & { htmlFor?: string }> = ({
  weight = "medium",
  variant = "default",
  className = "",
  children,
  htmlFor,
}) => {
  return (
    <label
      htmlFor={htmlFor}
      className={`label text-sm ${weight !== "default" ? `text-${weight}` : ""} ${variant !== "default" ? `text-${variant}` : ""} ${className}`}
    >
      {children}
    </label>
  );
};

export const Caption: React.FC<Omit<TextProps, 'size'>> = ({
  weight = "default",
  variant = "muted",
  className = "",
  children,
}) => {
  return (
    <Text
      size="xs"
      weight={weight}
      variant={variant}
      className={`caption ${className}`}
    >
      {children}
    </Text>
  );
}; 