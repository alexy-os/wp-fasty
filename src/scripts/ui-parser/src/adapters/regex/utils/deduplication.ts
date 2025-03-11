import { EnhancedClassEntry } from '../types';

export function deduplicateEntries(entries: EnhancedClassEntry[]): EnhancedClassEntry[] {
  const uniqueMap = new Map<string, EnhancedClassEntry>();

  for (const entry of entries) {
    const key = `${entry.classes}-${Object.entries(entry.variants).sort().join('-')}`;
    
    if (!uniqueMap.has(key)) {
      uniqueMap.set(key, entry);
    } else {
      
      const existing = uniqueMap.get(key)!;
      existing.components = {
        ...existing.components,
        ...entry.components
      };
    }
  }

  return Array.from(uniqueMap.values());
} 