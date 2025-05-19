import React from 'react';
import { MainLayout } from './MainLayout';

export type RootLayoutProps = {
  title: string
  description?: string
  children: React.ReactNode
}

export function RootLayout({ title, description, children }: RootLayoutProps) {
  return (
    <html lang="en">
      <head>
        <meta charSet="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>{title}</title>
        {description && <meta name="description" content={description} />}
        <link rel="stylesheet" href="/src/assets/css/styles.css" />
        <script src="/src/assets/js/theme-switcher.js"></script>
      </head>
      <body className="bg-background text-foreground">
        <MainLayout>
          {children}
        </MainLayout>
      </body>
    </html>
  );
}