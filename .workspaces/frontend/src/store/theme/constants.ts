/**
 * Theme constants
 */

export const THEME_TYPES = {
  SEMANTIC: 'semantic',
  UI8KIT: 'ui8kit',
} as const;

export const DEFAULT_THEME = THEME_TYPES.UI8KIT;

export const STORAGE_KEYS = {
  THEME: 'app-theme',
} as const;

export const LOCAL_STORAGE_KEY = STORAGE_KEYS.THEME; 