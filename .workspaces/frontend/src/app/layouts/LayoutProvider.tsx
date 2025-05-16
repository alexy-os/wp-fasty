import React from 'react';
import { RootLayout } from './RootLayout';
import { SemanticLayout } from './SemanticLayout';

type LayoutProps = {
  title: string;
  description?: string;
  children: React.ReactNode;
}

// Simple layout provider that checks localStorage for theme preference
export function LayoutProvider(props: LayoutProps) {
  // Use default SSR version based on the env var
  // In real app this should be synced with the client-side localStorage
  const defaultLayout = process.env.DEFAULT_LAYOUT || 'ui8kit';

  // In SSR context we can't access localStorage directly,
  // but this will be handled by theme-switcher.js on the client side
  // If we had access to request cookies, we could check them here

  // For now just render the default layout
  return defaultLayout === 'semantic'
    ? <SemanticLayout {...props} />
    : <RootLayout {...props} />;
}