// cd .workspaces/frontend && bun scripts/ui-cva-parser.ts
import fs from 'node:fs';
import path from 'node:path';
import { glob } from 'glob';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import generate from '@babel/generator';
import { ObjectProperty, ObjectExpression, StringLiteral } from '@babel/types';
import * as t from '@babel/types';
import { UI8KitConfig, type VariantsParserConfig } from './config/VariantsParserConfig';

interface UICVAParserConfig extends VariantsParserConfig {
  // Директория для семантических компонентов
  componentsOutputDir: string;
}

const DEFAULT_CONFIG: UICVAParserConfig = {
  ...UI8KitConfig,
  componentsOutputDir: './src/uikits/@semantic/src/ui'
};

class UICVAParser {
  /**
  /* inputDir: './src/uikits/n4shadcn/src/ui',
  /* outputDir: './src/assets/css/ui8px/ui',
  /* interfacesGlob: '** /*.tsx',
  /* variantsObject: 'cva',
  /* variantsKey: 'variants',
  /* defaultVariantsKey: 'defaultVariants',
  /* outputIndex: 'variants.css',
  /* componentsOutputDir: './src/uikits/@semantic/src/ui',
  */
  public config: UICVAParserConfig;
  private cssFiles: string[] = [];
  public isWatching = false;

  constructor(config: Partial<UICVAParserConfig> = {}) {
    this.config = { ...DEFAULT_CONFIG, ...config };
    const args = process.argv.slice(2);
    this.isWatching = args.includes('--watch');
  }

  public async generateAll(): Promise<void> {
    // Find all .tsx files recursively
    const pattern = `${this.config.inputDir.replace(/\\/g, '/')}/**/*.tsx`;
    const tsxFiles = await glob(pattern);
    this.cssFiles = [];
    for (const file of tsxFiles) {
      await this.processTSX(file);
    }
    this.generateIndexCss();
  }

  private async processTSX(tsxPath: string): Promise<void> {
    const component = this.getComponentName(tsxPath);
    const variantsIdentifier = `${component}Variants`;
    const tsxContent = fs.readFileSync(tsxPath, 'utf-8');
    const ast = parse(tsxContent, {
      sourceType: 'module',
      plugins: ['typescript', 'jsx']
    });

    let baseStyles = '';
    let variantData: Record<string, Record<string, string>> = {};
    let defaultSize = 'default';

    traverse(ast, {
      VariableDeclaration: (path) => {
        const declaration = path.node.declarations.find(
          d => d.id && 'name' in d.id && d.id.name === variantsIdentifier
        );
        if (!declaration || !declaration.init) return;
        const init = declaration.init;
        if (!('callee' in init) || !('name' in init.callee) ||
          init.callee.name !== this.config.variantsObject) return;

        // base styles
        if ('arguments' in init && init.arguments[0] && isStringLiteral(init.arguments[0])) {
          baseStyles = init.arguments[0].value;
        }

        // variants
        if ('arguments' in init && init.arguments[1] && isObjectExpression(init.arguments[1])) {
          const optionsArg = init.arguments[1];
          const variantsProperty = optionsArg.properties.find(
            prop => isObjectProperty(prop) &&
              prop.key.type === 'Identifier' &&
              prop.key.name === this.config.variantsKey
          ) as ObjectProperty | undefined;

          if (variantsProperty && isObjectExpression(variantsProperty.value)) {
            variantsProperty.value.properties.forEach(categoryProp => {
              if (!isObjectProperty(categoryProp) || categoryProp.key.type !== 'Identifier') return;
              const category = categoryProp.key.name;
              variantData[category] = {};
              if (!isObjectExpression(categoryProp.value)) return;
              categoryProp.value.properties.forEach(variantProp => {
                if (!isObjectProperty(variantProp) || variantProp.key.type !== 'Identifier') return;
                const variantName = variantProp.key.name;
                const variantValue = variantProp.value;
                if (isStringLiteral(variantValue)) {
                  variantData[category][variantName] = variantValue.value;
                }
              });
            });
          }

          // defaultVariants
          const defaultVariantsProperty = optionsArg.properties.find(
            prop => isObjectProperty(prop) &&
              prop.key.type === 'Identifier' &&
              prop.key.name === this.config.defaultVariantsKey
          ) as ObjectProperty | undefined;

          if (defaultVariantsProperty && isObjectExpression(defaultVariantsProperty.value)) {
            const sizeProperty = defaultVariantsProperty.value.properties.find(
              prop => isObjectProperty(prop) &&
                prop.key.type === 'Identifier' &&
                prop.key.name === 'size'
            ) as ObjectProperty | undefined;
            if (sizeProperty && isStringLiteral(sizeProperty.value)) {
              defaultSize = sizeProperty.value.value;
            }
          }
        }
      }
    });

    // Generate CSS
    let baseStylesContent = baseStyles ? `@apply ${baseStyles};` : '';
    const sizeVariants = variantData['size'];
    if (sizeVariants && sizeVariants[defaultSize]) {
      baseStylesContent += sizeVariants[defaultSize] ? ` @apply ${sizeVariants[defaultSize]};` : '';
    }
    let cssContent = baseStylesContent ? `.${component} {\n  ${baseStylesContent}\n}\n\n` : '';

    Object.entries(variantData).forEach(([category, variants]) => {
      Object.entries(variants).forEach(([variantName, styles]) => {
        if (category === 'size' && variantName === defaultSize) return;
        if (!styles.trim()) return;
        cssContent += `.${component}-${variantName} {\n  @apply ${styles};\n}\n\n`;
      });
    });

    // Save CSS
    if (cssContent.trim()) {
      const outputFile = path.join(this.config.outputDir, `${component}.css`);
      fs.mkdirSync(this.config.outputDir, { recursive: true });
      fs.writeFileSync(outputFile, cssContent.trim());
      this.cssFiles.push(outputFile);
      console.log(`Generated CSS: ${outputFile}`);
    } else {
      console.log(`Skipped empty CSS for: ${component}`);
    }

    // Generate semantic component without data-slot attributes
    this.generateSemanticComponent(tsxPath);
  }

