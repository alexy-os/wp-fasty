import { EnhancedClassEntry, ClassNameConfig } from '../types';
import { createClassEntry } from '../utils/class-entry';

export class TailwindVariantsExtractor {
  static extract(
    content: string,
    componentName: string,
    componentDir: string,
    config: ClassNameConfig
  ): EnhancedClassEntry[] {
    const classEntries: EnhancedClassEntry[] = [];
    const tvMatches = content.match(/tv\(\s*\{([\s\S]*?)\}\s*\)/g);

    if (!tvMatches) return classEntries;

    for (const tvMatch of tvMatches) {
      
      const baseMatch = tvMatch.match(/base:\s*["']([^"']+)["']/);
      if (baseMatch) {
        const baseClasses = baseMatch[1];
        classEntries.push(
          createClassEntry(
            baseClasses, 
            componentName, 
            componentDir, 
            'div',
            {},
            config
          )
        );
      }

      
      const variantsMatch = tvMatch.match(/variants:\s*({[\s\S]*?}(?=\s*[,}]\s*\}))/);
      if (variantsMatch) {
        const variantsStr = variantsMatch[1];
        const variantGroups = variantsStr.matchAll(/(\w+):\s*{([^}]+)}/g);

        for (const [_, groupName, groupContent] of variantGroups) {
          const valueMatches = groupContent.matchAll(/(\w+):\s*["']([^"']+)["']/g);
          
          for (const [__, valueName, classes] of valueMatches) {
            classEntries.push(
              createClassEntry(
                classes,
                componentName,
                componentDir,
                'div',
                { [groupName]: valueName },
                config
              )
            );
          }
        }
      }
    }

    return classEntries;
  }
} 