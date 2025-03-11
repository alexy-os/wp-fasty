import { EnhancedClassEntry, ClassNameConfig } from '../types';
import { CLASS_PATTERNS } from '../patterns';
import { createClassEntry } from '../utils/class-entry';
import { determineElementType } from '../utils/element-type';

export class ClassNameExtractor {
  static extract(
    content: string,
    componentName: string,
    componentDir: string,
    config: ClassNameConfig
  ): EnhancedClassEntry[] {
    const classEntries: EnhancedClassEntry[] = [];

    for (const [patternName, patternConfig] of Object.entries(CLASS_PATTERNS)) {
      if (patternName === 'tvVariants') continue;

      const { pattern, contextType } = patternConfig;
      let match;

      while ((match = pattern.exec(content)) !== null) {
        const classes = match[1];
        const elementType = determineElementType(content, match.index, contextType);
        classEntries.push(
          createClassEntry(
            classes, 
            componentName, 
            componentDir, 
            elementType,
            {},
            config
          )
        );
      }
    }

    return classEntries;
  }
} 