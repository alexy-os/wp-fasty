import { EnhancedClassEntry, ClassNameConfig } from '../types';
import { generateCryptoFromQuark } from '../../../utils/crypto-generator';

/**
 * Creates an enhanced class entry from extracted class information
 */
export function createClassEntry(
  classes: string,
  componentName: string,
  componentDir: string,
  elementType: string,
  variants: Record<string, string> = {},
  config: ClassNameConfig
): EnhancedClassEntry {
  const quark = generateQuarkName(classes, config.quarkPrefix);
  
  return {
    quark,
    crypto: generateCryptoFromQuark(quark),
    semantic: generateSemanticName(componentName, elementType, classes, config.semanticPrefix),
    classes: classes.trim(),
    componentName,
    elementType,
    variants,
    isPublic: true,
    components: {
      [componentName]: {
        path: componentDir,
        name: componentName
      }
    }
  };
}

/**
 * Generates a unique quark name from class string
 */
function generateQuarkName(classes: string, quarkPrefix: string): string {
  const normalizedClasses = normalizeClassString(classes);
  
  const quarkId = normalizedClasses
    .split(' ')
    .map(cls => {
      const parts = cls.split(':');
      const baseCls = parts[parts.length - 1];
      
      const cleanCls = baseCls
        .replace(/[\[\]\/\(\)]/g, '')
        .replace(/[&>~=]/g, '')
        .replace(/[^a-zA-Z0-9-_]/g, '');
      
      if (cleanCls.match(/^\d/)) {
        return cleanCls.replace(/[^\d]/g, '');
      }
      
      return cleanCls
        .split('-')
        .map(word => word[0] || '')
        .join('')
        .toLowerCase();
    })
    .join('');

  return `${quarkPrefix}${quarkId}`;
}

/**
 * Generates a semantic name based on component and class information
 */
function generateSemanticName(componentName: string, elementType: string, classes: string, semanticPrefix: string): string {
  const normalizedClasses = normalizeClassString(classes);
  const classIdentifier = normalizedClasses
    .split(' ')
    .map(cls => {
      const baseCls = cls.split(':').pop() || '';
      
      return baseCls
        .replace(/[\[\]\/\(\)]/g, '-')
        .replace(/[&>~=]/g, '')
        .replace(/[^a-zA-Z0-9-_]/g, '')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
    })
    .filter(Boolean)
    .join('-');

  return `${semanticPrefix}${componentName.toLowerCase()}-${elementType}${classIdentifier ? `-${classIdentifier}` : ''}`;
}

/**
 * Normalizes class string by sorting and deduplicating classes
 */
function normalizeClassString(classString: string): string {
  return classString.split(' ').sort().join(' ');
} 