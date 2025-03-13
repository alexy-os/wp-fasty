/**
 * Pattern configuration interface
 */
export interface PatternConfig {
  pattern: string;
  name?: string;
}

/**
 * Patterns categories configuration interface
 */
export interface PatternsConfig {
  layout: PatternConfig[];
  sizing: PatternConfig[];
  typography: PatternConfig[];
  interaction: PatternConfig[];
  decoration: PatternConfig[];
}

/**
 * Layout patterns for UI components
 */
export const layoutPatterns: PatternConfig[] = [
  { pattern: "inline-flex items-center justify-center", name: "layout-center" },
  { pattern: "flex items-start", name: "layout-start" },
  { pattern: "grid grid-cols-1 gap-4", name: "grid-stack" }
];

/**
 * Sizing patterns for UI components
 */
export const sizingPatterns: PatternConfig[] = [
  { pattern: "px-4 h-9 text-sm", name: "size-sm" },
  { pattern: "px-6 h-12 text-base", name: "size-md" },
  { pattern: "px-8 h-14 text-lg", name: "size-lg" }
];

/**
 * Typography patterns for UI components
 */
export const typographyPatterns: PatternConfig[] = [
  { pattern: "font-medium text-sm", name: "text-normal" },
  { pattern: "font-bold text-lg", name: "text-heading" }
];

/**
 * Interaction patterns for UI components
 */
export const interactionPatterns: PatternConfig[] = [
  { pattern: "transition-colors hover:bg-accent hover:text-accent-foreground", name: "interactive" },
  { pattern: "focus:ring-2 focus:ring-offset-2 focus:outline-none", name: "focusable" }
];

/**
 * Decoration patterns for UI components
 */
export const decorationPatterns: PatternConfig[] = [
  { pattern: "rounded-full border border-input", name: "pill" },
  { pattern: "rounded-md shadow-sm", name: "card" }
];

/**
 * Default patterns configuration with all categories
 */
export const defaultPatterns: PatternsConfig = {
  layout: layoutPatterns,
  sizing: sizingPatterns,
  typography: typographyPatterns,
  interaction: interactionPatterns,
  decoration: decorationPatterns
}; 