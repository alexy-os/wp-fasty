/**
 * Theme context
 */
import React, { createContext, useContext, useEffect, useReducer } from 'react';
import { initialThemeState, themeReducer } from './reducer';
import type { ThemeAction, ThemeState, ThemeType } from './types';
import { getInitialTheme, storeTheme } from './utils';

interface ThemeContextType extends ThemeState {
  setTheme: (theme: ThemeType) => void;
  toggleTheme: () => void;
}

interface ThemeProviderProps {
  children: React.ReactNode;
  initialTheme?: ThemeType;
}

const ThemeContext = createContext<ThemeContextType | undefined>(undefined);

export const ThemeProvider: React.FC<ThemeProviderProps> = ({
  children,
  initialTheme
}) => {
  const [state, dispatch] = useReducer(themeReducer, {
    ...initialThemeState,
    current: initialTheme || (typeof window !== 'undefined' ? getInitialTheme() : initialThemeState.current),
  });

  const setTheme = (theme: ThemeType) => {
    dispatch({ type: 'SET_THEME', payload: theme });
  };

  const handleToggleTheme = () => {
    dispatch({ type: 'TOGGLE_THEME' });
  };

  // Sync with localStorage when theme changes
  useEffect(() => {
    storeTheme(state.current);
  }, [state.current]);

  // Apply initial theme on client-side
  useEffect(() => {
    if (typeof window !== 'undefined' && !initialTheme) {
      const clientInitialTheme = getInitialTheme();
      if (clientInitialTheme !== state.current) {
        setTheme(clientInitialTheme);
      }
    }
  }, []);

  const value = {
    ...state,
    setTheme,
    toggleTheme: handleToggleTheme,
  };

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
};

export const useTheme = (): ThemeContextType => {
  const context = useContext(ThemeContext);
  if (context === undefined) {
    throw new Error('useTheme must be used within a ThemeProvider');
  }
  return context;
}; 