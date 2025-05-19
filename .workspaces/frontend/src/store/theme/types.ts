/**
 * Theme types
 */
import { THEME_TYPES } from './constants';

export type ThemeType = typeof THEME_TYPES[keyof typeof THEME_TYPES];

export interface ThemeState {
  current: ThemeType;
  isLoading: boolean;
}

export type ThemeAction =
  | { type: 'SET_THEME'; payload: ThemeType }
  | { type: 'TOGGLE_THEME' }
  | { type: 'SET_LOADING'; payload: boolean }; 