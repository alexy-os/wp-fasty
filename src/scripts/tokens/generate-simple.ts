import { defaultTokens } from './defaults';

// Base color tokens from shadcn/ui
const baseColors = {
  background: { light: "hsl(0 0% 100%)", dark: "hsl(222.2 84% 4.9%)" },
  foreground: { light: "hsl(222.2 84% 4.9%)", dark: "hsl(210 40% 98%)" },
  card: { light: "hsl(0 0% 100%)", dark: "hsl(222.2 84% 4.9%)" },
  "card-foreground": { light: "hsl(222.2 84% 4.9%)", dark: "hsl(210 40% 98%)" },
  popover: { light: "hsl(0 0% 100%)", dark: "hsl(222.2 84% 4.9%)" },
  "popover-foreground": { light: "hsl(222.2 84% 4.9%)", dark: "hsl(210 40% 98%)" },
  primary: { light: "hsl(221.2 83.2% 53.3%)", dark: "hsl(217.2 91.2% 59.8%)" },
  "primary-foreground": { light: "hsl(210 40% 98%)", dark: "hsl(222.2 47.4% 11.2%)" },
  secondary: { light: "hsl(210 40% 96.1%)", dark: "hsl(217.2 32.6% 17.5%)" },
  "secondary-foreground": { light: "hsl(222.2 47.4% 11.2%)", dark: "hsl(210 40% 98%)" },
  muted: { light: "hsl(210 40% 96.1%)", dark: "hsl(217.2 32.6% 17.5%)" },
  "muted-foreground": { light: "hsl(215.4 16.3% 46.9%)", dark: "hsl(215 20.2% 65.1%)" },
  accent: { light: "hsl(210 40% 96.1%)", dark: "hsl(217.2 32.6% 17.5%)" },
  "accent-foreground": { light: "hsl(222.2 47.4% 11.2%)", dark: "hsl(210 40% 98%)" },
  destructive: { light: "hsl(0 84.2% 60.2%)", dark: "hsl(0 62.8% 30.6%)" },
  "destructive-foreground": { light: "hsl(210 40% 98%)", dark: "hsl(210 40% 98%)" },
  border: { light: "hsl(214.3 31.8% 91.4%)", dark: "hsl(217.2 32.6% 17.5%)" },
  input: { light: "hsl(214.3 31.8% 91.4%)", dark: "hsl(217.2 32.6% 17.5%)" },
  ring: { light: "hsl(221.2 83.2% 53.3%)", dark: "hsl(224.3 76.3% 48%)" }
};

async function generateTokens() {
  try {
    // Form final tokens structure
    const tokens = {
      color: baseColors,
      ...defaultTokens
    };

    // Write file
    await Bun.write(
      'figma-tokens.json',
      JSON.stringify(tokens, null, 2)
    );

    console.log('✅ Tokens generated successfully!');
  } catch (error) {
    console.error('❌ Error:', error);
    process.exit(1);
  }
}

generateTokens(); 