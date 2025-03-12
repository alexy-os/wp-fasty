import { configManager } from '../config';
import { generateCryptoFromQuark } from './crypto-generator';
import { ModifierEntry, ModifierType } from '../core/types';

export interface ExtractedModifier {
  name: string;
  classes: string;
  remainingClasses: string;
}

/**
 * Finds and extracts modifiers from a set of classes
 */
export function extractModifiers(
  classes: string, 
  componentName: string, 
  elementType: string
): { 
  modifiers: ModifierEntry[];
} {
  const modifiers: ModifierEntry[] = [];
  const classesSet = new Set(classes.split(' ').filter(c => c.trim()));
  
  // Get all patterns from the configuration
  const patterns = [
    ...configManager.getConfig().patterns.layout,
    ...configManager.getConfig().patterns.sizing,
    ...configManager.getConfig().patterns.typography,
    ...configManager.getConfig().patterns.interaction,
    ...configManager.getConfig().patterns.decoration
  ];
  
  // Try to find all patterns in classes
  for (const pattern of patterns) {
    const patternClasses = pattern.pattern.split(' ');
    
    // Check if classesSet contains all classes from pattern
    const matches = patternClasses.every(cls => classesSet.has(cls));
    
    if (matches) {
      // Determine modifier type
      const modType = determineModifierType(pattern.pattern);
      
      // Generate semantic identifier for modifier
      const semanticName = pattern.name || generateSemanticModifierName(pattern.pattern, componentName, elementType);
      
      // Generate crypto for modifier
      const modQuark = `q-mod-${semanticName.replace(/[^a-z0-9]/g, '')}`;
      const modCrypto = generateCryptoFromQuark(modQuark);
      
      // Create modifier entry
      const modifier: ModifierEntry = {
        name: semanticName,
        classes: pattern.pattern,
        crypto: modCrypto,
        semantic: `${configManager.getConfig().classNames.semanticPrefix}${semanticName}`,
        type: modType
      };
      
      modifiers.push(modifier);
      
      // Remove modifier classes from remaining classes
      patternClasses.forEach(cls => classesSet.delete(cls));
    }
  }
  
  // If modifiers were found or we force create source
  if (modifiers.length > 0) {
    // Convert remaining classes to source modifier
    const remainingClasses = Array.from(classesSet).join(' ');
    
    if (remainingClasses) {
      // Create source modifier for remaining classes
      const sourceName = `${componentName}-${elementType}-base`;
      const sourceQuark = `q-src-${sourceName.replace(/[^a-z0-9]/g, '')}`;
      const sourceCrypto = generateCryptoFromQuark(sourceQuark);
      
      modifiers.push({
        name: sourceName,
        classes: remainingClasses,
        crypto: sourceCrypto,
        semantic: `${configManager.getConfig().classNames.semanticPrefix}${sourceName}`,
        type: 'source'
      });
    }
  }
  
  return { modifiers };
}

/**
 * Determines modifier type based on pattern
 */
function determineModifierType(pattern: string): ModifierType {
  if (pattern.includes('flex') || pattern.includes('grid') || pattern.includes('items-')) {
    return 'layout';
  }
  
  if (pattern.includes('px-') || pattern.includes('py-') || pattern.includes('h-') || pattern.includes('w-')) {
    return 'sizing';
  }
  
  if (pattern.includes('font-') || pattern.includes('text-')) {
    return 'typography';
  }
  
  if (pattern.includes('hover:') || pattern.includes('focus:') || pattern.includes('active:')) {
    return 'interaction';
  }
  
  if (pattern.includes('rounded') || pattern.includes('border') || pattern.includes('shadow')) {
    return 'decoration';
  }
  
  return 'source';
}

/**
 * Generates semantic name for modifier based on pattern
 */
function generateSemanticModifierName(
  pattern: string, 
  componentName: string, 
  elementType: string
): string {
  // Analyze pattern to determine its type
  if (pattern.includes('flex') || pattern.includes('grid') || pattern.includes('items-')) {
    return `${componentName}-${elementType}-layout`;
  }
  
  if (pattern.includes('px-') || pattern.includes('py-') || pattern.includes('h-') || pattern.includes('w-')) {
    return `${componentName}-${elementType}-size`;
  }
  
  if (pattern.includes('font-') || pattern.includes('text-')) {
    return `${componentName}-${elementType}-typography`;
  }
  
  if (pattern.includes('hover:') || pattern.includes('focus:') || pattern.includes('active:')) {
    return `${componentName}-${elementType}-interactive`;
  }
  
  if (pattern.includes('rounded') || pattern.includes('border') || pattern.includes('shadow')) {
    return `${componentName}-${elementType}-decoration`;
  }
  
  // If we couldn't determine the type, use generic
  return `${componentName}-${elementType}-mod`;
} 