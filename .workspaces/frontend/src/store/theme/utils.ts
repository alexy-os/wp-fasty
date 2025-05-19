/**
 * Theme utilities
 */
import { DEFAULT_THEME, LOCAL_STORAGE_KEY, THEME_TYPES } from './constants';
import type { ThemeType } from './types';

/**
 * Gets theme from storage (localStorage on client, url param on server)
 */
export const getStoredTheme = (): ThemeType | null => {
  if (typeof window === 'undefined') {
    return null; // Server-side - handled elsewhere
  }

  try {
    const storedTheme = localStorage.getItem(LOCAL_STORAGE_KEY);
    if (storedTheme && Object.values(THEME_TYPES).includes(storedTheme as ThemeType)) {
      return storedTheme as ThemeType;
    }
    return null;
  } catch (error) {
    console.error('Failed to get theme from localStorage:', error);
    return null;
  }
};

/**
 * Stores theme in localStorage
 */
export const storeTheme = (theme: ThemeType): void => {
  if (typeof window === 'undefined') {
    return; // Skip on server-side
  }

  try {
    localStorage.setItem(LOCAL_STORAGE_KEY, theme);
  } catch (error) {
    console.error('Failed to store theme in localStorage:', error);
  }
};

/**
 * Gets theme from URL parameter
 */
export const getThemeFromUrl = (): ThemeType | null => {
  if (typeof window === 'undefined') {
    return null; // Server-side - handled elsewhere
  }

  try {
    const url = new URL(window.location.href);
    const themeParam = url.searchParams.get('theme');

    if (themeParam && Object.values(THEME_TYPES).includes(themeParam as ThemeType)) {
      return themeParam as ThemeType;
    }
    return null;
  } catch (error) {
    console.error('Failed to get theme from URL:', error);
    return null;
  }
};

/**
 * Removes theme parameter from URL
 */
export const removeThemeParamFromUrl = (): void => {
  if (typeof window === 'undefined') {
    return; // Skip on server-side
  }

  try {
    const url = new URL(window.location.href);
    if (url.searchParams.has('theme')) {
      url.searchParams.delete('theme');
      window.history.replaceState({}, '', url.toString());
    }
  } catch (error) {
    console.error('Failed to remove theme param from URL:', error);
  }
};

/**
 * Gets current theme from all available sources
 * Priority: URL param > localStorage > default
 */
export const getInitialTheme = (): ThemeType => {
  const urlTheme = getThemeFromUrl();
  if (urlTheme) {
    storeTheme(urlTheme);
    removeThemeParamFromUrl();
    return urlTheme;
  }

  const storedTheme = getStoredTheme();
  if (storedTheme) {
    return storedTheme;
  }

  return DEFAULT_THEME;
};

/**
 * Toggles between available themes
 */
export const toggleTheme = (currentTheme: ThemeType): ThemeType => {
  return currentTheme === THEME_TYPES.SEMANTIC
    ? THEME_TYPES.UI8KIT
    : THEME_TYPES.SEMANTIC;
}; 