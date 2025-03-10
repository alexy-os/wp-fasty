export interface TokenValue {
  value: string;
}

export interface ColorToken {
  light: string;
  dark: string;
}

export interface Tokens {
  color: Record<string, ColorToken>;
  radius: Record<string, TokenValue>;
  container: {
    "max-width": {
      "2xl": TokenValue;
    };
    padding: TokenValue;
  };
  typography: {
    "font-family": Record<string, TokenValue>;
    "font-size": Record<string, TokenValue>;
    "font-weight": Record<string, TokenValue>;
    "line-height": Record<string, TokenValue>;
  };
  spacing: Record<string, TokenValue>;
  shadow: Record<string, TokenValue>;
  "border-width": Record<string, TokenValue>;
  "z-index": Record<string, TokenValue>;
}

export interface ThemeVariables {
  lightTheme: Record<string, string>;
  darkTheme: Record<string, string>;
} 