import fs from 'fs';
import path from 'path';
import { defaultTokens } from './defaults';
import { ColorToken, TokenValue, Tokens } from './types';

// shadcn/ui setting
const cssGlobalPath = 'src/styles/shadcn-base.css';
const fileTokens = 'src/design.json';
  
// List of all shadcn color variables
const colorVars = [
  'background', 'foreground', 'card', 'card-foreground',
  'popover', 'popover-foreground', 'primary', 'primary-foreground',
  'secondary', 'secondary-foreground', 'muted', 'muted-foreground',
  'accent', 'accent-foreground', 'destructive', 'destructive-foreground',
  'border', 'input', 'ring'
];

// Function to extract CSS variables from a block
function extractVarsFromBlock(block: string): Record<string, string> {
  const result: Record<string, string> = {};
  const varPattern = /--([a-zA-Z0-9-]+):\s*([^;]+);/g;
  
  let match;
  while ((match = varPattern.exec(block)) !== null) {
    // Check if name and value exist
    if (match[1] && match[2]) {
      const name = match[1];
      const value = match[2].trim();
      result[name] = value;
    }
  }
  
  return result;
}

// Function to read CSS file and extract variables
function extractCssVariables(cssContent: string): { 
  lightTheme: Record<string, string>; 
  darkTheme: Record<string, string>; 
} {
  const rootRegex = /:root\s*{([^}]*)}/s;
  const darkRegex = /\.dark\s*{([^}]*)}/s;

  const rootMatch = cssContent.match(rootRegex);
  const darkMatch = cssContent.match(darkRegex);

  // Use safe access to groups
  const lightTheme = rootMatch && rootMatch[1] 
    ? extractVarsFromBlock(rootMatch[1]) 
    : {};
    
  const darkTheme = darkMatch && darkMatch[1] 
    ? extractVarsFromBlock(darkMatch[1]) 
    : {};

  return { lightTheme, darkTheme };
}

// Function to create color tokens
function createColorTokens(
  lightTheme: Record<string, string>, 
  darkTheme: Record<string, string>
): Record<string, ColorToken> {
  const colorTokens: Record<string, ColorToken> = {};
  
  // Create tokens for each variable
  for (const varName of colorVars) {
    // Check if value exists in light theme
    const lightValue = lightTheme[varName];
    if (lightValue) {
      colorTokens[varName] = {
        light: `hsl(${lightValue})`,
        // Check if value exists in dark theme
        dark: darkTheme[varName] 
          ? `hsl(${darkTheme[varName]})` 
          : `hsl(${lightValue})`
      };
    }
  }
  
  return colorTokens;
}

// Function to create radius tokens
function createRadiusTokens(
  lightTheme: Record<string, string>
): Record<string, TokenValue> {
  return {
    default: { 
      value: lightTheme['radius'] || '0.3rem' 
    }
  };
}

// Main function to generate tokens
async function generateTokens(): Promise<void> {
  try {
    // Path to CSS file
    const cssPath = path.resolve(process.cwd(), cssGlobalPath);
    
    // Check if file exists
    if (!fs.existsSync(cssPath)) {
      throw new Error(`CSS file not found at ${cssPath}`);
    }
    
    // Read CSS file
    const cssContent = await fs.promises.readFile(cssPath, 'utf-8');
    
    // Extract variables
    const { lightTheme, darkTheme } = extractCssVariables(cssContent);
    
    // Create tokens
    const tokens: Tokens = {
      color: createColorTokens(lightTheme, darkTheme),
      radius: createRadiusTokens(lightTheme),
      container: {
        "max-width": {
          "2xl": { value: "1400px" }
        },
        padding: { value: "2rem" }
      },
      ...defaultTokens
    };
    
    // Path to save tokens
    const outputPath = path.resolve(process.cwd(), fileTokens );
    
    // Write tokens to file
    await fs.promises.writeFile(
      outputPath,
      JSON.stringify(tokens, null, 2),
      'utf-8'
    );
    
    console.log('✅ Tokens generated successfully!');
    console.log(`📄 Saved to: ${outputPath}`);
  } catch (error) {
    console.error('❌ Error generating tokens:', error instanceof Error ? error.message : String(error));
    process.exit(1);
  }
}

// Run generation
generateTokens();