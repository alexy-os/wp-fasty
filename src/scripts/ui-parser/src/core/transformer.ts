import fs from 'fs';
import path from 'path';
import { configManager } from '../config';
import { componentAnalyzer } from './analyzer';
import { 
  EnhancedClassEntry,
  TransformationResult,
  ModifierEntry,
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
  private cachedAnalysisData: EnhancedClassEntry[] | null = null;
  private cachedClassMap: Map<string, { semantic: string, crypto: string, modifiers?: ModifierEntry[] }> | null = null;
  private transformedComponents: Set<string> = new Set();
  
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
    const sourceDir = options.sourceDir || configManager.getPath('sourceDir');
    const targetOutputDir = options.targetOutputDir || configManager.getPath('componentOutput');
    const transformationType = options.transformationType || 'both';
    
    const components = componentAnalyzer.scanDirectory(sourceDir);
    
    // Get analysis data from options or load from file
    let domAnalysisData: EnhancedClassEntry[] = options.classEntries || [];
    
    if (domAnalysisData.length === 0) {
      if (this.cachedAnalysisData !== null) {
        //console.log('Using cached analysis data for transformation');
        domAnalysisData = this.cachedAnalysisData;
      } else {
        try {
          const jsonContent = fs.readFileSync(configManager.getPath('domAnalysisResults'), 'utf-8');
          domAnalysisData = JSON.parse(jsonContent);
          //console.log(`Loaded ${domAnalysisData.length} class entries from domAnalysis.json`);
          
          // Cache the analysis data
          this.cachedAnalysisData = domAnalysisData;
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
      }
    }
    
    // Build or use cached class map
    const classMap = this.getClassMap(domAnalysisData);
    
    //console.log(`Using class map with ${classMap.size} entries`);
    
    const result: TransformationResult = {
      componentsTransformed: 0,
      classesReplaced: 0,
      errors: []
    };
    
    for (const component of components) {
      // Skip components that have already been transformed, unless options explicitly 
      // include the component (indicating we want to re-transform it)
      const cacheKey = `${component.path}:${transformationType}:${targetOutputDir}`;
      if (this.transformedComponents.has(cacheKey) && !options.classEntries) {
        //console.log(`Skipping already transformed component: ${component.name}`);
        result.componentsTransformed++;
        continue;
      }
      
      //console.log(`Processing component: ${component.name}`);
      
      try {
        const content = fs.readFileSync(component.path, 'utf-8');
        
        let semanticContent = content;
        let quarkContent = content;
        
        // Get the patterns for this file type from configuration
        const filePatterns = configManager.getPatternsForFile(component.path);
        
        if (!filePatterns) {
          console.warn(`No patterns found for file type: ${component.path}`);
          continue;
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
        
        //console.log(`Found ${foundClasses.length} class declarations in ${component.name}`);
        
        // Process found classes, starting from the end to avoid index issues
        foundClasses.sort((a, b) => b.index - a.index);
        
        for (const { fullMatch, classValue, index } of foundClasses) {
          if (classMap.has(classValue)) {
            const replacement = classMap.get(classValue)!;
            
            // The replacement pattern depends on the original pattern
            // We need to preserve the attribute type (class or className)
            const attributeType = fullMatch.startsWith('class=') ? 'class' : 'className';
            
            if (transformationType === 'semantic' || transformationType === 'both') {
              semanticContent = 
                semanticContent.substring(0, index) + 
                `${attributeType}="${replacement.semantic}"` + 
                semanticContent.substring(index + fullMatch.length);
            }
            
            if (transformationType === 'quark' || transformationType === 'both') {
              quarkContent = 
                quarkContent.substring(0, index) + 
                `${attributeType}="${replacement.crypto}"` +
                quarkContent.substring(index + fullMatch.length);
            }
            
            // Add information about modifiers to the log
            if (replacement.modifiers && replacement.modifiers.length > 0) {
              //console.log(`Replaced "${classValue}" with modifiers:`);
              replacement.modifiers.forEach(mod => {
                //console.log(`  - ${mod.type}: "${mod.name}" (${mod.classes})`);
              });
            } else {
              //console.log(`Replaced "${classValue}" with semantic: "${replacement.semantic}" and crypto: "${replacement.crypto}"`);
            }
            
            result.classesReplaced++;
            continue;
          }
          
          const normalizedClassValue = this.normalizeClassString(classValue);
          if (classMap.has(normalizedClassValue)) {
            const replacement = classMap.get(normalizedClassValue)!;
            
            // Preserve original attribute type
            const attributeType = fullMatch.startsWith('class=') ? 'class' : 'className';
            
            if (transformationType === 'semantic' || transformationType === 'both') {
              semanticContent = 
                semanticContent.substring(0, index) + 
                `${attributeType}="${replacement.semantic}"` + 
                semanticContent.substring(index + fullMatch.length);
            }
            
            if (transformationType === 'quark' || transformationType === 'both') {
              quarkContent = 
                quarkContent.substring(0, index) + 
                `${attributeType}="${replacement.crypto}"` +
                quarkContent.substring(index + fullMatch.length);
            }
            
            result.classesReplaced++;
            //console.log(`Replaced normalized "${normalizedClassValue}" with semantic: "${replacement.semantic}" and crypto: "${replacement.crypto}"`);
            continue;
          }
          
          //console.log(`No replacement found for "${classValue}"`);
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
        
        // Add to transformed components cache
        this.transformedComponents.add(cacheKey);
        
        result.componentsTransformed++;
        //console.log(`Transformed component ${component.name} saved`);
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
   * Get class map from analysis data (cached or newly created)
   */
  private getClassMap(domAnalysisData: EnhancedClassEntry[]): Map<string, { 
    semantic: string, 
    crypto: string,
    modifiers?: ModifierEntry[]
  }> {
    // If we have a cached map, return it
    if (this.cachedClassMap !== null) {
      return this.cachedClassMap;
    }
    
    const classMap = new Map<string, { 
      semantic: string, 
      crypto: string,
      modifiers?: ModifierEntry[]
    }>();
    
    domAnalysisData.forEach(entry => {
      if (entry.modifiers.length > 0) {
        // We have modifiers
        const modSemanticClasses = entry.modifiers.map(m => m.semantic).join(' ');
        const modCryptoClasses = entry.modifiers.map(m => m.crypto).join(' ');
        
        classMap.set(entry.classes, {
          semantic: modSemanticClasses,
          crypto: modCryptoClasses,
          modifiers: entry.modifiers
        });
      } else {
        // No modifiers, using original crypto/semantic
        classMap.set(entry.classes, {
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
          
          classMap.set(normalizedClasses, {
            semantic: modSemanticClasses,
            crypto: modCryptoClasses,
            modifiers: entry.modifiers
          });
        } else {
          classMap.set(normalizedClasses, {
            semantic: entry.semantic,
            crypto: entry.crypto
          });
        }
      }
    });
    
    // Cache the class map
    this.cachedClassMap = classMap;
    
    return classMap;
  }
  
  /**
   * Clear all caches
   */
  public clearCaches(): void {
    this.cachedAnalysisData = null;
    this.cachedClassMap = null;
    this.transformedComponents.clear();
    //console.log('Transformer caches cleared');
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