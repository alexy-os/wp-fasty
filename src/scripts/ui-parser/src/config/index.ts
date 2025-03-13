import path from 'path';
import { PatternContextType } from '../adapters/regex/types/pattern-types';

/**
 * Extractor type
 */
export type ExtractorType = 'dom' | 'regex';

/**
 * Supported file formats configuration
 */
export interface FileFormatConfig {
  extensions: string[];
  patterns: {
    className: Array<{
      pattern: RegExp;
      name: string;
    }>;
    contextType: PatternContextType;
  };
}

/**
 * Pattern configuration
 */
export interface PatternConfig {
  pattern: string;
  name?: string;
}

/**
 * UI Parser configuration
 */
export interface UIParserConfig {
  paths: {
    sourceDir: string;
    componentOutput: string;
    domAnalysisResults: string;
  };
  classNames: {
    semanticPrefix: string;
    quarkPrefix: string;
  };
  extractor: ExtractorType;
  formats: {
    [key: string]: FileFormatConfig;
  };
  patterns: {
    layout: PatternConfig[];
    sizing: PatternConfig[];
    typography: PatternConfig[];
    interaction: PatternConfig[];
    decoration: PatternConfig[];
  };
}

/**
 * Class for configuration management
 */
export class ConfigManager {
  private static instance: ConfigManager;
  private config: UIParserConfig;

