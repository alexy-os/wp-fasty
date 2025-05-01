import React from 'react';
import { IconProps } from './interface';

/**
 * Icon wrapper component for lucide icons
 * Follows the 8px design system
 */
export const Icon: React.FC<IconProps & {
  icon: React.ComponentType<{ className?: string; size?: number; strokeWidth?: number }>
}> = ({
  variant = "default",
  size = "default",
  strokeWidth = 1.5,
  className = "",
  icon: IconComponent,
}) => {
    // Convert size to number for lucide icon
    const sizeMap = {
      sm: 14,
      default: 16,
      lg: 20,
      xl: 24
    };

    const numericSize = sizeMap[size] || 16;

    return (
      <span className={`icon icon-${variant} ${size !== "default" ? `icon-${size}` : ""} ${className}`}>
        <IconComponent strokeWidth={strokeWidth} size={numericSize} />
      </span>
    );
  }; 