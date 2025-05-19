import { THEME_TYPES, type ThemeType } from './store/theme/constants';

// Глобальная переменная для темы
let currentTheme: ThemeType = THEME_TYPES.UI8KIT;

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
  // Валидация темы
  if (theme === THEME_TYPES.SEMANTIC || theme === THEME_TYPES.UI8KIT) {
    currentTheme = theme as ThemeType;
  }
}
