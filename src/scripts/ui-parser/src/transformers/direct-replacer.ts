import fs from 'fs';
import path from 'path';
import { EnhancedClassEntry } from '../core/types';

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
  private classMap: Map<string, { semantic: string, crypto: string }>;

  constructor(classEntries: EnhancedClassEntry[]) {
    this.classEntries = classEntries;
    this.classMap = new Map();

    // Create a map of correspondences for fast search
    this.classEntries.forEach(entry => {
      this.classMap.set(entry.classes, {
        semantic: entry.semantic,
        crypto: entry.crypto  // Use crypto instead of quark
      });

      // Also add the normalized version
      const normalizedClasses = this.normalizeClassString(entry.classes);
      if (normalizedClasses !== entry.classes) {
        this.classMap.set(normalizedClasses, {
          semantic: entry.semantic,
          crypto: entry.crypto  // Use crypto instead of quark
        });
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
      let cryptoContent = content;  // Переименовали из quarkContent

      const classRegex = /className=["']([^"']+)["']/g;
      let match;
      const matches: Array<{ index: number, length: number, classes: string }> = [];

      while ((match = classRegex.exec(content)) !== null) {
        matches.push({
          index: match.index,
          length: match[0].length,
          classes: match[1]
        });
      }

      // Process from the end to avoid index shifts
      for (let i = matches.length - 1; i >= 0; i--) {
        const { index, length, classes } = matches[i];
        
        const replacement = this.classMap.get(classes) || 
                          this.classMap.get(this.normalizeClassString(classes));

        if (replacement) {
          semanticContent = 
            semanticContent.slice(0, index) + 
            `className="${replacement.semantic}"` + 
            semanticContent.slice(index + length);

          cryptoContent = 
            cryptoContent.slice(0, index) + 
            `className="${replacement.crypto}"` +  // Use crypto
            cryptoContent.slice(index + length);
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
    const ext = path.extname(filePath).toLowerCase();
    return ['.tsx', '.jsx', '.js', '.vue', '.svelte', '.html', '.hbs', '.handlebars', '.php'].includes(ext);
  }
}