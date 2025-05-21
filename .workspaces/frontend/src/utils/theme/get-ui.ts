import { useTheme } from '@/store/theme/context';
import React from 'react';

/**
 * Type for ui collection
 */
export type UiCollection = { [key: string]: React.ComponentType<any> };

/**
 * Loads uis for the current theme
 * @param ui - name of the ui/module (defaults to 'index')
 * @returns loaded uis
 */
export function getUis(ui: string = 'index'): UiCollection {
  const { current: theme } = useTheme();
  const result: UiCollection = {};

  try {
    // Load uis from the current theme
    const uis = require(`@${theme}/ui/${ui}`);

    // Create proxies for each ui to track missing uis
    Object.keys(uis).forEach(key => {
      result[key] = uis[key] || createFallbackUi(key, theme);
    });

    // Add a handler for missing uis
    return new Proxy(result, {
      get: (target, prop: string) => {
        if (!(prop in target)) {
          console.error(`Ui "${prop}" not found in theme "${theme}"`);
          return createFallbackUi(prop as string, theme);
        }
        return target[prop];
      }
    });
  } catch (error) {
    console.error(`Failed to load ui module "${ui}" for theme "${theme}":`, error);

    // Try fallback theme
    if (theme !== 'semantic') {
      try {
        return getUis(ui);
      } catch {
        console.error(`Fallback failed for ui module "${ui}"`);
      }
    }

    return new Proxy({}, {
      get: (_, prop: string) => {
        console.error(`Ui "${prop}" not available - module "${ui}" failed to load`);
        return createFallbackUi(prop as string, theme);
      }
    });
  }
}

// Creates a fallback ui that displays an error message
function createFallbackUi(name: string, theme: string): React.FC<any> {
  return function FallbackUi(props: any) {
    console.error(`Rendering fallback for missing ui "${name}" in theme "${theme}"`);

    // In development, show a visible error
    if (process.env.NODE_ENV !== 'production') {
      return React.createElement('div', {
        style: {
          border: '2px dashed red',
          padding: '8px',
          color: 'red',
          margin: '4px 0'
        },
        ...props,
        className: `missing-ui-${name} ${props.className || ''}`
      }, `Missing ui: ${name} (theme: ${theme})`);
    }

    // In production, render an empty span to avoid breaking the layout
    return React.createElement('span', { ...props, style: { display: 'none' } });
  };
}