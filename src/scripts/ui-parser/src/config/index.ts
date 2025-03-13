import { PatternContextType } from '../adapters/regex/types/pattern-types';
import { defaultPaths, PathsConfig } from './paths-config';
import { defaultFormats, FileFormatConfig } from './file-formats-config';
import { defaultPatterns, PatternConfig, PatternsConfig } from './patterns-config';

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
 * Class for configuration management
 */
export class ConfigManager {
  private static instance: ConfigManager;
  private config: UIParserConfig;

  private constructor() {
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
}

export const configManager = ConfigManager.getInstance();
export const CONFIG = configManager.getConfig();

export default configManager; 