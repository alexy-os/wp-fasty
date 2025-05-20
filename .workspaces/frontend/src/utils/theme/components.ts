import { useTheme } from '@/store/theme/context';

/**
 * Loads components for the current theme
 * @param component - name of the component/module (defaults to 'index')
 * @returns loaded components
 */
export function getComponents(component: string = 'index'): { [key: string]: any } {
  const { current: theme } = useTheme();

  try {
    // Use dynamic import instead of require
    return require(`@${theme}/components/${component}`);
  } catch (error) {
    console.error(`Failed to load component ${component} for theme ${theme}:`, error);

    // Fallback for safety
    if (theme === 'semantic') {
      try {
        return require(`@semantic/components/${component}`);
      } catch {
        console.error(`Fallback failed for component ${component}`);
        return {};
      }
    }

    return {};
  }
}