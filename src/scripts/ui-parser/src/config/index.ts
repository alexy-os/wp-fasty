import { PatternContextType } from '../adapters/regex/types/pattern-types';
import { defaultPaths, PathsConfig } from './paths-config';
import { defaultFormats, FileFormatConfig } from './file-formats-config';
import { defaultPatterns, PatternConfig, PatternsConfig } from './patterns-config';
import path from 'path';
import fs from 'fs';

/**
 * Extractor type
 */
export type ExtractorType = 'dom' | 'regex';

/**
 * Re-export types from configuration modules
 */
export type { PathsConfig } from './paths-config';
export type { FileFormatConfig } from './file-formats-config';
export type { PatternConfig, PatternsConfig } from './patterns-config';

/**
 * UI Parser configuration
 */
export interface UIParserConfig {
  paths: PathsConfig;
  classNames: {
    semanticPrefix: string;
    quarkPrefix: string;
  };
  extractor: ExtractorType;
  formats: Record<string, FileFormatConfig>;
  patterns: PatternsConfig;
}

/**
 * Interface for the config.type.json file
 */
interface ConfigJson {
  formats?: Record<string, {
    extensions: string[];
    patterns: {
      className: Array<{
        name: string;
        pattern: string;
      }>;
      contextType: PatternContextType;
    }
  }>;
  patterns?: {
    layout?: Array<{ pattern: string; name?: string }>;
    sizing?: Array<{ pattern: string; name?: string }>;
    typography?: Array<{ pattern: string; name?: string }>;
    interaction?: Array<{ pattern: string; name?: string }>;
    decoration?: Array<{ pattern: string; name?: string }>;
  };
}

/**
 * Convert string patterns from JSON to RegExp
 */
function convertJsonPatternsToRegExp(jsonFormats: ConfigJson['formats']): Record<string, FileFormatConfig> {
  if (!jsonFormats) return {};

  const result: Record<string, FileFormatConfig> = {};

  for (const [key, format] of Object.entries(jsonFormats)) {
    result[key] = {
      extensions: format.extensions,
      patterns: {
        className: format.patterns.className.map(p => ({
          name: p.name,
          pattern: new RegExp(p.pattern, 'g')
        })),
        contextType: format.patterns.contextType
      }
    };
  }

  return result;
}

/**
 * Class for configuration management
 */
export class ConfigManager {
  private static instance: ConfigManager;
  private config: UIParserConfig;
  private configFilePath: string;