  private generateSemanticComponent(tsxPath: string): void {
    try {
      // Read the original component
      const tsxContent = fs.readFileSync(tsxPath, 'utf-8');

      // Parse with Babel
      const ast = parse(tsxContent, {
        sourceType: 'module',
        plugins: ['typescript', 'jsx']
      });

      // Track if any data-slot attributes were found and removed
      let dataSlotFound = false;

      // Modify the AST to remove data-slot attributes
      traverse(ast, {
        JSXAttribute(path) {
          // If this is a data-slot attribute, remove it
          if (path.node.name.name === 'data-slot') {
            dataSlotFound = true;
            path.remove();
          }
        }
      });

      // Only generate a new version if we found and removed data-slot attributes
      if (dataSlotFound) {
        // Generate code from the modified AST
        const output = generate(ast, { retainLines: true });

        // Create a directory for semantic components
        const relativePath = path.relative(this.config.inputDir, path.dirname(tsxPath));
        const outputDir = path.join(this.config.componentsOutputDir, relativePath);
        fs.mkdirSync(outputDir, { recursive: true });

        // Save the modified component
        const fileName = path.basename(tsxPath);
        const outputFile = path.join(outputDir, fileName);
        fs.writeFileSync(outputFile, output.code);
        console.log(`Generated semantic component: ${outputFile}`);
      }
    } catch (err) {
      console.error(`Error generating semantic component for ${tsxPath}:`, err);
    }
  }

  private generateIndexCss(): void {
    const indexPath = path.join(this.config.outputDir, this.config.outputIndex);
    const imports = this.cssFiles.map(file =>
      `@import "./${path.basename(file)}";`
    ).join('\n');
    fs.writeFileSync(indexPath, imports);
    console.log(`Generated: ${indexPath}`);
  }

  private getComponentName(tsxPath: string): string {
    // Get component name from file name (e.g. button.tsx -> button)
    return path.basename(tsxPath, '.tsx');
  }
}

// Type guards
function isObjectProperty(node: any): node is ObjectProperty {
  return node && node.type === 'ObjectProperty';
}
function isObjectExpression(node: any): node is ObjectExpression {
  return node && node.type === 'ObjectExpression';
}
function isStringLiteral(node: any): node is StringLiteral {
  return node && node.type === 'StringLiteral';
}

// Run
const parser = new UICVAParser();

async function run() {
  await parser.generateAll();

  if (parser.isWatching) {
    console.log('Watching for changes in .tsx files...');
    const watchDir = path.resolve(parser.config.inputDir);
    fs.watch(watchDir, { recursive: true }, async (filename) => {
      if (!filename) return;
      if (!filename.endsWith('.tsx')) return;
      console.log(`File changed: ${filename}`);
      setTimeout(async () => {
        try {
          await parser.generateAll();
          console.log('Regenerated all CSS files');
        } catch (err) {
          console.error('Error regenerating CSS:', err);
        }
      }, 100);
    });
  }
}

run().catch(console.error);
