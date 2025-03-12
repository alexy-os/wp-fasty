import { EnhancedClassEntry } from '../core/types';

/**
 * Removes duplicate entries by crypto field
 */
export function deduplicateEntries(entries: EnhancedClassEntry[]): EnhancedClassEntry[] {
  const cryptoMap = new Map<string, EnhancedClassEntry>();
  
  entries.forEach(entry => {
    const existingEntry = cryptoMap.get(entry.crypto);
    
    if (!existingEntry) {
      cryptoMap.set(entry.crypto, entry);
    } else {
      existingEntry.components = {
        ...existingEntry.components,
        ...entry.components
      };
    }
  });
  
  return Array.from(cryptoMap.values());
} 