import React from 'react';
import { ProgressProps } from './interface';

/**
 * Progress bar component
 * Follows the 8px design system
 */
export const Progress: React.FC<ProgressProps> = ({
  variant = "default",
  size = "default",
  value = 0,
  max = 100,
  label,
  className = "",
}) => {
  // Ensure value is between 0 and max
  const clampedValue = Math.max(0, Math.min(value, max));

  // Calculate percentage
  const percentage = (clampedValue / max) * 100;

  return (
    <div className="flex items-center gap-x-3">
      {label && (
        <span className="text-xs text-muted-foreground">{label}</span>
      )}
      <div
        className={`progress progress-${variant} ${size !== "default" ? `progress-${size}` : ""} ${className}`}
        role="progressbar"
        aria-valuenow={clampedValue}
        aria-valuemin={0}
        aria-valuemax={max}
      >
        <div
          className={`progress-indicator progress-indicator-${variant}`}
          style={{ width: `${percentage}%` }}
        />
      </div>
    </div>
  );
}; 