import React from 'react';
import { QuizOptionProps } from './interface';

/**
 * Quiz Option component for interactive choices
 * Follows the 8px design system
 */
export const QuizOption: React.FC<QuizOptionProps & React.ButtonHTMLAttributes<HTMLButtonElement>> = ({
  variant = "default",
  size = "default",
  selected = false,
  value,
  onClick,
  className = "",
  children,
  ...props
}) => {
  return (
    <button
      type="button"
      className={`quiz-option quiz-option-${variant} ${size !== "default" ? `quiz-option-${size}` : ""} ${selected ? 'quiz-option-selected' : ''} ${className}`}
      onClick={onClick}
      data-value={value}
      aria-pressed={selected}
      {...props}
    >
      {children}
    </button>
  );
}; 