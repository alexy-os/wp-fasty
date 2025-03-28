/** @type {import('tailwindcss').Config} */

module.exports = {
  darkMode: ["class"],
  
  content: [
    './wp-fasty-storefront/**/*.{php,js}',
    './wp-fasty-storefront/fasty/**/*.php',
    './wp-fasty-storefront/template-parts/**/*.php',
    './wp-fasty-storefront/templates/**/*.php',
    './wp-fasty-storefront/*.php'
  ],
  
  theme: {
    extend: {
      colors: {
        // Извлекаем цвета из наших CSS переменных
        background: "var(--color-background)",
        foreground: "var(--color-foreground)",
        card: "var(--color-card)",
        "card-foreground": "var(--color-card-foreground)",
        primary: "var(--color-primary)",
        "primary-foreground": "var(--color-primary-foreground)",
        secondary: "var(--color-secondary)",
        "secondary-foreground": "var(--color-secondary-foreground)",
        muted: "var(--color-muted)",
        "muted-foreground": "var(--color-muted-foreground)",
        accent: "var(--color-accent)",
        "accent-foreground": "var(--color-accent-foreground)",
        destructive: "var(--color-destructive)",
        "destructive-foreground": "var(--color-destructive-foreground)",
        border: "var(--color-border)",
        input: "var(--color-input)",
        ring: "var(--color-ring)",
        success: "var(--color-success)",
        info: "var(--color-info)"
      },
      borderRadius: {
        DEFAULT: "var(--size-radius)",
      },
      fontFamily: {
        sans: "var(--font-base)",
      },
      boxShadow: {
        "elegant": "0 2px 10px rgba(0, 0, 0, 0.05)",
      },
      maxWidth: {
        "container": "var(--container-width)",
      },
      screens: {
        "desktop": "768px",
        "handheld": "568px",
      }
    },
  },
  plugins: [],
}; 