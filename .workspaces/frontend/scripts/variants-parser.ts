import fs from 'node:fs';
import path from 'node:path';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import { Expression, ObjectProperty, ObjectExpression, StringLiteral } from '@babel/types';
import { VariantsConfig, VariantsParserConfig } from '../src/components/ui/button';

class VariantsParser {
  private config: VariantsParserConfig;
  private baseStyles: string = '';
  private variantData: Record<string, Record<string, string>> = {};
  private defaultSize: string;

  constructor(config: Partial<VariantsParserConfig> = {}) {
    this.config = { ...VariantsConfig, ...config };
    this.defaultSize = this.config.defaultSize;
  }

  /**
   * Main execution method
   */
  public generate(): void {
    this.parseInterfaceFile();
    const cssContent = this.generateCSS();
    this.writeOutputFile(cssContent);
  }

  /**
   * Parse the interface file and extract styles
   */
  private parseInterfaceFile(): void {
    try {
      // Read and parse file
      const interfacePath = path.resolve(this.config.inputPath);
      const interfaceContent = fs.readFileSync(interfacePath, 'utf-8');
      const ast = parse(interfaceContent, {
        sourceType: 'module',
        plugins: ['typescript']
      });

      // Extract data from AST
      traverse(ast, {
        VariableDeclaration: (path) => {
          const declaration = path.node.declarations.find(
            d => d.id && 'name' in d.id && d.id.name === this.config.variantsIdentifier
          );

          if (!declaration || !declaration.init) return;

          const init = declaration.init;
          if (!('callee' in init) || !('name' in init.callee) ||
            init.callee.name !== this.config.variantsObject) return;

          this.extractBaseStyles(init);
          this.extractVariants(init);
          this.extractDefaultVariants(init);
        }
      });
    } catch (error) {
      console.error('Error parsing interface file:', error);
      throw error;
    }
  }

  /**
   * Extract base styles from cva arguments
   */
  private extractBaseStyles(init: Expression): void {
    if (!('arguments' in init) || !init.arguments[0]) return;

    const baseStyleArg = init.arguments[0];
    if (isStringLiteral(baseStyleArg)) {
      this.baseStyles = baseStyleArg.value;
    }
  }

  /**
   * Extract variants from cva arguments
   */
  private extractVariants(init: Expression): void {
    if (!('arguments' in init) || init.arguments.length < 2) return;

    const optionsArg = init.arguments[1];
    if (!isObjectExpression(optionsArg)) return;

    const variantsProperty = optionsArg.properties.find(
      prop => isObjectProperty(prop) &&
        prop.key.type === 'Identifier' &&
        prop.key.name === this.config.variantsKey
    ) as ObjectProperty | undefined;

    if (!variantsProperty) return;

    const variantsValue = variantsProperty.value;
    if (!isObjectExpression(variantsValue)) return;

    variantsValue.properties.forEach(categoryProp => {
      if (!isObjectProperty(categoryProp) ||
        categoryProp.key.type !== 'Identifier') return;

      const category = categoryProp.key.name;
      this.variantData[category] = {};

      const categoryValue = categoryProp.value;
      if (!isObjectExpression(categoryValue)) return;

      categoryValue.properties.forEach(variantProp => {
        if (!isObjectProperty(variantProp) ||
          variantProp.key.type !== 'Identifier') return;

        const variantName = variantProp.key.name;
        const variantValue = variantProp.value;

        if (isStringLiteral(variantValue)) {
          this.variantData[category][variantName] = variantValue.value;
        }
      });
    });
  }

  /**
   * Extract default variants from cva arguments
   */
  private extractDefaultVariants(init: Expression): void {
    if (!('arguments' in init) || init.arguments.length < 2) return;

    const optionsArg = init.arguments[1];
    if (!isObjectExpression(optionsArg)) return;

    const defaultVariantsProperty = optionsArg.properties.find(
      prop => isObjectProperty(prop) &&
        prop.key.type === 'Identifier' &&
        prop.key.name === this.config.defaultVariantsKey
    ) as ObjectProperty | undefined;

    if (!defaultVariantsProperty) return;

    const defaultVariantsValue = defaultVariantsProperty.value;
    if (!isObjectExpression(defaultVariantsValue)) return;

    const sizeProperty = defaultVariantsValue.properties.find(
      prop => isObjectProperty(prop) &&
        prop.key.type === 'Identifier' &&
        prop.key.name === 'size'
    ) as ObjectProperty | undefined;

    if (sizeProperty && isStringLiteral(sizeProperty.value)) {
      this.defaultSize = sizeProperty.value.value;
    }
  }

  /**
   * Generate CSS content with semantic naming
   */
  private generateCSS(): string {
    // Combine base styles with default size
    let baseStylesContent = this.baseStyles;
    const sizeVariants = this.variantData['size'];

    if (sizeVariants && sizeVariants[this.defaultSize]) {
      baseStylesContent += ' ' + sizeVariants[this.defaultSize];
    }

    // Generate base class
    let cssContent = `.${this.config.componentName} {\n  @apply ${baseStylesContent};\n}\n\n`;

    // Add variant classes
    Object.entries(this.variantData).forEach(([category, variants]) => {
      Object.entries(variants).forEach(([variantName, styles]) => {
        // Skip default size as it's already in the base class
        if (category === 'size' && variantName === this.defaultSize) return;
        cssContent += `.${this.config.componentName}-${variantName} {\n  @apply ${styles};\n}\n\n`;
      });
    });

    return cssContent.trim();
  }

  /**
   * Write CSS to output file
   */
  private writeOutputFile(cssContent: string): void {
    try {
      const outputPath = path.resolve(this.config.outputPath);
      fs.writeFileSync(outputPath, cssContent);
      console.log(`CSS file generated at ${outputPath}`);
    } catch (error) {
      console.error('Error writing output file:', error);
      throw error;
    }
  }
}

// Type guards for Babel AST
function isObjectProperty(node: any): node is ObjectProperty {
  return node && node.type === 'ObjectProperty';
}

function isObjectExpression(node: any): node is ObjectExpression {
  return node && node.type === 'ObjectExpression';
}

function isStringLiteral(node: any): node is StringLiteral {
  return node && node.type === 'StringLiteral';
}

// Create and run parser instance
const parser = new VariantsParser();
parser.generate();