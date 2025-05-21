import { useTheme } from '@/store/theme/context';
import React from 'react';

/**
 * Type for component collection
 */
export type ComponentCollection = { [key: string]: React.ComponentType<any> };

/**
 * Loads components for the current theme
 * @param component - name of the component/module (defaults to 'index')
 * @returns loaded components
 */
export function getComponents(component: string = 'index'): ComponentCollection {
  const { current: theme } = useTheme();
  const result: ComponentCollection = {};

  try {
    // Load components from the current theme
    const components = require(`@${theme}/components/${component}`);

    // Create proxies for each component to track missing components
    Object.keys(components).forEach(key => {
      result[key] = components[key] || createFallbackComponent(key, theme);
    });

    // Add a handler for missing components
    return new Proxy(result, {
      get: (target, prop: string) => {
        if (!(prop in target)) {
          console.error(`Component "${prop}" not found in theme "${theme}"`);
          return createFallbackComponent(prop as string, theme);
        }
        return target[prop];
      }
    });
  } catch (error) {
    console.error(`Failed to load component module "${component}" for theme "${theme}":`, error);

    // Try fallback theme
    if (theme !== 'semantic') {
      try {
        return getComponents(component);
      } catch {
        console.error(`Fallback failed for component module "${component}"`);
      }
    }

    return new Proxy({}, {
      get: (_, prop: string) => {
        console.error(`Component "${prop}" not available - module "${component}" failed to load`);
        return createFallbackComponent(prop as string, theme);
      }
    });
  }
}

// Creates a fallback component that displays an error message
function createFallbackComponent(name: string, theme: string): React.FC<any> {
  return function FallbackComponent(props: any) {
    console.error(`Rendering fallback for missing component "${name}" in theme "${theme}"`);

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
        className: `missing-component-${name} ${props.className || ''}`
      }, `Missing component: ${name} (theme: ${theme})`);
    }

    // In production, render an empty span to avoid breaking the layout
    return React.createElement('span', { ...props, style: { display: 'none' } });
  };
}