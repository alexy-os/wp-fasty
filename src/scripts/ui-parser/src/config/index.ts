import { PatternContextType } from '../adapters/regex/types/pattern-types';
import { defaultPaths, PathsConfig } from './paths-config';
import { defaultFormats, FileFormatConfig } from './file-formats-config';
import { defaultPatterns, PatternConfig, PatternsConfig } from './patterns-config.js';
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
export type { PatternConfig, PatternsConfig } from './patterns-config.js';

/**
 * Validation error interface
 * @interface ValidationError
 * @description Represents a single validation error with path and message
 */
interface ValidationError {
  path: string;
  message: string;
}

/**
 * Validation result interface
 * @interface ValidationResult
 * @description Contains validation status and any errors found during validation
 */
interface ValidationResult {
  valid: boolean;
  errors: ValidationError[];
}

/**
 * UI Parser configuration
 * @interface UIParserConfig
 * @description Main configuration interface that defines all aspects of the UI Parser operation
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
 * @interface ConfigJson
 * @description Represents the structure of the configuration JSON file
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
 * Config validator class - lightweight Zod alternative
 * @class ConfigValidator
 * @description Provides validation functionality for configuration objects without external dependencies
 */
class ConfigValidator {
  /**
   * Validate the entire configuration
   * @param {UIParserConfig} config - The configuration object to validate
   * @returns {ValidationResult} Validation result with errors if any were found
   * @description Performs comprehensive validation of all configuration sections
   */
  public static validateConfig(config: UIParserConfig): ValidationResult {
    const errors: ValidationError[] = [];
    
    // Validate paths
    if (!config.paths) {
      errors.push({ path: 'paths', message: 'Paths configuration is required' });
    } else {
      const pathsErrors = this.validatePaths(config.paths);
      errors.push(...pathsErrors.errors.map(err => ({ 
        path: `paths.${err.path}`, 
        message: err.message 
      })));
    }
    
    // Validate classNames
    if (!config.classNames) {
      errors.push({ path: 'classNames', message: 'ClassNames configuration is required' });
    } else {
      if (typeof config.classNames.semanticPrefix !== 'string') {
        errors.push({ 
          path: 'classNames.semanticPrefix', 
          message: 'semanticPrefix must be a string' 
        });
      }
      
      if (typeof config.classNames.quarkPrefix !== 'string') {
        errors.push({ 
          path: 'classNames.quarkPrefix', 
          message: 'quarkPrefix must be a string' 
        });
      }
    }
    
    // Validate extractor
    if (!config.extractor) {
      errors.push({ path: 'extractor', message: 'Extractor configuration is required' });
    } else if (config.extractor !== 'dom' && config.extractor !== 'regex') {
      errors.push({ 
        path: 'extractor', 
        message: 'Extractor must be either "dom" or "regex"' 
      });
    }
    
    // Validate formats
    if (!config.formats) {
      errors.push({ path: 'formats', message: 'Formats configuration is required' });
    } else {
      Object.entries(config.formats).forEach(([key, format]) => {
        const formatErrors = this.validateFileFormat(format);
        errors.push(...formatErrors.errors.map(err => ({ 
          path: `formats.${key}.${err.path}`, 
          message: err.message 
        })));
      });
    }
    
    // Validate patterns
    if (!config.patterns) {
      errors.push({ path: 'patterns', message: 'Patterns configuration is required' });
    } else {
      const patternErrors = this.validatePatterns(config.patterns);
      errors.push(...patternErrors.errors.map(err => ({ 
        path: `patterns.${err.path}`, 
        message: err.message 
      })));
    }
    
    return {
      valid: errors.length === 0,
      errors
    };
  }
  
  /**
   * Validate paths configuration
   */
  public static validatePaths(paths: PathsConfig): ValidationResult {
    const errors: ValidationError[] = [];
    
    const requiredPaths = ['sourceDir', 'componentOutput', 'domAnalysisResults'];
    
    requiredPaths.forEach(pathKey => {
      if (!paths[pathKey as keyof PathsConfig]) {
        errors.push({ 
          path: pathKey, 
          message: `${pathKey} is required` 
        });
      } else if (typeof paths[pathKey as keyof PathsConfig] !== 'string') {
        errors.push({ 
          path: pathKey, 
          message: `${pathKey} must be a string` 
        });
      }
    });
    
    return {
      valid: errors.length === 0,
      errors
    };
  }
  
