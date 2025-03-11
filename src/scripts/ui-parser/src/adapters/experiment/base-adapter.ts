import { EnhancedClassEntry } from '../../core/types';

/**
 * Base interface for class extractor adapters
 */
export interface ClassExtractorAdapter {
  /**
   * Adapter name
   */
  readonly name: string;
  
  /**
   * Analyzes the component and extracts classes
   * @param componentPath Path to the component
   * @returns Array of class records
   */
  extractClasses(componentPath: string): Promise<EnhancedClassEntry[]>;
  
  /**
   * Checks if the adapter supports the given component
   * @param componentPath Path to the component
   * @returns true if the adapter supports the component
   */
  supportsComponent(componentPath: string): boolean;
} 