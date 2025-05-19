import { DEFAULT_THEME, THEME_TYPES } from './theme/constants';
import type { ThemeType } from './theme/types';

// Global variable for the theme
let currentTheme: ThemeType = DEFAULT_THEME;

/**
 * Gets current theme 
 */
export function getTheme(): ThemeType {
  return currentTheme;
}

/**
 * Sets current theme
 */
export function setTheme(theme: string): void {
  if (Object.values(THEME_TYPES).includes(theme as ThemeType)) {
    currentTheme = theme as ThemeType;
  } else {
    console.warn(`Invalid theme: ${theme}. Using default: ${DEFAULT_THEME}`);
    currentTheme = DEFAULT_THEME;
  }
}

export function getThemeFromUrl(): ThemeType | null {
  const url = new URL(window.location.href);
  const theme = url.searchParams.get('theme');
  return theme ? (theme as ThemeType) : null;
}
