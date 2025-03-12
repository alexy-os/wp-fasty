export interface Component {
  path: string;
  name: string;
}

export interface ComponentMap {
  [key: string]: Component;
}

export interface EnhancedClassEntry {
  quark: string;
  crypto: string;
  semantic: string;
  classes: string;
  componentName: string;
  elementType: string;
  variants: Record<string, string>;
  isPublic: boolean;
  components: ComponentMap;
} 