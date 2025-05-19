// Simple global theme store
let globalTheme = 'ui8kit';

export function setTheme(theme: string): void {
  if (theme === 'semantic' || theme === 'ui8kit') {
    globalTheme = theme;
    console.log(`Theme set to: ${theme}`);
  }
}

export function getTheme(): string {
  return globalTheme;
}
