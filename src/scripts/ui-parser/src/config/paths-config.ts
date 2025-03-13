import path from 'path';

/**
 * Paths configuration for the UI Parser
 */
export interface PathsConfig {
  sourceDir: string;
  componentOutput: string;
  domAnalysisResults: string;
}

/**
 * Default paths configuration
 */
export const defaultPaths: PathsConfig = {
  sourceDir: path.resolve(process.cwd(), './src/source'),
  componentOutput: path.resolve(process.cwd(), './src/components'),
  domAnalysisResults: path.resolve(process.cwd(), './src/components/domAnalysis.json'),
}; 