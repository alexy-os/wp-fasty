/**
 * Component for dynamically loading themed components
 */
import React from 'react';
import { useComponent } from '../store/theme/hooks';

interface ThemeComponentProps {
  component: string;
  fallback?: React.ReactNode;
  [key: string]: any;
}

/**
 * Component that dynamically loads and renders a component based on the current theme
 */
export function ThemeComponent({
  component,
  fallback = null,
  ...props
}: ThemeComponentProps) {
  const { component: Component, loading, error } = useComponent(component as any);

  if (loading) {
    return <>{fallback}</>;
  }

  if (error || !Component) {
    console.error(`Error loading component ${component}:`, error);
    return <div>Error loading component</div>;
  }

  return <Component {...props} />;
}

/**
 * HOC to create pre-configured themed components
 */
export function createThemedComponent(componentName: string) {
  return function ThemedComponent(props: any) {
    return <ThemeComponent component={componentName} {...props} />;
  };
}

// Pre-configured themed components
export const Container = createThemedComponent('Container');
export const Section = createThemedComponent('Section');
export const SectionHeader = createThemedComponent('SectionHeader');
export const SectionFooter = createThemedComponent('SectionFooter');
export const Nav = createThemedComponent('Nav');
export const NavList = createThemedComponent('NavList');
export const NavItem = createThemedComponent('NavItem');
export const NavLink = createThemedComponent('NavLink');
export const Main = createThemedComponent('Main');
export const Article = createThemedComponent('Article');
export const ArticleHeader = createThemedComponent('ArticleHeader');
export const ArticleContent = createThemedComponent('ArticleContent');
export const ArticleMeta = createThemedComponent('ArticleMeta');
export const ArticleFooter = createThemedComponent('ArticleFooter'); 