import { configManager } from '../config';
import { generateCryptoFromQuark } from './crypto-generator';
import { ModifierEntry } from '../core/types';

export interface ExtractedModifier {
  name: string;
  classes: string;
  remainingClasses: string;
}

/**
 * Extracts modifiers from a set of classes
 */
export function extractModifiers(
  classes: string, 
  componentName: string, 
  elementType: string
): { 
  modifiers: ModifierEntry[]; 
  remainingClasses: string;
} {
  const modifiers: ModifierEntry[] = [];
  let remainingClasses = classes;
  const classesSet = new Set(classes.split(' '));
  
  // Get all patterns from configuration
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
        semantic: `${configManager.getConfig().classNames.semanticPrefix}${semanticName}`
      };
      
      modifiers.push(modifier);
      
      // Remove modifier classes from remaining classes
      patternClasses.forEach(cls => classesSet.delete(cls));
    }
  }
  
  // Convert remaining classes back to string
  remainingClasses = Array.from(classesSet).join(' ');
  
  return { modifiers, remainingClasses };
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