  private constructor() {
    this.config = {
      paths: {
        sourceDir: path.resolve(process.cwd(), './src/source'),
        componentOutput: path.resolve(process.cwd(), './src/components'),
        domAnalysisResults: path.resolve(process.cwd(), './src/components/domAnalysis.json'),
      },
      classNames: {
        semanticPrefix: 'sc-',
        quarkPrefix: 'q-',
      },
      extractor: 'regex',
      formats: {
        react: {
          extensions: ['.tsx', '.jsx'],
          patterns: {
            className: [
              {
                name: 'jsxClassName',
                pattern: /className=["']([^"']+)["']/g
              },
              {
                name: 'dynamicClassName',
                pattern: /className=\{(?:clsx|cn)\(\s*(?:['"`]([^'"`]+)['"`](?:\s*,\s*['"`]([^'"`]+)['"`])*)\s*\)\}/g
              },
              {
                name: 'templateClassName',
                pattern: /className=\{`([^`]+)`\}/g
              },
              {
                name: 'tvVariants',
                pattern: /tv\(\s*\{([\s\S]*?)\}\s*\)/gs
              }
            ],
            contextType: 'jsx' as PatternContextType
          }
        },
        javascript: {
          extensions: ['.js', '.ts'],
          patterns: {
            className: [
              {
                name: 'constClassName',
                pattern: /className:\s*["']([^"']+)["']/g
              },
              {
                name: 'configClassName',
                pattern: /\bclassName:\s*["']([^"']+)["']/g
              }
            ],
            contextType: 'const' as PatternContextType
          }
        },
        php: {
          extensions: ['.php'],
          patterns: {
            className: [
              {
                name: 'phpClassName',
                pattern: /className=["']([^"']+)["']/g
              },
              {
                name: 'phpClass',
                pattern: /class=["']([^"']+)["']/g
              }
            ],
            contextType: 'php' as PatternContextType
          }
        },
        html: {
          extensions: ['.html', '.hbs', '.handlebars'],
          patterns: {
            className: [
              {
                name: 'htmlClass',
                pattern: /class=["']([^"']+)["']/g
              }
            ],
            contextType: 'html' as PatternContextType
          }
        },
        vue: {
          extensions: ['.vue'],
          patterns: {
            className: [
              {
                name: 'vueClass',
                pattern: /class=["']([^"']+)["']/g
              },
              {
                name: 'vueDynamicClass',
                pattern: /:class=["']\{([^}]+)\}["']/g
              }
            ],
            contextType: 'vue' as PatternContextType
          }
        },
        svelte: {
          extensions: ['.svelte'],
          patterns: {
            className: [
              {
                name: 'svelteClass',
                pattern: /class=["']([^"']+)["']/g
              },
              {
                name: 'svelteDynamicClass',
                pattern: /class:([^=]+)=["']([^"']+)["']/g
              }
            ],
            contextType: 'svelte' as PatternContextType
          }
        }
      },
      patterns: {
        layout: [
          { pattern: "inline-flex items-center justify-center", name: "layout-center" },
          { pattern: "flex items-start", name: "layout-start" },
          { pattern: "grid grid-cols-1 gap-4", name: "grid-stack" }
        ],
        sizing: [
          { pattern: "px-4 h-9 text-sm", name: "size-sm" },
          { pattern: "px-6 h-12 text-base", name: "size-md" },
          { pattern: "px-8 h-14 text-lg", name: "size-lg" }
        ],
        typography: [
          { pattern: "font-medium text-sm", name: "text-normal" },
          { pattern: "font-bold text-lg", name: "text-heading" }
        ],
        interaction: [
          { pattern: "transition-colors hover:bg-accent hover:text-accent-foreground", name: "interactive" },
          { pattern: "focus:ring-2 focus:ring-offset-2 focus:outline-none", name: "focusable" }
        ],
        decoration: [
          { pattern: "rounded-full border border-input", name: "pill" },
          { pattern: "rounded-md shadow-sm", name: "card" }
        ]
      }
    };
  }

  /**
   * Get ConfigManager instance (Singleton)
   */
  public static getInstance(): ConfigManager {
    if (!ConfigManager.instance) {
      ConfigManager.instance = new ConfigManager();
    }
    return ConfigManager.instance;
  }

  /**
   * Get current configuration
   */
  public getConfig(): UIParserConfig {
    return this.config;
  }

  /**
   * Update configuration
   */
  public updateConfig(newConfig: Partial<UIParserConfig>): void {
    this.config = {
      ...this.config,
      ...newConfig,
      paths: {
        ...this.config.paths,
        ...(newConfig.paths || {}),
      },
      classNames: {
        ...this.config.classNames,
        ...(newConfig.classNames || {}),
      },
      formats: {
        ...this.config.formats,
        ...(newConfig.formats || {}),
      },
      patterns: {
        ...this.config.patterns,
        ...(newConfig.patterns || {}),
      }
    };
  }

  /**
   * Update paths in configuration
   */
  public updatePaths(paths: Partial<UIParserConfig['paths']>): void {
    this.config.paths = {
      ...this.config.paths,
      ...paths,
    };
  }

  /**
   * Update class name settings
   */
  public updateClassNames(classNames: Partial<UIParserConfig['classNames']>): void {
    this.config.classNames = {
      ...this.config.classNames,
      ...classNames,
    };
  }

  public setExtractor(type: ExtractorType): void {
    this.config.extractor = type;
  }

  public getExtractor(): ExtractorType {
    return this.config.extractor;
  }

  /**
   * Get supported file extensions
   */
  public getSupportedExtensions(): string[] {
    return Object.values(this.config.formats)
      .flatMap(format => format.extensions);
  }

  /**
   * Get patterns for file type
   */
  public getPatternsForFile(filePath: string): { 
    patterns: Array<{ pattern: RegExp; name: string }>;
    contextType: PatternContextType; 
  } | null {
    const ext = path.extname(filePath).toLowerCase();
    const format = Object.values(this.config.formats)
      .find(f => f.extensions.includes(ext));

    if (!format) return null;

    return {
      patterns: format.patterns.className,
      contextType: format.patterns.contextType
    };
  }

  /**
   * Check if file type is supported
   */
  public isFileSupported(filePath: string): boolean {
    const ext = path.extname(filePath).toLowerCase();
    return this.getSupportedExtensions().includes(ext);
  }
}

export const configManager = ConfigManager.getInstance();
export const CONFIG = configManager.getConfig();

export default configManager; 