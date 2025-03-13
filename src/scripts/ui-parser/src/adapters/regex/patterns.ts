import { ExtractorPatterns } from './types/pattern-types';
import { configManager } from '../../config';
import { FileFormatConfig } from '../../config';

// Dynamically create CLASS_PATTERNS from the configuration
export const CLASS_PATTERNS: ExtractorPatterns = {};

Object.entries(configManager.getConfig().formats).forEach(([_key, format]: [string, FileFormatConfig]) => {
  format.patterns.className.forEach(({ name, pattern }) => {
    CLASS_PATTERNS[name] = {
      pattern,
      contextType: format.patterns.contextType
    };
  });
});

export type PatternName = keyof typeof CLASS_PATTERNS; 