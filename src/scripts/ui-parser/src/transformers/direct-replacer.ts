import fs from 'fs';
import path from 'path';
import { EnhancedClassEntry, ModifierEntry } from '../core/types';
import { configManager } from '../config';

/**
 * Options for the DirectReplacer
 * @interface DirectReplacerOptions
 * @description Configures how components are transformed by the DirectReplacer
 */
export interface DirectReplacerOptions {
  sourceFile: string;           // Path to source component file
  quarkOutput: string;          // Path for Quark version output
  semanticOutput: string;       // Path for Semantic version output
  classEntries?: EnhancedClassEntry[];  // Class entries for replacement
  transformationType?: 'semantic' | 'quark' | 'both';  // Type of transformation
}

/*interface ReplacementResult {
  result: string;
  replacementCount: number;
}*/

/**
 * Component transformation class
 * @class DirectReplacer
 * @description Responsible for transforming component files by replacing utility classes with semantic or quark equivalents
 */
export class DirectReplacer {
  private classEntries: EnhancedClassEntry[];
  private classMap: Map<string, { 
    semantic: string, 
    crypto: string,
    modifiers?: ModifierEntry[]
  }>;

  /**
   * Creates a new DirectReplacer instance
   * @param {EnhancedClassEntry[]} classEntries - Array of class entries to use for replacements
   * @throws {Error} If classEntries is not a valid array
   * @description Initializes the replacer and builds a mapping of original classes to their semantic/crypto equivalents
   */
  constructor(classEntries: EnhancedClassEntry[]) {
    // Validate input
    if (!classEntries || !Array.isArray(classEntries)) {
      throw new Error('DirectReplacer requires valid class entries array');
    }
    
    this.classEntries = classEntries;
    this.classMap = new Map();

    // Create a map of correspondences for fast search
    this.classEntries.forEach(entry => {
      if (!entry || typeof entry !== 'object') {
        return; // Skip invalid entries
      }
      
      try {
        if (Array.isArray(entry.modifiers) && entry.modifiers.length > 0) {
          // We have modifiers
          const modSemanticClasses = entry.modifiers
            .filter(m => m && m.semantic)
            .map(m => m.semantic)
            .join(' ');
          
          const modCryptoClasses = entry.modifiers
            .filter(m => m && m.crypto)
            .map(m => m.crypto)
            .join(' ');
          
          if (entry.classes) {
            this.classMap.set(entry.classes, {
              semantic: modSemanticClasses,
              crypto: modCryptoClasses,
              modifiers: entry.modifiers
            });
          }
        } else if (entry.classes && entry.semantic && entry.crypto) {
          // No modifiers, using original crypto/semantic
          this.classMap.set(entry.classes, {
            semantic: entry.semantic,
            crypto: entry.crypto
          });
        }
        
        // Also for normalized classes
        if (entry.classes) {
          const normalizedClasses = this.normalizeClassString(entry.classes);
          if (normalizedClasses !== entry.classes) {
            if (Array.isArray(entry.modifiers) && entry.modifiers.length > 0) {
              const modSemanticClasses = entry.modifiers
                .filter(m => m && m.semantic)
                .map(m => m.semantic)
                .join(' ');
              
              const modCryptoClasses = entry.modifiers
                .filter(m => m && m.crypto)
                .map(m => m.crypto)
                .join(' ');
              
              this.classMap.set(normalizedClasses, {
                semantic: modSemanticClasses,
                crypto: modCryptoClasses,
                modifiers: entry.modifiers
              });
            } else if (entry.semantic && entry.crypto) {
              this.classMap.set(normalizedClasses, {
                semantic: entry.semantic,
                crypto: entry.crypto
              });
            }
          }
        }
      } catch (error) {
        console.error('Error processing class entry:', error);
        // Continue with next entry
      }
    });
    
    if (this.classMap.size === 0) {
      console.warn('DirectReplacer initialized with no valid class entries');
    }
  }

  /**
   * Updates import statements in the content
   */
  /*private updateImports(content: string, variant: 'quark' | 'semantic'): string {
    let result = content;

    
    const importRegex = /import\s+{([^}]+)}\s+from\s+['"]([^'"]+)['"]/g;
    result = result.replace(importRegex, (match, imports, importPath) => {
      
      if (importPath.startsWith('@') || !importPath.startsWith('.')) {
        return match;
      }

      
      const updatedPath = importPath.endsWith(`.${variant}`)
        ? importPath
        : `${importPath}.${variant}`;

      return `import {${imports}} from "${updatedPath}"`;
    });

    
    const typeImportRegex = /import\s+type\s+{([^}]+)}\s+from\s+['"]([^'"]+)['"]/g;
    result = result.replace(typeImportRegex, (match, imports, importPath) => {
      if (importPath.startsWith('@') || !importPath.startsWith('.')) {
        return match;
      }

      const updatedPath = importPath.endsWith(`.${variant}`)
        ? importPath
        : `${importPath}.${variant}`;

      return `import type {${imports}} from "${updatedPath}"`;
    });

    return result;
  }*/

  /*private replaceClassesInContent(content: string, useQuark: boolean): ReplacementResult {
    let result = content;
    let replacementCount = 0;

    for (const [originalClasses, { quark, semantic }] of this.classMap) {
      const replacement = useQuark ? quark : semantic;
      const escapedClasses = originalClasses.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
      const searchRegex = new RegExp(`(['"\`])${escapedClasses}\\1`, 'g');
      
      result = result.replace(searchRegex, (_, quote) => {
        replacementCount++;
        return `${quote}${replacement}${quote}`;
      });
    }

    return { result, replacementCount };
  }*/

