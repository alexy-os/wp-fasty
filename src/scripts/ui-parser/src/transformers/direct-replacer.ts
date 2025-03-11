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

interface ReplacementResult {
  result: string;
  replacementCount: number;
}

export class DirectReplacer {
  // private classEntries: EnhancedClassEntry[];
  private classMap: Map<string, { quark: string; semantic: string }>;

  constructor(classEntries: EnhancedClassEntry[]) {
    // this.classEntries = classEntries;
    const sortedEntries = classEntries
      .map(entry => ({
        original: entry.classes,
        quark: entry.quark,
        semantic: entry.semantic
      }))
      .sort((a, b) => b.original.length - a.original.length);

    this.classMap = new Map(
      sortedEntries.map(entry => [
        entry.original,
        { quark: entry.quark, semantic: entry.semantic }
      ])
    );
  }

  /**
   * Updates import statements in the content
   */
  private updateImports(content: string, variant: 'quark' | 'semantic'): string {
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
  }

  private replaceClassesInContent(content: string, useQuark: boolean): ReplacementResult {
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
  }

  /**
   * Transform a component file
   */
  public async transform(options: DirectReplacerOptions): Promise<void> {
    const { sourceFile, quarkOutput, semanticOutput } = options;
    
    // Use provided classEntries or fall back to the ones provided in constructor
    // const entries = classEntries || this.classEntries;

    try {
      if (!fs.existsSync(sourceFile)) {
        throw new Error(`Source file not found: ${sourceFile}`);
      }

      
      const content = fs.readFileSync(sourceFile, 'utf-8');
      const componentName = path.basename(sourceFile, path.extname(sourceFile));

      
      const quarkContent = this.replaceClassesInContent(content, true);
      const semanticContent = this.replaceClassesInContent(content, false);

      
      const quarkWithImports = this.updateImports(quarkContent.result, 'quark');
      const semanticWithImports = this.updateImports(semanticContent.result, 'semantic');

      
      const wrappedQuarkContent = this.wrapWithExport(quarkWithImports, componentName, 'Quark');
      const wrappedSemanticContent = this.wrapWithExport(semanticWithImports, componentName, 'Semantic');

      
      const outputDir = path.dirname(quarkOutput);
      fs.mkdirSync(outputDir, { recursive: true });

      
      fs.writeFileSync(quarkOutput, wrappedQuarkContent);
      fs.writeFileSync(semanticOutput, wrappedSemanticContent);

      console.log(`✓ Component ${componentName} transformed successfully`);
    } catch (error) {
      console.error('Error during transformation:', error instanceof Error ? error.message : error);
      throw error;
    }
  }

  /**
   * Wraps component content with proper export
   */
  private wrapWithExport(content: string, componentName: string, variant: 'Quark' | 'Semantic'): string {
    
    const exportMatch = content.match(/export\s+(?:default\s+)?(?:function|const|class)\s+(\w+)/);
    
    if (!exportMatch) {
      
      return content;
    }

    
    const modifiedContent = content.replace(
      /export\s+(?:default\s+)?(?=(?:function|const|class)\s+\w+)/,
      ''
    );

    
    return `${modifiedContent}


export { ${componentName} as ${componentName}${variant} };
export default ${componentName};
`;
  }

  public supportsFile(filePath: string): boolean {
    const ext = path.extname(filePath).toLowerCase();
    return ['.tsx', '.jsx', '.js', '.vue', '.svelte', '.html', '.hbs', '.handlebars', '.php'].includes(ext);
  }
}