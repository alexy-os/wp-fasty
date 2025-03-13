import { PatternContextType } from '../adapters/regex/types/pattern-types';

/**
 * Supported file formats configuration
 */
export interface FileFormatConfig {
  extensions: string[];
  patterns: {
    className: Array<{
      pattern: RegExp;
      name: string;
    }>;
    contextType: PatternContextType;
  };
}

/**
 * Default file formats configuration
 */
export const defaultFormats: Record<string, FileFormatConfig> = {
  react: {
    extensions: ['.tsx', '.jsx'],
    patterns: {
      className: [
        {
          name: 'jsxClassName',
          pattern: /className=["']([^"']+)["']/g
        },
        {
          name: 'dynamicClassName',
          pattern: /className=\{(?:clsx|cn)\(\s*(?:['"`]([^'"`]+)['"`](?:\s*,\s*['"`]([^'"`]+)['"`])*)\s*\)\}/g
        },
        {
          name: 'templateClassName',
          pattern: /className=\{`([^`]+)`\}/g
        },
        {
          name: 'tvVariants',
          pattern: /tv\(\s*\{([\s\S]*?)\}\s*\)/gs
        }
      ],
      contextType: 'jsx' as PatternContextType
    }
  },
  javascript: {
    extensions: ['.js', '.ts'],
    patterns: {
      className: [
        {
          name: 'constClassName',
          pattern: /className:\s*["']([^"']+)["']/g
        },
        {
          name: 'configClassName',
          pattern: /\bclassName:\s*["']([^"']+)["']/g
        }
      ],
      contextType: 'const' as PatternContextType
    }
  },
  php: {
    extensions: ['.php'],
    patterns: {
      className: [
        /*{
          name: 'phpClassName',
          pattern: /className=["']([^"']+)["']/g
        },*/
        {
          name: 'phpClass',
          pattern: /class=["']([^"']+)["']/g
        }
      ],
      contextType: 'php' as PatternContextType
    }
  },
  html: {
    extensions: ['.html', '.hbs', '.handlebars'],
    patterns: {
      className: [
        {
          name: 'htmlClass',
          pattern: /class=["']([^"']+)["']/g
        }
      ],
      contextType: 'html' as PatternContextType
    }
  },
  vue: {
    extensions: ['.vue'],
    patterns: {
      className: [
        {
          name: 'vueClass',
          pattern: /class=["']([^"']+)["']/g
        },
        {
          name: 'vueDynamicClass',
          pattern: /:class=["']\{([^}]+)\}["']/g
        }
      ],
      contextType: 'vue' as PatternContextType
    }
  },
  svelte: {
    extensions: ['.svelte'],
    patterns: {
      className: [
        {
          name: 'svelteClass',
          pattern: /class=["']([^"']+)["']/g
        },
        {
          name: 'svelteDynamicClass',
          pattern: /class:([^=]+)=["']([^"']+)["']/g
        }
      ],
      contextType: 'svelte' as PatternContextType
    }
  }
}; 