import fs from 'fs';
import path from 'path';
import { EnhancedClassEntry, ModifierEntry } from '../core/types';
import { configManager } from '../config';

/**
 * Options for the DirectReplacer
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

export class DirectReplacer {
  private classEntries: EnhancedClassEntry[];
  private classMap: Map<string, { 
    semantic: string, 
    crypto: string,
    modifiers?: ModifierEntry[]
  }>;

  constructor(classEntries: EnhancedClassEntry[]) {
    this.classEntries = classEntries;
    this.classMap = new Map();

    // Create a map of correspondences for fast search
    this.classEntries.forEach(entry => {
      if (entry.modifiers.length > 0) {
        // We have modifiers
        const modSemanticClasses = entry.modifiers.map(m => m.semantic).join(' ');
        const modCryptoClasses = entry.modifiers.map(m => m.crypto).join(' ');
        
        this.classMap.set(entry.classes, {
          semantic: modSemanticClasses,
          crypto: modCryptoClasses,
          modifiers: entry.modifiers
        });
      } else {
        // No modifiers, using original crypto/semantic
        this.classMap.set(entry.classes, {
          semantic: entry.semantic,
          crypto: entry.crypto
        });
      }
      
      // Also for normalized classes
      const normalizedClasses = this.normalizeClassString(entry.classes);
      if (normalizedClasses !== entry.classes) {
        if (entry.modifiers.length > 0) {
          const modSemanticClasses = entry.modifiers.map(m => m.semantic).join(' ');
          const modCryptoClasses = entry.modifiers.map(m => m.crypto).join(' ');
          
          this.classMap.set(normalizedClasses, {
            semantic: modSemanticClasses,
            crypto: modCryptoClasses,
            modifiers: entry.modifiers
          });
        } else {
          this.classMap.set(normalizedClasses, {
            semantic: entry.semantic,
            crypto: entry.crypto
          });
        }
      }
    });
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
   */
  public async transform(options: DirectReplacerOptions): Promise<void> {
    const { sourceFile, quarkOutput, semanticOutput } = options;

    try {
      const content = fs.readFileSync(sourceFile, 'utf-8');
      let semanticContent = content;
      let cryptoContent = content;  // Renamed from quarkContent

      // Get the patterns for this file type from configuration
      const filePatterns = configManager.getPatternsForFile(sourceFile);
      
      if (!filePatterns) {
        console.warn(`No patterns found for file type: ${sourceFile}`);
        return;
      }
      
      //console.log(`Using patterns for context type: ${filePatterns.contextType}`);
      
      const foundClasses: Array<{
        fullMatch: string;
        classValue: string;
        index: number;
      }> = [];
      
      // Apply all available patterns for this file type
      for (const patternObj of filePatterns.patterns) {
        const regex = patternObj.pattern;
        let match;
        
        // Create a new regex instance to reset lastIndex for each pattern
        const patternRegex = new RegExp(regex.source, regex.flags);
        
        //console.log(`Applying pattern: ${patternObj.name} - ${patternRegex}`);
        
        while ((match = patternRegex.exec(content)) !== null) {
          // The pattern's first capturing group should contain the class values
          if (match[1]) {
            //console.log(`  Found match: "${match[0]}" with class value: "${match[1]}"`);
            foundClasses.push({
              fullMatch: match[0],
              classValue: match[1],
              index: match.index
            });
          }
        }
      }
      
      //console.log(`Found ${foundClasses.length} class declarations in ${sourceFile}`);
      
      // Process found classes, starting from the end to avoid index issues
      foundClasses.sort((a, b) => b.index - a.index);
      
      for (const { fullMatch, classValue, index } of foundClasses) {
        const replacement = this.classMap.get(classValue) || 
                          this.classMap.get(this.normalizeClassString(classValue));

        if (replacement) {
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
            
          //console.log(`Replaced "${classValue}" with semantic: "${replacement.semantic}" and crypto: "${replacement.crypto}"`);
        }
      }

      // Create directories if they don't exist
      fs.mkdirSync(path.dirname(semanticOutput), { recursive: true });
      fs.mkdirSync(path.dirname(quarkOutput), { recursive: true });

      // Save results
      fs.writeFileSync(semanticOutput, semanticContent);
      fs.writeFileSync(quarkOutput, cryptoContent);  // Save crypto version

    } catch (error) {
      console.error('Error during direct replacement:', error);
      throw error;
    }
  }

  private normalizeClassString(classString: string): string {
    return classString.split(' ').sort().join(' ');
  }

  public supportsFile(filePath: string): boolean {
    // Use the configuration manager to determine supported files
    return configManager.isFileSupported(filePath);
  }
}