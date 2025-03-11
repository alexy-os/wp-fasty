import path from 'path';

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
    className: RegExp[];
    contextType: string;
  };
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
            className: [/className=["']([^"']+)["']/g],
            contextType: 'jsx'
          }
        },
        javascript: {
          extensions: ['.js', '.ts'],
          patterns: {
            className: [
              /className:\s*["']([^"']+)["']/g,
              /\bclassName:\s*["']([^"']+)["']/g
            ],
            contextType: 'const'
          }
        },
        php: {
          extensions: ['.php'],
          patterns: {
            className: [
              /className=["']([^"']+)["']/g,
              /class=["']([^"']+)["']/g
            ],
            contextType: 'php'
          }
        },
        html: {
          extensions: ['.html', '.hbs', '.handlebars'],
          patterns: {
            className: [/class=["']([^"']+)["']/g],
            contextType: 'html'
          }
        },
        vue: {
          extensions: ['.vue'],
          patterns: {
            className: [
              /class=["']([^"']+)["']/g,
              /:class=["']\{([^}]+)\}["']/g
            ],
            contextType: 'vue'
          }
        },
        svelte: {
          extensions: ['.svelte'],
          patterns: {
            className: [
              /class=["']([^"']+)["']/g,
              /class:([^=]+)=["']([^"']+)["']/g
            ],
            contextType: 'svelte'
          }
        }
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
  public getPatternsForFile(filePath: string): { patterns: RegExp[], contextType: string } | null {
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