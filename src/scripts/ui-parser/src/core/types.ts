/**
 * Enhanced class entry with additional information
 */
export interface EnhancedClassEntry {
  quark: string;
  semantic: string;
  crypto: string;
  classes: string;
  componentName: string;     
  elementType: string;       
  variants: {                
    [key: string]: string | undefined;
  };
  isPublic: boolean;         
  components: Record<string, {
    path: string;
    name: string;
  }>;
}

/**
 * Component information
 */
export interface ComponentInfo {
  path: string;
  name: string;
  relativePath: string;
}

/**
 * Component analysis result
 */
export interface AnalysisResult {
  entries: EnhancedClassEntry[];
  componentName: string;
  success: boolean;
  error?: string;
}

/**
 * CSS generation result
 */
export interface CSSGenerationResult {
  quarkCSS: string;
  semanticCSS: string;
}

/**
 * Transformation result
 */
export interface TransformationResult {
  componentsTransformed: number;
  classesReplaced: number;
  errors: Array<{
    component: string;
    error: string;
  }>;
}

/**
 * Analysis options
 */
export interface AnalysisOptions {
  sourceDir?: string;
  outputPath?: string;
  verbose?: boolean;
}

/**
 * Generation options
 */
export interface GenerationOptions {
  outputPath?: string;
  format?: 'css' | 'scss' | 'less';
  minify?: boolean;
}

/**
 * Transformation options
 */
export interface TransformationOptions {
  sourceFile: string;           
  quarkOutput: string;           
  semanticOutput: string;        
  classEntries?: EnhancedClassEntry[];  
  transformationType?: 'semantic' | 'quark' | 'both';  
}

/**
 * Direct Replacer options
 */
export interface DirectReplacerOptions extends TransformationOptions {
} 