  /**
   * Validate file format configuration
   */
  public static validateFileFormat(format: FileFormatConfig): ValidationResult {
    const errors: ValidationError[] = [];
    
    // Validate extensions
    if (!Array.isArray(format.extensions)) {
      errors.push({ 
        path: 'extensions', 
        message: 'Extensions must be an array' 
      });
    } else if (format.extensions.length === 0) {
      errors.push({ 
        path: 'extensions', 
        message: 'Extensions array cannot be empty' 
      });
    } else {
      format.extensions.forEach((ext, index) => {
        if (typeof ext !== 'string' || !ext.startsWith('.')) {
          errors.push({ 
            path: `extensions[${index}]`, 
            message: 'Extension must be a string starting with "."' 
          });
        }
      });
    }
    
    // Validate patterns
    if (!format.patterns) {
      errors.push({ 
        path: 'patterns', 
        message: 'Patterns configuration is required' 
      });
    } else {
      // Validate className patterns
      if (!Array.isArray(format.patterns.className)) {
        errors.push({ 
          path: 'patterns.className', 
          message: 'className patterns must be an array' 
        });
      } else if (format.patterns.className.length === 0) {
        errors.push({ 
          path: 'patterns.className', 
          message: 'className patterns array cannot be empty' 
        });
      } else {
        format.patterns.className.forEach((pattern, index) => {
          if (!pattern.name || typeof pattern.name !== 'string') {
            errors.push({ 
              path: `patterns.className[${index}].name`, 
              message: 'Pattern name is required and must be a string' 
            });
          }
          
          if (!pattern.pattern || !(pattern.pattern instanceof RegExp)) {
            errors.push({ 
              path: `patterns.className[${index}].pattern`, 
              message: 'Pattern must be a valid RegExp' 
            });
          }
        });
      }
      
      // Validate contextType
      const validContextTypes = ['jsx', 'vue', 'svelte', 'php', 'html', 'const'];
      if (!format.patterns.contextType || !validContextTypes.includes(format.patterns.contextType)) {
        errors.push({ 
          path: 'patterns.contextType', 
          message: `contextType must be one of: ${validContextTypes.join(', ')}` 
        });
      }
    }
    
    return {
      valid: errors.length === 0,
      errors
    };
  }
  
  /**
   * Validate patterns configuration
   */
  public static validatePatterns(patterns: PatternsConfig): ValidationResult {
    const errors: ValidationError[] = [];
    
    const categories = ['layout', 'sizing', 'typography', 'interaction', 'decoration'];
    
    categories.forEach(category => {
      const patternArray = patterns[category as keyof PatternsConfig];
      
      if (!Array.isArray(patternArray)) {
        errors.push({ 
          path: category, 
          message: `${category} patterns must be an array` 
        });
      } else {
        patternArray.forEach((pattern, index) => {
          if (!pattern.pattern || typeof pattern.pattern !== 'string') {
            errors.push({ 
              path: `${category}[${index}].pattern`, 
              message: 'Pattern must be a string' 
            });
          }
          
          if (pattern.name && typeof pattern.name !== 'string') {
            errors.push({ 
              path: `${category}[${index}].name`, 
              message: 'Pattern name must be a string if provided' 
            });
          }
        });
      }
    });
    
    return {
      valid: errors.length === 0,
      errors
    };
  }
  
