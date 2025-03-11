import fs from 'fs';
import path from 'path';
import { adapterFactory } from '../adapters';
import { CONFIG } from '../config';
import { 
  EnhancedClassEntry, 
  ComponentInfo, 
  AnalysisResult, 
  AnalysisOptions 
} from './types';
import { configManager } from '../config/index';

/**
 * Class for component analysis
 */
export class ComponentAnalyzer {
  private static instance: ComponentAnalyzer;
  
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
    return components;
  }
  
  /**
   * Analyzes all components in the directory
   */
  public async analyzeAllComponents(options: AnalysisOptions = {}): Promise<EnhancedClassEntry[]> {
    const sourceDir = options.sourceDir || CONFIG.paths.sourceDir;
    const outputPath = options.outputPath || CONFIG.paths.domAnalysisResults;
    
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

            CONFIG.paths.domAnalysisResults = outputFilePath;
    } catch (error) {
      console.error('Error saving analysis results:', error);
      throw error;
    }
    
    return results;
  }
  
  /**
   * Loads analysis results from file
   */
  public loadAnalysisResults(filePath: string = CONFIG.paths.domAnalysisResults): EnhancedClassEntry[] {
    try {
      if (fs.existsSync(filePath)) {
        const jsonContent = fs.readFileSync(filePath, 'utf-8');
        return JSON.parse(jsonContent) as EnhancedClassEntry[];
      }
      return [];
    } catch (error) {
      console.error('Error loading analysis results:', error);
      return [];
    }
  }
}

export const componentAnalyzer = ComponentAnalyzer.getInstance();

export default componentAnalyzer; 