  private constructor() {
    this.configFilePath = path.resolve(process.cwd(), './src/scripts/ui-parser/src/config/config.type.json');

    let jsonConfig: ConfigJson = {};
    
    // Try to load config from JSON file
    try {
      if (fs.existsSync(this.configFilePath)) {
        const fileContent = fs.readFileSync(this.configFilePath, 'utf-8');
        jsonConfig = JSON.parse(fileContent);
        console.log('Loaded configuration from JSON file');
      }
    } catch (error) {
      console.warn('Failed to load configuration from JSON file:', error);
      console.warn('Using default configuration');
    }

    // Initialize with default configuration, then override with JSON if available
    this.config = {
      paths: defaultPaths,
      classNames: {
        semanticPrefix: 'sc-',
        quarkPrefix: 'q-',
      },
      extractor: 'regex',
      formats: defaultFormats,
      patterns: defaultPatterns
    };

    // Override with JSON config if available
    if (jsonConfig.formats) {
      this.config.formats = {
        ...this.config.formats,
        ...convertJsonPatternsToRegExp(jsonConfig.formats)
      };
    }

    if (jsonConfig.patterns) {
      this.config.patterns = {
        layout: jsonConfig.patterns.layout || defaultPatterns.layout,
        sizing: jsonConfig.patterns.sizing || defaultPatterns.sizing,
        typography: jsonConfig.patterns.typography || defaultPatterns.typography,
        interaction: jsonConfig.patterns.interaction || defaultPatterns.interaction,
        decoration: jsonConfig.patterns.decoration || defaultPatterns.decoration
      };
    }
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
   * Get path configuration value by key
   */
  public getPath(key: keyof PathsConfig): string {
    return this.config.paths[key];
  }

  /**
   * Get all paths configuration
   */
  public getPaths(): PathsConfig {
    return { ...this.config.paths };
  }

  /**
   * Get className prefix by type
   */
  public getClassNamePrefix(type: 'semantic' | 'quark'): string {
    return type === 'semantic' 
      ? this.config.classNames.semanticPrefix 
      : this.config.classNames.quarkPrefix;
  }

  /**
   * Get all classNames configuration
   */
  public getClassNames(): UIParserConfig['classNames'] {
    return { ...this.config.classNames };
  }

  /**
   * Get patterns by category
   */
  public getPatterns(category: keyof PatternsConfig): PatternConfig[] {
    return [...this.config.patterns[category]];
  }

  /**
   * Get all patterns configuration
   */
  public getAllPatterns(): PatternsConfig {
    return { ...this.config.patterns };
  }

  /**
   * Get format configuration by name
   */
  public getFormat(name: string): FileFormatConfig | undefined {
    return this.config.formats[name] 
      ? { ...this.config.formats[name] }
      : undefined;
  }

  /**
   * Get all formats configuration
   */
  public getFormats(): Record<string, FileFormatConfig> {
    return { ...this.config.formats };
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
  public updatePaths(paths: Partial<PathsConfig>): void {
    this.config.paths = {
      ...this.config.paths,
      ...paths,
    };
  }

  /**
   * Update specific path in configuration
   */
  public setPath(key: keyof PathsConfig, value: string): void {
    this.config.paths[key] = value;
  }

  /**
   * Update classNames in configuration
   */
  public updateClassNames(classNames: Partial<UIParserConfig['classNames']>): void {
    this.config.classNames = {
      ...this.config.classNames,
      ...classNames,
    };
  }

  /**
   * Set extractor type
   */
  public setExtractor(type: ExtractorType): void {
    this.config.extractor = type;
  }

  /**
   * Get extractor type
   */
  public getExtractor(): ExtractorType {
    return this.config.extractor;
  }

  /**
   * Get all supported file extensions
   */
  public getSupportedExtensions(): string[] {
    return Object.values(this.config.formats)
      .flatMap(format => format.extensions);
  }

  /**
   * Get patterns for a specific file
   */
  public getPatternsForFile(filePath: string): { 
    patterns: Array<{ pattern: RegExp; name: string }>;
    contextType: PatternContextType; 
  } | null {
    try {
      const fileExt = filePath.substring(filePath.lastIndexOf('.'));
      //console.log(`Looking for patterns for file extension: ${fileExt}`);
      
      for (const formatKey in this.config.formats) {
        const format = this.config.formats[formatKey];
        if (format.extensions.includes(fileExt)) {
          console.log(`Found format configuration for: ${formatKey}`);
          console.log(`Format has ${format.patterns.className.length} patterns`);
          
          // Log all patterns to help with debugging
          format.patterns.className.forEach((p, i) => {
            console.log(`  Pattern ${i+1}: ${p.name} - ${p.pattern}`);
          });
          
          return {
            patterns: format.patterns.className,
            contextType: format.patterns.contextType
          };
        }
      }
      
      console.log(`No format configuration found for extension: ${fileExt}`);
      return null;
    } catch (error) {
      console.error('Error in getPatternsForFile:', error);
      return null;
    }
  }

  /**
   * Check if file is supported
   */
  public isFileSupported(filePath: string): boolean {
    return !!this.getPatternsForFile(filePath);
  }

  /**
   * Save configuration to JSON file
   */
  public saveConfigToFile(): boolean {
    try {
      // Convert RegExp patterns to string for JSON storage
      const jsonFormats: Record<string, any> = {};
      for (const [key, format] of Object.entries(this.config.formats)) {
        jsonFormats[key] = {
          extensions: format.extensions,
          patterns: {
            className: format.patterns.className.map(p => ({
              name: p.name,
              pattern: p.pattern.toString().slice(1, -2) // Remove /g from RegExp
            })),
            contextType: format.patterns.contextType
          }
        };
      }

      const jsonConfig: ConfigJson = {
        formats: jsonFormats,
        patterns: this.config.patterns
      };

      fs.writeFileSync(
        this.configFilePath,
        JSON.stringify(jsonConfig, null, 2),
        'utf-8'
      );
      
      console.log('Configuration saved to:', this.configFilePath);
      return true;
    } catch (error) {
      console.error('Failed to save configuration to file:', error);
      return false;
    }
  }
}

export const configManager = ConfigManager.getInstance();

// Export CONFIG but with a deprecation warning in the getter
// This encourages using configManager methods directly instead
let hasWarnedAboutConfig = false;
export const CONFIG = new Proxy(configManager.getConfig(), {
  get: function(target, prop) {
    if (!hasWarnedAboutConfig) {
      console.warn('WARNING: Direct access to CONFIG is deprecated. Please use configManager methods instead.');
      hasWarnedAboutConfig = true;
    }
    return target[prop as keyof UIParserConfig];
  }
});

export default configManager; 