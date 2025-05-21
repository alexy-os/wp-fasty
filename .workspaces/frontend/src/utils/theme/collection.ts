import { useTheme } from '@/store/theme/context';
import React from 'react';

/**
 * Type for component collection
 */
export type ComponentCollection = { [key: string]: React.ComponentType<any> };

/**
 * Component loader with fallback support
 */
export class ComponentLoader {
  private theme: string;
  private namespace: string;

  constructor(namespace: string = 'components') {
    const { current } = useTheme();
    this.theme = current;
    this.namespace = namespace;
  }

  /**
   * Load component module from the current theme
   */
  load(module: string = 'index'): ComponentCollection {
    const result: ComponentCollection = {};

    try {
      // Try to load components from the current theme
      let components;
      try {
        components = require(`@${this.theme}/${this.namespace}/${module}`);
      } catch (importError: any) {
        // If the error is related to missing dependencies, log and continue
        if (importError.message && importError.message.includes('Cannot find module')) {
          console.warn(`Import dependency error in module "${module}" for theme "${this.theme}": ${importError.message}`);
          // Create an empty object instead of the broken module
          components = {};
        } else {
          // If this is another type of error, pass it on
          throw importError;
        }
      }

      // Create a proxy for each component
      Object.keys(components).forEach(key => {
        try {
          result[key] = components[key] || this.createFallback(key);
        } catch (componentError) {
          console.warn(`Error loading component "${key}" from "${module}": ${componentError}`);
          result[key] = this.createFallback(key);
        }
      });

      // Add a handler for missing components
      return new Proxy(result, {
        get: (target, prop: string) => {
          if (typeof prop === 'string' && !(prop in target)) {
            console.error(`Component "${prop}" not found in theme "${this.theme}" namespace "${this.namespace}"`);
            return this.createFallback(prop);
          }
          return target[prop as string];
        }
      });
    } catch (error) {
      console.error(`Failed to load module "${module}" for theme "${this.theme}" in namespace "${this.namespace}":`, error);

      // Return a proxy that creates fallbacks for any requested component
      return new Proxy({}, {
        get: (_, prop: string) => {
          if (typeof prop === 'string') {
            console.error(`Component "${prop}" not available - module "${module}" failed to load in namespace "${this.namespace}"`);
            return this.createFallback(prop);
          }
          return undefined;
        }
      });
    }
  }

  /**
   * Creates a fallback component that displays an error message
   */
  private createFallback(name: string): React.FC<any> {
    // Capture theme and namespace context values outside of component
    const theme = this.theme;
    const namespace = this.namespace;

    // Now create component without relying on this binding
    const FallbackComponent: React.FC<any> = (props) => {
      console.error(`Rendering fallback for missing component "${name}" in theme "${theme}" namespace "${namespace}"`);

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
        }, `Missing component: ${name} (theme: ${theme}, namespace: ${namespace})`);
      }

      // In production, render an empty span to avoid breaking the layout
      return React.createElement('span', { ...props, style: { display: 'none' } });
    };

    return FallbackComponent;
  }
}

/**
 * Factory functions for different component types
 */
export const getComponents = (module: string = 'index'): ComponentCollection => {
  return new ComponentLoader('components').load(module);
};

export const getUI = (module: string = 'index'): ComponentCollection => {
  return new ComponentLoader('ui').load(module);
};

export const getBlocks = (module: string = 'index'): ComponentCollection => {
  return new ComponentLoader('blocks').load(module);
};

export const getTemplates = (module: string = 'index'): ComponentCollection => {
  return new ComponentLoader('templates').load(module);
};