  /**
   * Transform a component file
   * @param {DirectReplacerOptions} options - Configuration options for the transformation
   * @returns {Promise<void>} Promise that resolves when transformation is complete
   * @throws {Error} If options are invalid or transformation fails
   * @description Processes a source file, replaces class attributes, and outputs semantic and quark versions
   */
  public async transform(options: DirectReplacerOptions): Promise<void> {
    // Validate options
    if (!options || typeof options !== 'object') {
      throw new Error('Invalid options provided to transform method');
    }
    
    const { sourceFile, quarkOutput, semanticOutput } = options;
    
    // Validate required paths
    if (!sourceFile || typeof sourceFile !== 'string') {
      throw new Error('sourceFile is required and must be a string');
    }
    
    if (!quarkOutput || typeof quarkOutput !== 'string') {
      throw new Error('quarkOutput is required and must be a string');
    }
    
    if (!semanticOutput || typeof semanticOutput !== 'string') {
      throw new Error('semanticOutput is required and must be a string');
    }
    
    // Verify that configuration is valid
    if (!configManager.isValid()) {
      console.warn('Configuration validation failed. Continuing with limited functionality.');
      // We'll continue but with caution
    }

    try {
      // Check if source file exists
      if (!fs.existsSync(sourceFile)) {
        throw new Error(`Source file not found: ${sourceFile}`);
      }
      
      const content = fs.readFileSync(sourceFile, 'utf-8');
      let semanticContent = content;
      let cryptoContent = content;  // Renamed from quarkContent

      // Get the patterns for this file type from configuration
      const filePatterns = configManager.getPatternsForFile(sourceFile);
      
      if (!filePatterns) {
        throw new Error(`No patterns found for file type: ${sourceFile}`);
      }
      
      const foundClasses: Array<{
        fullMatch: string;
        classValue: string;
        index: number;
      }> = [];
      
      // Apply all available patterns for this file type
      for (const patternObj of filePatterns.patterns) {
        if (!patternObj || !patternObj.pattern || !(patternObj.pattern instanceof RegExp)) {
          continue; // Skip invalid patterns
        }
        
        const regex = patternObj.pattern;
        let match;
        
        try {
          // Create a new regex instance to reset lastIndex for each pattern
          const patternRegex = new RegExp(regex.source, regex.flags);
          
          while ((match = patternRegex.exec(content)) !== null) {
            // The pattern's first capturing group should contain the class values
            if (match[1]) {
              foundClasses.push({
                fullMatch: match[0],
                classValue: match[1],
                index: match.index
              });
            }
          }
        } catch (error) {
          console.error(`Error applying pattern ${patternObj.name}:`, error);
          // Continue with next pattern
        }
      }
      
      if (foundClasses.length === 0) {
        console.warn(`No class declarations found in ${sourceFile}`);
      }
      
      // Process found classes, starting from the end to avoid index issues
      foundClasses.sort((a, b) => b.index - a.index);
      
      let replacementCount = 0;
      
      for (const { fullMatch, classValue, index } of foundClasses) {
        const replacement = this.classMap.get(classValue) || 
                          this.classMap.get(this.normalizeClassString(classValue));

        if (replacement) {
          try {
            // The replacement pattern depends on the original pattern
            // We need to preserve the attribute type (class or className)
            const attributeType = fullMatch.startsWith('class=') ? 'class' : 'className';
            
            semanticContent = 
              semanticContent.substring(0, index) + 
              `${attributeType}="${replacement.semantic}"` + 
              semanticContent.substring(index + fullMatch.length);

            cryptoContent = 
              cryptoContent.substring(0, index) + 
              `${attributeType}="${replacement.crypto}"` + 
              cryptoContent.substring(index + fullMatch.length);
              
            replacementCount++;
          } catch (error) {
            console.error(`Error replacing class "${classValue}":`, error);
            // Continue with next replacement
          }
        }
      }
      
      if (replacementCount === 0 && foundClasses.length > 0) {
        console.warn(`Found ${foundClasses.length} classes but made 0 replacements in ${sourceFile}`);
      }

      try {
        // Create directories if they don't exist
        fs.mkdirSync(path.dirname(semanticOutput), { recursive: true });
        fs.mkdirSync(path.dirname(quarkOutput), { recursive: true });

        // Save results
        fs.writeFileSync(semanticOutput, semanticContent);
        fs.writeFileSync(quarkOutput, cryptoContent);  // Save crypto version
      } catch (error) {
        console.error('Error saving transformed files:', error);
        throw error;
      }

    } catch (error) {
      console.error('Error during direct replacement:', error);
      throw error;
    }
  }

  /**
   * Normalizes a class string for consistent matching
   * @param {string} classString - The class string to normalize
   * @returns {string} Normalized class string with classes sorted alphabetically
   * @description Splits a class string, sorts individual classes, and rejoins them for reliable matching
   */
  private normalizeClassString(classString: string): string {
    if (!classString || typeof classString !== 'string') {
      return '';
    }
    return classString.split(' ').sort().join(' ');
  }

  /**
   * Checks if a file is supported by this transformer
   * @param {string} filePath - Path to the file to check
   * @returns {boolean} True if the file is supported
   * @description Uses the configuration manager to determine if the file type is supported
   */
  public supportsFile(filePath: string): boolean {
    if (!filePath || typeof filePath !== 'string') {
      return false;
    }
    // Use the configuration manager to determine supported files
    return configManager.isFileSupported(filePath);
  }
}