import fs from 'fs';
import path from 'path';
import { CONFIG } from '../config';
import { componentAnalyzer } from './analyzer';
import { 
  EnhancedClassEntry,
  TransformationResult,
} from './types';

/**
 * Define a new interface for component transformation options
 */
export interface ComponentTransformationOptions {
  sourceDir?: string;
  targetOutputDir?: string;
  transformationType?: 'semantic' | 'quark' | 'both';
  classEntries?: EnhancedClassEntry[];
}

/**
 * Class for component transformation
 */
export class ComponentTransformer {
  private static instance: ComponentTransformer;
  
  private constructor() {}
  
  /**
   * Get ComponentTransformer instance (Singleton)
   */
  public static getInstance(): ComponentTransformer {
    if (!ComponentTransformer.instance) {
      ComponentTransformer.instance = new ComponentTransformer();
    }
    return ComponentTransformer.instance;
  }
  
  /**
   * Transforms components, replacing classes with semantic or quark
   */
  public transformComponents(options: ComponentTransformationOptions = {}): TransformationResult {
    const sourceDir = options.sourceDir || CONFIG.paths.sourceDir;
    const targetOutputDir = options.targetOutputDir || CONFIG.paths.componentOutput;
    const transformationType = options.transformationType || 'both';
    
    const components = componentAnalyzer.scanDirectory(sourceDir);
    
    let domAnalysisData: EnhancedClassEntry[] = [];
    try {
      const jsonContent = fs.readFileSync(CONFIG.paths.domAnalysisResults, 'utf-8');
      domAnalysisData = JSON.parse(jsonContent);
      console.log(`Loaded ${domAnalysisData.length} class entries from domAnalysis.json`);
    } catch (error) {
      console.error('Error loading domAnalysis.json:', error);
      return {
        componentsTransformed: 0,
        classesReplaced: 0,
        errors: [{
          component: 'domAnalysis.json',
          error: error instanceof Error ? error.message : String(error)
        }]
      };
    }
    
    const classMap = new Map<string, { semantic: string, crypto: string }>();
    
    domAnalysisData.forEach(entry => {
      classMap.set(entry.classes, {
        semantic: entry.semantic,
        crypto: entry.crypto
      });
      
      const normalizedClasses = this.normalizeClassString(entry.classes);
      if (normalizedClasses !== entry.classes) {
        classMap.set(normalizedClasses, {
          semantic: entry.semantic,
          crypto: entry.crypto
        });
      }
    });
    
    console.log(`Created class map with ${classMap.size} entries`);
    
    const result: TransformationResult = {
      componentsTransformed: 0,
      classesReplaced: 0,
      errors: []
    };
    
    for (const component of components) {
      console.log(`Processing component: ${component.name}`);
      
      try {
        const content = fs.readFileSync(component.path, 'utf-8');
        
        let semanticContent = content;
        let quarkContent = content;
        
        const classRegex = /className=["']([^"']+)["']/g;
        let match;
        
        const foundClasses: Array<{
          fullMatch: string;
          classValue: string;
          index: number;
        }> = [];
        
        while ((match = classRegex.exec(content)) !== null) {
          foundClasses.push({
            fullMatch: match[0],
            classValue: match[1],
            index: match.index
          });
        }
        
        console.log(`Found ${foundClasses.length} className declarations in ${component.name}`);
        
        for (let i = foundClasses.length - 1; i >= 0; i--) {
          const { fullMatch, classValue, index } = foundClasses[i];
          
          if (classMap.has(classValue)) {
            const replacement = classMap.get(classValue)!;
            
            if (transformationType === 'semantic' || transformationType === 'both') {
              semanticContent = 
                semanticContent.substring(0, index) + 
                `className="${replacement.semantic}"` + 
                semanticContent.substring(index + fullMatch.length);
            }
            
            if (transformationType === 'quark' || transformationType === 'both') {
              quarkContent = 
                quarkContent.substring(0, index) + 
                `className="${replacement.crypto}"` +
                quarkContent.substring(index + fullMatch.length);
            }
            
            result.classesReplaced++;
            console.log(`Replaced "${classValue}" with semantic: "${replacement.semantic}" and crypto: "${replacement.crypto}"`);
            continue;
          }
          
          const normalizedClassValue = this.normalizeClassString(classValue);
          if (classMap.has(normalizedClassValue)) {
            const replacement = classMap.get(normalizedClassValue)!;
            
            if (transformationType === 'semantic' || transformationType === 'both') {
              semanticContent = 
                semanticContent.substring(0, index) + 
                `className="${replacement.semantic}"` + 
                semanticContent.substring(index + fullMatch.length);
            }
            
            if (transformationType === 'quark' || transformationType === 'both') {
              quarkContent = 
                quarkContent.substring(0, index) + 
                `className="${replacement.crypto}"` +
                quarkContent.substring(index + fullMatch.length);
            }
            
            result.classesReplaced++;
            console.log(`Replaced normalized "${normalizedClassValue}" with semantic: "${replacement.semantic}" and crypto: "${replacement.crypto}"`);
            continue;
          }
          
          console.log(`No replacement found for "${classValue}"`);
        }
        
        const outputPath = path.join(targetOutputDir, component.relativePath);
        const outputDir = path.dirname(outputPath);
        
        if (!fs.existsSync(outputDir)) {
          fs.mkdirSync(outputDir, { recursive: true });
        }
        
        const baseName = path.basename(outputPath, path.extname(outputPath));
        const extension = path.extname(outputPath);
        
        if (transformationType === 'semantic' || transformationType === 'both') {
          const semanticOutputPath = path.join(outputDir, `${baseName}.semantic${extension}`);
          fs.writeFileSync(semanticOutputPath, semanticContent);
        }
        
        if (transformationType === 'quark' || transformationType === 'both') {
          const cryptoOutputPath = path.join(outputDir, `${baseName}.crypto${extension}`);
          fs.writeFileSync(cryptoOutputPath, quarkContent);
        }
        
        result.componentsTransformed++;
        console.log(`Transformed component ${component.name} saved`);
      } catch (error) {
        console.error(`Error transforming component ${component.name}:`, error);
        result.errors.push({
          component: component.name,
          error: error instanceof Error ? error.message : String(error)
        });
      }
    }
    
    return result;
  }
  
  /**
   * Normalizes class string
   */
  private normalizeClassString(classString: string): string {
    return classString.split(' ').sort().join(' ');
  }
}

export const componentTransformer = ComponentTransformer.getInstance();

export default componentTransformer; 