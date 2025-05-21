import React from 'react';
import { getTheme } from '@/utils/theme';

export function Button({
  children,
  variant = 'default',
  size = 'default',
  className,
  ...props
}: React.ComponentProps<"button"> & {
  variant?: string;
  size?: string;
}) {
  const theme = getTheme();

  try {
    // Dynamically import the Button component based on theme
    const { Button } = require(`@${theme === 'semantic' ? 'semantic' : 'n4shadcn'}/ui/button`);

    return (
      <Button
        variant={variant}
        size={size}
        className={className}
        {...props}
      >
        {children}
      </Button>
    );
  } catch (error) {
    console.error('Error importing Button component:', error);
    return null;
  }
}