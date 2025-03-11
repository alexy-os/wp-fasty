import fs from 'fs';
import path from 'path';
import { CONFIG } from '../config';
import { EnhancedClassEntry, CSSGenerationResult, GenerationOptions } from './types';

/**
 * Class for generating CSS
 */
export class CSSGenerator {
  private static instance: CSSGenerator;
  
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
    try {
      if (fs.existsSync(CONFIG.paths.domAnalysisResults)) {
        const jsonContent = fs.readFileSync(CONFIG.paths.domAnalysisResults, 'utf-8');
        return JSON.parse(jsonContent);
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
    let quarkCSS = '';
    let semanticCSS = '';
    
    entries.forEach(entry => {
      quarkCSS += `.${entry.quark} { @apply ${entry.classes}; }\n`;
      semanticCSS += `.${entry.semantic} { @apply ${entry.classes}; }\n`;
    });
    
    return { quarkCSS, semanticCSS };
  }
  
  /**
   * Saves the generated CSS
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
    const outputDir = options.outputPath || CONFIG.paths.componentOutput;
    
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
      console.error('❌ CSS generation failed:', error instanceof Error ? error.message : error);
      throw error;
    }
  }
}

export const cssGenerator = CSSGenerator.getInstance();

export default cssGenerator; 