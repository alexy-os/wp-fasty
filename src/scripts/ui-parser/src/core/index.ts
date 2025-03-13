import { componentAnalyzer } from './analyzer.js';
import { cssGenerator } from './generator.js';
import { componentTransformer } from './transformer.js';

export * from './types.js';
export { componentAnalyzer, cssGenerator, componentTransformer };

/**
 * Main UIParser class
 */
export class UIParser {
  private static instance: UIParser;
  private latestAnalysisResults: any[] | null = null;
  
  private constructor() {}
  
  /**
   * Get UIParser instance (Singleton)
   */
  public static getInstance(): UIParser {
    if (!UIParser.instance) {
      UIParser.instance = new UIParser();
    }
    return UIParser.instance;
  }
  
  /**
   * Analyzes components
   */
  public async analyze(options = {}) {
    const results = await componentAnalyzer.analyzeAllComponents(options);
    this.latestAnalysisResults = results;
    return results;
  }
  
  /**
   * Generates CSS
   */
  public generate(options = {}) {
    return cssGenerator.generate(options);
  }
  
  /**
   * Transforms components
   */
  public transform(options = {}) {
    // If we have analysis results, pass them to the transformer
    if (this.latestAnalysisResults) {
      const enhancedOptions = {
        ...options,
        classEntries: this.latestAnalysisResults
      };
      return componentTransformer.transformComponents(enhancedOptions);
    }
    return componentTransformer.transformComponents(options);
  }
  
  /**
   * Performs all operations sequentially
   */
  public async all(options = {}) {
    console.log('Starting UI Parser all operations...');
    
    try {
      // Clear caches at the beginning of a full run
      this.clearAllCaches();
      
      console.log('Step 1: Analyzing components...');
      const analysisResults = await this.analyze(options);
      console.log(`Found ${analysisResults.length} class entries`);
      
      console.log('\nStep 2: Generating CSS...');
      const cssResults = this.generate(options);
      console.log(`Generated quark.css (${cssResults.quarkCSS.length} bytes)`);
      console.log(`Generated semantic.css (${cssResults.semanticCSS.length} bytes)`);
      
      console.log('\nStep 3: Transforming components...');
      const transformResults = this.transform(options);
      console.log(`Transformed ${transformResults.componentsTransformed} components`);
      
      console.log('\nAll operations completed successfully!');
      
      return {
        analysisResults,
        cssResults,
        transformResults
      };
    } catch (error) {
      console.error('Error during operations:', error);
      throw error;
    }
  }
  
  /**
   * Clears all caches in the system
   */
  public clearAllCaches(): void {
    console.log('Clearing all caches...');
    componentAnalyzer.clearComponentCache();
    componentAnalyzer.clearAnalysisCache();
    cssGenerator.clearCaches();
    componentTransformer.clearCaches();
    this.latestAnalysisResults = null;
  }
}

export const uiParser = UIParser.getInstance();

export default uiParser; 