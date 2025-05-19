/**
 * Theme reducer
 */
import { DEFAULT_THEME } from './constants';
import type { ThemeAction, ThemeState } from './types';
import { toggleTheme } from './utils';

export const initialThemeState: ThemeState = {
  current: DEFAULT_THEME,
  isLoading: false,
};

export function themeReducer(state: ThemeState, action: ThemeAction): ThemeState {
  switch (action.type) {
    case 'SET_THEME':
      return {
        ...state,
        current: action.payload,
      };
    case 'TOGGLE_THEME':
      return {
        ...state,
        current: toggleTheme(state.current),
      };
    case 'SET_LOADING':
      return {
        ...state,
        isLoading: action.payload,
      };
    default:
      return state;
  }
} 