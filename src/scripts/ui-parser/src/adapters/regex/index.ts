import fs from 'fs';
import path from 'path';
import { EnhancedClassEntry, RegexExtractorConfig } from './types';
import { deduplicateEntries } from './utils/deduplication';
import { TailwindVariantsExtractor } from './extractors/tv-extractor';
import { ClassNameExtractor } from './extractors/className-extractor';
import { configManager } from '../../config';

export class RegexExtractorAdapter {
  readonly name = 'Regex Extractor';
  private config: RegexExtractorConfig;
  
  constructor(config?: Partial<RegexExtractorConfig>) {
    
    this.config = {
      classNames: {
        quarkPrefix: 'q-',
        semanticPrefix: 's-',
        ...(config?.classNames || {})
      }
    };
  }

  /**
   * Updates adapter configuration
   */
  configure(config: Partial<RegexExtractorConfig>): void {
    this.config = {
      ...this.config,
      classNames: {
        ...this.config.classNames,
        ...(config.classNames || {})
      }
    };
  }

  /**
   * Gets current configuration
   */
  getConfig(): RegexExtractorConfig {
    return { ...this.config };
  }
  
  /**
   * Checks if the adapter can process the given component file
   */
  supportsComponent(componentPath: string): boolean {
    return configManager.isFileSupported(componentPath);
  }
  
  /**
   * Extracts CSS classes from a component file
   */
  async extractClasses(componentPath: string): Promise<EnhancedClassEntry[]> {
    try {
      const content = fs.readFileSync(componentPath, 'utf-8');
      const componentName = path.basename(componentPath, path.extname(componentPath));
      const componentDir = path.dirname(componentPath);
      
      const classEntries: EnhancedClassEntry[] = [
        ...TailwindVariantsExtractor.extract(
          content, 
          componentName, 
          componentDir,
          this.config.classNames
        ),
        ...ClassNameExtractor.extract(
          content, 
          componentName, 
          componentDir,
          this.config.classNames
        )
      ];

      return deduplicateEntries(classEntries);
    } catch (error) {
      console.error(`Error analyzing component with regex:`, error);
      return [];
    }
  }
}

export default RegexExtractorAdapter; 