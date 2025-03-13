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
    const fileExt = filePath.substring(filePath.lastIndexOf('.'));
    
    for (const formatKey in this.config.formats) {
      const format = this.config.formats[formatKey];
      if (format.extensions.includes(fileExt)) {
        return {
          patterns: format.patterns.className,
          contextType: format.patterns.contextType
        };
      }
    }
    
    return null;
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
export const CONFIG = configManager.getConfig();

export default configManager; 