import fs from 'fs';
import path from 'path';
import { configManager } from '../config';
import { EnhancedClassEntry, CSSGenerationResult, GenerationOptions } from './types';

/**
 * Class for generating CSS
 */
export class CSSGenerator {
  private static instance: CSSGenerator;
  private cachedResults: Map<string, EnhancedClassEntry[]> = new Map();
  private cachedCSS: Map<string, CSSGenerationResult> = new Map();
  
  private constructor() {}
  
  /**
   * Get CSSGenerator instance (Singleton)
   */
  public static getInstance(): CSSGenerator {
    if (!CSSGenerator.instance) {
      CSSGenerator.instance = new CSSGenerator();
    }
    return CSSGenerator.instance;
  }
  
  /**
   * Loads analysis results
   */
  private loadAnalysisResults(): EnhancedClassEntry[] {
    const domAnalysisPath = configManager.getPath('domAnalysisResults');
    
    // Check if we already have cached results for this file
    if (this.cachedResults.has(domAnalysisPath)) {
      console.log(`Using cached analysis results for CSS generation`);
      return this.cachedResults.get(domAnalysisPath) || [];
    }
    
    try {
      if (fs.existsSync(domAnalysisPath)) {
        const jsonContent = fs.readFileSync(domAnalysisPath, 'utf-8');
        const results = JSON.parse(jsonContent);
        
        // Cache the results
        this.cachedResults.set(domAnalysisPath, results);
        
        return results;
      }
      return [];
    } catch (error) {
      console.error('Error loading analysis results:', error);
      return [];
    }
  }
  
  /**
   * Generates CSS with semantic and quark classes
   */
  private generateCSS(entries: EnhancedClassEntry[]): CSSGenerationResult {
    // Create a cache key based on entries
    const cacheKey = JSON.stringify(entries.map(e => e.classes).sort());
    
    // Check if we have this CSS already generated
    if (this.cachedCSS.has(cacheKey)) {
      console.log('Using cached CSS generation result');
      return this.cachedCSS.get(cacheKey) || { quarkCSS: '', semanticCSS: '' };
    }
    
    let quarkCSS = '';
    let semanticCSS = '';
    
    entries.forEach(entry => {
      if (entry.modifiers.length > 0) {
        // We have modifiers, generate CSS for each of them
        entry.modifiers.forEach(mod => {
          // For crypto file
          quarkCSS += `.${mod.crypto} { @apply ${mod.classes}; }\n`;
          
          // For semantic file
          semanticCSS += `.${mod.semantic} { @apply ${mod.classes}; }\n`;
        });
      } else {
        // No modifiers, using the original class
        quarkCSS += `.${entry.crypto} { @apply ${entry.classes}; }\n`;
        semanticCSS += `.${entry.semantic} { @apply ${entry.classes}; }\n`;
      }
      
      // Add comment with full set of classes for debugging
      // quarkCSS += `/* Original classes: ${entry.classes} */\n\n`;
      // semanticCSS += `/* Original classes: ${entry.classes} */\n\n`;
    });
    
    const result = { quarkCSS, semanticCSS };
    
    // Cache the generated CSS
    this.cachedCSS.set(cacheKey, result);
    
    return result;
  }
  
  /**
   * Saves CSS to files
   */
  private saveCSS(css: CSSGenerationResult, outputDir: string): void {
    fs.mkdirSync(outputDir, { recursive: true });
    
    fs.writeFileSync(
      path.join(outputDir, 'quark.css'), 
      css.quarkCSS
    );
    
    fs.writeFileSync(
      path.join(outputDir, 'semantic.css'), 
      css.semanticCSS
    );
    
    console.log('CSS files generated successfully');
  }
  
  /**
   * Generates CSS and saves files
   */
  public generate(options: GenerationOptions = {}): CSSGenerationResult {
    const outputDir = options.outputPath || configManager.getPath('componentOutput');
    
    // Clear caches if minify or format options change
    if (options.minify !== undefined || options.format !== undefined) {
      this.clearCaches();
    }
    
    try {
      const entries = this.loadAnalysisResults();
      
      if (entries.length === 0) {
        throw new Error('No class entries found for CSS generation');
      }
      
      const css = this.generateCSS(entries);
      
      this.saveCSS(css, outputDir);
      
      console.log(`✓ Generated CSS files:
  - quark.css (${css.quarkCSS.length} bytes)
  - semantic.css (${css.semanticCSS.length} bytes)`);
      
      return css;
    } catch (error) {
      console.error('Failed to generate CSS:', error);
      throw error;
    }
  }
  
  /**
   * Clear all caches
   */
  public clearCaches(): void {
    this.cachedResults.clear();
    this.cachedCSS.clear();
    console.log('CSS generator caches cleared');
  }
}

export const cssGenerator = CSSGenerator.getInstance();

export default cssGenerator; 