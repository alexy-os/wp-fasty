/**
 * Theme hooks
 */
import { useEffect, useState } from 'react';
import { THEME_TYPES } from './constants';
import type { ThemeType } from './types';
import { useTheme } from './context';

interface ComponentModule {
  // Add types for your components here
  Container: React.ComponentType<any>;
  SectionHeader: React.ComponentType<any>;
  SectionFooter: React.ComponentType<any>;
  Nav: React.ComponentType<any>;
  NavList: React.ComponentType<any>;
  NavItem: React.ComponentType<any>;
  NavLink: React.ComponentType<any>;
  Main: React.ComponentType<any>;
  Article: React.ComponentType<any>;
  ArticleHeader: React.ComponentType<any>;
  ArticleContent: React.ComponentType<any>;
  ArticleMeta: React.ComponentType<any>;
  ArticleFooter: React.ComponentType<any>;
  // Add more components as needed
}

type ComponentKey = keyof ComponentModule;

/**
 * Maps theme names to component paths
 */
const themeComponentMap: Record<ThemeType, string> = {
  [THEME_TYPES.SEMANTIC]: '@semantic/components',
  [THEME_TYPES.UI8KIT]: '@ui8kit/components',
};

/**
 * Gets component module path based on theme
 */
export const getComponentsPath = (theme: ThemeType): string => {
  return themeComponentMap[theme];
};

/**
 * Asynchronously loads theme components
 */
export async function loadThemeComponents(theme: ThemeType): Promise<ComponentModule> {
  try {
    return await import(/* @vite-ignore */ getComponentsPath(theme));
  } catch (error) {
    console.error(`Failed to load components for theme ${theme}:`, error);
    // Fallback to UI8KIT if SEMANTIC fails
    if (theme === THEME_TYPES.SEMANTIC) {
      return loadThemeComponents(THEME_TYPES.UI8KIT);
    }
    throw error;
  }
}

/**
 * Hook for using components from the current theme
 */
export function useComponents() {
  const { current: currentTheme, isLoading, setTheme } = useTheme();
  const [components, setComponents] = useState<ComponentModule | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  useEffect(() => {
    setLoading(true);

    loadThemeComponents(currentTheme)
      .then((loadedComponents) => {
        setComponents(loadedComponents);
        setError(null);
      })
      .catch((err) => {
        console.error('Error loading components:', err);
        setError(err);
      })
      .finally(() => {
        setLoading(false);
      });
  }, [currentTheme]);

  return {
    components,
    loading: loading || isLoading,
    error,
    currentTheme,
    setTheme,
  };
}

/**
 * Hook for using specific component from the current theme
 */
export function useComponent<K extends ComponentKey>(componentName: K) {
  const { components, loading, error, currentTheme } = useComponents();

  return {
    component: components ? components[componentName] : null,
    loading,
    error,
    currentTheme,
  };
} 