/**
 * Theme Switcher Button Component
 */
import React from 'react';
import { THEME_TYPES } from '../store/theme';
import { useTheme } from '../store/theme/context';

interface ThemeSwitcherProps {
  className?: string;
}

export function ThemeSwitcher({ className = '' }: ThemeSwitcherProps) {
  const { current: currentTheme, toggleTheme } = useTheme();

  const isSemanticActive = currentTheme === THEME_TYPES.SEMANTIC;

  return (
    <button
      id="theme-toggle"
      type="button"
      onClick={toggleTheme}
      className={`theme-toggle-btn ${className}`}
      data-theme={currentTheme}
      aria-label={`Switch to ${isSemanticActive ? 'UI8KIT' : 'Semantic'} theme`}
    >
      {isSemanticActive ? 'Switch to UI8KIT' : 'Switch to Semantic'}
    </button>
  );
} 