  /**
   * Validate JSON config structure before converting to actual config
   * @param {ConfigJson} jsonConfig - The JSON configuration to validate
   * @returns {ValidationResult} Validation result with errors if any were found
   * @description Validates the raw JSON configuration before it's converted to the internal format
   */
  public static validateJsonConfig(jsonConfig: ConfigJson): ValidationResult {
    const errors: ValidationError[] = [];
    
    // Validate formats
    if (jsonConfig.formats) {
      Object.entries(jsonConfig.formats).forEach(([key, format]) => {
        // Check extensions
        if (!Array.isArray(format.extensions)) {
          errors.push({ 
            path: `formats.${key}.extensions`, 
            message: 'Extensions must be an array' 
          });
        }
        
        // Check patterns
        if (!format.patterns) {
          errors.push({ 
            path: `formats.${key}.patterns`, 
            message: 'Patterns is required' 
          });
        } else {
          // Check className patterns
          if (!Array.isArray(format.patterns.className)) {
            errors.push({ 
              path: `formats.${key}.patterns.className`, 
              message: 'className patterns must be an array' 
            });
          } else {
            format.patterns.className.forEach((pattern, index) => {
              if (!pattern.name) {
                errors.push({ 
                  path: `formats.${key}.patterns.className[${index}].name`, 
                  message: 'Pattern name is required' 
                });
              }
              
              if (!pattern.pattern) {
                errors.push({ 
                  path: `formats.${key}.patterns.className[${index}].pattern`, 
                  message: 'Pattern string is required' 
                });
              } else {
                // Try to create a RegExp to validate
                try {
                  new RegExp(pattern.pattern);
                } catch (e) {
                  errors.push({ 
                    path: `formats.${key}.patterns.className[${index}].pattern`, 
                    message: `Invalid RegExp: ${e instanceof Error ? e.message : String(e)}` 
                  });
                }
              }
            });
          }
        }
      });
    }
    
    // Validate patterns
    if (jsonConfig.patterns) {
      const categories = ['layout', 'sizing', 'typography', 'interaction', 'decoration'];
      
      categories.forEach(category => {
        const patternArray = jsonConfig.patterns?.[category as keyof typeof jsonConfig.patterns];
        
        if (patternArray && !Array.isArray(patternArray)) {
          errors.push({ 
            path: `patterns.${category}`, 
            message: `${category} patterns must be an array` 
          });
        } else if (patternArray) {
          patternArray.forEach((pattern, index) => {
            if (!pattern.pattern) {
              errors.push({ 
                path: `patterns.${category}[${index}].pattern`, 
                message: 'Pattern is required' 
              });
            }
          });
        }
      });
    }
    
    return {
      valid: errors.length === 0,
      errors
    };
  }
}

/**
 * Convert string patterns from JSON to RegExp
 */
function convertJsonPatternsToRegExp(jsonFormats: ConfigJson['formats']): Record<string, FileFormatConfig> {
  if (!jsonFormats) return {};

  const result: Record<string, FileFormatConfig> = {};

  for (const [key, format] of Object.entries(jsonFormats)) {
    try {
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
    } catch (e) {
      console.error(`Error converting pattern for format ${key}:`, e);
      // Skip this format if we can't convert the pattern
    }
  }

  return result;
}

/**
 * Class for configuration management
 * @class ConfigManager
 * @description Singleton class responsible for managing UI Parser configuration
 * and providing access to various configuration settings
 */
export class ConfigManager {
  private static instance: ConfigManager;
  private config: UIParserConfig;
  private configFilePath: string;
  private validationResult: ValidationResult | null = null;

