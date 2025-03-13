import fs from 'fs';
import path from 'path';
import { adapterFactory } from '../adapters';
import { configManager } from '../config';
import { 
  EnhancedClassEntry, 
  ComponentInfo, 
  AnalysisResult, 
  AnalysisOptions 
} from './types';

/**
 * Class for component analysis
 */
export class ComponentAnalyzer {
  private static instance: ComponentAnalyzer;
  private cachedAnalysisResults: Map<string, EnhancedClassEntry[]> = new Map();
  private cachedComponents: Map<string, ComponentInfo[]> = new Map();
  
  private constructor() {}
  
  /**
   * Get ComponentAnalyzer instance (Singleton)
   */
  public static getInstance(): ComponentAnalyzer {
    if (!ComponentAnalyzer.instance) {
      ComponentAnalyzer.instance = new ComponentAnalyzer();
    }
    return ComponentAnalyzer.instance;
  }
  
  /**
   * Analyzes component and extracts classes
   */
  public async analyzeComponent(componentPath: string): Promise<AnalysisResult> {
    const componentName = path.basename(componentPath, path.extname(componentPath));
    console.log(`Analyzing component: ${componentName}`);
    
    try {
            const adapter = adapterFactory.findAdapter(componentPath);
      
      if (!adapter) {
        return {
          entries: [],
          componentName,
          success: false,
          error: `No suitable adapter found for ${componentName}`
        };
      }
      
      console.log(`Using adapter: ${adapter.name}`);
      
            const entries = await adapter.extractClasses(componentPath);
      
      return {
        entries,
        componentName,
        success: entries.length > 0
      };
    } catch (error) {
      console.error(`Error analyzing component ${componentName}:`, error);
      return {
        entries: [],
        componentName,
        success: false,
        error: error instanceof Error ? error.message : String(error)
      };
    }
  }
  
  /**
   * Scans directory and finds components
   */
  public scanDirectory(dir: string): ComponentInfo[] {
    // Check if we already have cached results for this directory
    if (this.cachedComponents.has(dir)) {
      console.log(`Using cached component scan results for: ${dir}`);
      return this.cachedComponents.get(dir) || [];
    }
    
    const components: ComponentInfo[] = [];
    
    const scan = (currentDir: string, relativeDirPath: string = '') => {
      const files = fs.readdirSync(currentDir, { withFileTypes: true });
      
      for (const file of files) {
        const filePath = path.join(currentDir, file.name);
        const relativeFilePath = path.join(relativeDirPath, file.name);
        
        if (file.isDirectory()) {
          scan(filePath, relativeFilePath);
        } else if (file.isFile() && configManager.isFileSupported(file.name)) {
          const componentName = path.basename(file.name, path.extname(file.name));
          components.push({
            path: filePath,
            name: componentName,
            relativePath: relativeFilePath
          });
        }
      }
    };
    
    scan(dir);
    
    // Cache the results
    this.cachedComponents.set(dir, components);
    
    return components;
  }
  
  /**
   * Clear component scan cache for a specific directory or all directories
   */
  public clearComponentCache(dir?: string): void {
    if (dir) {
      this.cachedComponents.delete(dir);
    } else {
      this.cachedComponents.clear();
    }
  }
  
  /**
   * Analyzes all components in the directory
   */
  public async analyzeAllComponents(options: AnalysisOptions = {}): Promise<EnhancedClassEntry[]> {
    const sourceDir = options.sourceDir || configManager.getPath('sourceDir');
    const outputPath = options.outputPath || configManager.getPath('domAnalysisResults');
    
    // Clear cached results if force option is set
    if (options.verbose) {
      this.clearComponentCache(sourceDir);
    }
    
    const components = this.scanDirectory(sourceDir);
    const results: EnhancedClassEntry[] = [];
    
    console.log(`Found ${components.length} components to analyze`);
    
    for (const component of components) {
      try {
        const analysisResult = await this.analyzeComponent(component.path);
        
        if (analysisResult.success) {
          results.push(...analysisResult.entries);
          console.log(`Successfully analyzed ${component.name}, found ${analysisResult.entries.length} class entries`);
        } else {
          console.warn(`No class entries found for ${component.name}${analysisResult.error ? `: ${analysisResult.error}` : ''}`);
        }
      } catch (error) {
        console.error(`Error analyzing ${component.name}:`, error);
      }
    }
    
    try {
            const outputFilePath = outputPath.endsWith('.json') 
        ? outputPath 
        : path.join(outputPath, 'domAnalysis.json');

            const outputDir = path.dirname(outputFilePath);
      fs.mkdirSync(outputDir, { recursive: true });

            fs.writeFileSync(
        outputFilePath,
        JSON.stringify(results, null, 2),
        'utf-8'
      );
      
      
      console.log(`Results saved to: ${outputFilePath}`);

            configManager.setPath('domAnalysisResults', outputFilePath);
      
      // Cache the analysis results
      this.cachedAnalysisResults.set(outputFilePath, results);
      
    } catch (error) {
      console.error('Error saving analysis results:', error);
      throw error;
    }
    
    return results;
  }
  
  /**
   * Loads analysis results from file
   */
  public loadAnalysisResults(filePath: string = configManager.getPath('domAnalysisResults')): EnhancedClassEntry[] {
    // Check if we already have cached results for this file
    if (this.cachedAnalysisResults.has(filePath)) {
      console.log(`Using cached analysis results for: ${filePath}`);
      return this.cachedAnalysisResults.get(filePath) || [];
    }
    
    try {
      if (fs.existsSync(filePath)) {
        const jsonContent = fs.readFileSync(filePath, 'utf-8');
        const results = JSON.parse(jsonContent) as EnhancedClassEntry[];
        
        // Cache the results
        this.cachedAnalysisResults.set(filePath, results);
        
        return results;
      }
      return [];
    } catch (error) {
      console.error('Error loading analysis results:', error);
      return [];
    }
  }
  
  /**
   * Clear analysis results cache for a specific file or all files
   */
  public clearAnalysisCache(filePath?: string): void {
    if (filePath) {
      this.cachedAnalysisResults.delete(filePath);
    } else {
      this.cachedAnalysisResults.clear();
    }
  }
}

export const componentAnalyzer = ComponentAnalyzer.getInstance();

export default componentAnalyzer; 