import { ExtractorPatterns, PatternContextType } from './types';
import { configManager } from '../../config';


export const CLASS_PATTERNS: ExtractorPatterns = {
  jsxClassName: {
    pattern: /className=["']([^"']+)["']/g,
    contextType: 'jsx'
  },
  constClassName: {
    pattern: /className:\s*["']([^"']+)["']/g,
    contextType: 'const'
  },
  configClassName: {
    pattern: /\bclassName:\s*["']([^"']+)["']/g,
    contextType: 'config'
  },
  dynamicClassName: {
    pattern: /className=\{(?:clsx|cn)\(\s*(?:['"`]([^'"`]+)['"`](?:\s*,\s*['"`]([^'"`]+)['"`])*)\s*\)\}/g,
    contextType: 'dynamic'
  },
  templateClassName: {
    pattern: /className=\{`([^`]+)`\}/g,
    contextType: 'template'
  },
  tvVariants: {
    pattern: /tv\(\s*\{([\s\S]*?)\}\s*\)/gs,
    contextType: 'config'
  },
  phpClassName: {
    pattern: /className=["']([^"']+)["']/g,
    contextType: 'php'
  },
  phpClass: {
    pattern: /class=["']([^"']+)["']/g,
    contextType: 'php'
  }
} as const;

// Добавляем паттерны из конфигурации
Object.values(configManager.getConfig().formats).forEach(format => {
  format.patterns.className.forEach((pattern, index) => {
    CLASS_PATTERNS[`${format.patterns.contextType}${index}`] = {
      pattern,
      contextType: format.patterns.contextType as PatternContextType
    };
  });
}); 