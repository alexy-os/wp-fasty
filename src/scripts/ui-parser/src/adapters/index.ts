import { ClassExtractorAdapter } from './experiment/base-adapter';
//import { DOMExtractorAdapter } from './experiment/dom-adapter';
import { RegexExtractorAdapter } from './regex';
import { configManager } from '../config';

/**
 * Adapter factory for class extraction
 */
export class AdapterFactory {
  private static instance: AdapterFactory;
  private adapters: Map<string, ClassExtractorAdapter> = new Map();
  
  private constructor() {
    //this.registerAdapter('dom', new DOMExtractorAdapter());
    this.registerAdapter('regex', new RegexExtractorAdapter());
  }
  
  /**
   * Get AdapterFactory instance (Singleton)
   */
  public static getInstance(): AdapterFactory {
    if (!AdapterFactory.instance) {
      AdapterFactory.instance = new AdapterFactory();
    }
    return AdapterFactory.instance;
  }
  
  /**
   * Registers a new adapter
   */
  public registerAdapter(type: string, adapter: ClassExtractorAdapter): void {
    this.adapters.set(type, adapter);
  }
  
  /**
   * Gets all registered adapters
   */
  public getAdapters(): ClassExtractorAdapter[] {
    return Array.from(this.adapters.values());
  }
  
  /**
  * Finds an appropriate adapter for the component
   */
  public findAdapter(componentPath: string): ClassExtractorAdapter | null {
        const preferredType = configManager.getExtractor();
    
        const preferredAdapter = this.adapters.get(preferredType);
    if (preferredAdapter?.supportsComponent(componentPath)) {
      return preferredAdapter;
    }
    
        for (const [type, adapter] of this.adapters.entries()) {
      if (type !== preferredType && adapter.supportsComponent(componentPath)) {
        return adapter;
      }
    }
    
    return null;
  }
}

export const adapterFactory = AdapterFactory.getInstance();

//export type { ClassExtractorAdapter } from './experiment/base-adapter';
//export { DOMExtractorAdapter } from './experiment/dom-adapter';
export { RegexExtractorAdapter } from './regex'; 