  private constructor() {
    this.configFilePath = path.resolve(process.cwd(), './src/scripts/ui-parser/src/config/config.type.json');

    let jsonConfig: ConfigJson = {};
    
    // Try to load config from JSON file
    try {
      if (fs.existsSync(this.configFilePath)) {
        const fileContent = fs.readFileSync(this.configFilePath, 'utf-8');
        jsonConfig = JSON.parse(fileContent);
        
        // Validate JSON config
        const jsonValidation = ConfigValidator.validateJsonConfig(jsonConfig);
        if (!jsonValidation.valid) {
          console.error('Invalid JSON configuration:', jsonValidation.errors);
          // Continue with default config if JSON is invalid
          jsonConfig = {};
        }
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
    
    // Validate the final configuration
    this.validationResult = ConfigValidator.validateConfig(this.config);
    if (!this.validationResult.valid) {
      console.error('Configuration validation failed:', this.validationResult.errors);
    }
  }

  /**
   * Get ConfigManager instance (Singleton)
   * @returns {ConfigManager} The singleton instance of ConfigManager
   */
  public static getInstance(): ConfigManager {
    if (!ConfigManager.instance) {
      ConfigManager.instance = new ConfigManager();
    }
    return ConfigManager.instance;
  }
  
  /**
   * Check if configuration is valid
   * @returns {boolean} True if the configuration passes all validation checks
   */
  public isValid(): boolean {
    return this.validationResult !== null && this.validationResult.valid;
  }
  
  /**
   * Get validation errors
   * @returns {ValidationError[]} Array of validation errors if any exist
   */
  public getValidationErrors(): ValidationError[] {
    return this.validationResult?.errors || [];
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
    if (!this.config.paths[key]) {
      console.warn(`Path '${key}' not found in configuration. Using empty string.`);
      return '';
    }
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
    if (!this.config.patterns[category]) {
      console.warn(`Pattern category '${category}' not found in configuration. Using empty array.`);
      return [];
    }
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
    const updatedConfig = {
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
    
    // Validate updated config
    const validation = ConfigValidator.validateConfig(updatedConfig);
    if (!validation.valid) {
      console.error('Invalid configuration update:', validation.errors);
      // Continue with update despite errors, but store validation result
      this.validationResult = validation;
    } else {
      this.validationResult = validation;
    }
    
    // Apply the update
    this.config = updatedConfig;
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
    if (type !== 'dom' && type !== 'regex') {
      console.warn(`Invalid extractor type: ${type}. Must be 'dom' or 'regex'.`);
      return;
    }
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
   * @param {string} filePath - Path to the file for which to get patterns
   * @returns {Object|null} Object containing patterns for the file or null if not found
   * @description Identifies the appropriate patterns to use for a given file based on its extension
   */
  public getPatternsForFile(filePath: string): { 
    patterns: Array<{ pattern: RegExp; name: string }>;
    contextType: PatternContextType; 
  } | null {
    try {
      const fileExt = filePath.substring(filePath.lastIndexOf('.'));
      
      for (const formatKey in this.config.formats) {
        const format = this.config.formats[formatKey];
        if (format.extensions.includes(fileExt)) {
          if (!format.patterns || !format.patterns.className || !Array.isArray(format.patterns.className)) {
            return null;
          }
          
          return {
            patterns: format.patterns.className,
            contextType: format.patterns.contextType
          };
        }
      }
      
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
   * @returns {boolean} True if configuration was successfully saved
   * @description Validates configuration before saving and converts RegExp patterns to string format
   */
  public saveConfigToFile(): boolean {
    try {
      // Validate before saving
      const validation = ConfigValidator.validateConfig(this.config);
      if (!validation.valid) {
        console.error('Cannot save invalid configuration:', validation.errors);
        return false;
      }
      
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
      
      return true;
    } catch (error) {
      console.error('Failed to save configuration to file:', error);
      return false;
    }
  }
}

/**
 * Singleton instance of the ConfigManager
 * @const configManager
 * @type {ConfigManager}
 * @description Main access point for configuration operations and settings
 */
export const configManager = ConfigManager.getInstance();

// Export CONFIG but with a deprecation warning in the getter
// This encourages using configManager methods directly instead
let hasWarnedAboutConfig = false;

/**
 * Legacy configuration object
 * @const CONFIG
 * @type {UIParserConfig}
 * @deprecated Use configManager methods instead for better type safety and validation
 * @description Direct access to configuration, emits warning when accessed
 */
export const CONFIG = new Proxy(configManager.getConfig(), {
  get: function(target, prop) {
    if (!hasWarnedAboutConfig) {
      console.warn('WARNING: Direct access to CONFIG is deprecated. Please use configManager methods instead.');
      hasWarnedAboutConfig = true;
    }
    return target[prop as keyof UIParserConfig];
  }
}); 