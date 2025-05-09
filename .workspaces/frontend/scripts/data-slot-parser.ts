// cd .workspaces/frontend && bun scripts/data-slot-parser.ts
import fs from 'node:fs';
import path from 'node:path';
import { glob } from 'glob';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import generate from '@babel/generator';
import * as t from '@babel/types';

interface DataSlotParserConfig {
  inputDir: string;
  componentsGlob: string;
  stylesOutputDir: string;
  componentsOutputDir: string;
}

const DEFAULT_CONFIG: DataSlotParserConfig = {
  inputDir: './src/uikits/ui8px/core/source',
  componentsGlob: '**/*.tsx',
  stylesOutputDir: './src/uikits/ui8px/core/styles',
  componentsOutputDir: './src/uikits/ui8px/core/components'
};

class DataSlotParser {
  public config: DataSlotParserConfig;
  private cssFiles: string[] = [];
  public isWatching = false;

  constructor(config: Partial<DataSlotParserConfig> = {}) {
    this.config = { ...DEFAULT_CONFIG, ...config };

    // Check the --watch flag
    const args = process.argv.slice(2);
    this.isWatching = args.includes('--watch');
  }

  public async generateAll(): Promise<void> {
    // Use recursive search for all .tsx files in the specified directory
    const pattern = `${this.config.inputDir.replace(/\\/g, '/')}/${this.config.componentsGlob}`;
    console.log('Glob pattern:', pattern);

    const componentFiles = await glob(pattern);
    console.log(`Found component files: ${componentFiles.length}`);

    this.cssFiles = []; // Reset the list of CSS files before generation

    for (const file of componentFiles) {
      await this.processComponent(file);
    }

    this.generateIndexCss();
  }

  private async processComponent(componentPath: string): Promise<void> {
    try {
      console.log(`Processing component: ${componentPath}`);
      const componentName = this.getComponentName(componentPath);

      // Read the component file content
      const componentContent = fs.readFileSync(componentPath, 'utf-8');

      // Parse with Babel
      const ast = parse(componentContent, {
        sourceType: 'module',
        plugins: ['jsx', 'typescript']
      });

      // Structure to store extracted styles
      const stylesMap: Record<string, string> = {};

      // Traverse the AST and find data-slot attributes and cn() function
      traverse(ast, {
        JSXAttribute(path) {
          // Find data-slot attribute
          if (path.node.name.name === 'data-slot' && t.isStringLiteral(path.node.value)) {
            const slotName = path.node.value.value;

            // Find the parent JSX element
            const jsxElementPath = path.findParent(p => p.isJSXOpeningElement());
            if (!jsxElementPath || !t.isJSXOpeningElement(jsxElementPath.node)) return;

            // Now we have a correctly typed JSXOpeningElement
            const jsxElement = jsxElementPath.node;

            // Find className attribute in this element
            const classNameAttr = jsxElement.attributes.find(
              (attr): attr is t.JSXAttribute =>
                t.isJSXAttribute(attr) &&
                t.isJSXIdentifier(attr.name) &&
                attr.name.name === 'className'
            );

            if (classNameAttr && t.isJSXExpressionContainer(classNameAttr.value)) {
              // Check if this is a call to the cn() function
              const expr = classNameAttr.value.expression;
              if (t.isCallExpression(expr) &&
                (t.isIdentifier(expr.callee) && expr.callee.name === 'cn')) {

                // Get the first argument of cn() - these are our styles
                const firstArg = expr.arguments[0];
                if (t.isStringLiteral(firstArg)) {
                  // Save styles for this slot
                  stylesMap[slotName] = firstArg.value;
                }
              }
            }
          }
        }
      });

      // If we found styles, generate CSS and copy the component
      if (Object.keys(stylesMap).length > 0) {
        this.generateCssFile(componentName, stylesMap);
        this.generateSemanticComponent(componentPath, stylesMap);
      }
    } catch (err) {
      console.error(`Error processing component ${componentPath}:`, err);
    }
  }

  private generateCssFile(componentName: string, stylesMap: Record<string, string>): void {
    let cssContent = '';

    // Generate CSS for each data-slot
    Object.entries(stylesMap).forEach(([slotName, styles]) => {
      if (!styles.trim()) return; // Skip empty styles
      cssContent += `.${slotName} {\n  @apply ${styles};\n}\n\n`;
    });

    if (cssContent.trim()) {
      // Create a directory for styles, preserving the original directory structure
      const stylesDirPath = path.join(this.config.stylesOutputDir, 'components');
      fs.mkdirSync(stylesDirPath, { recursive: true });

      // Path to the CSS file
      const outputFile = path.join(stylesDirPath, `${componentName}.css`);

      // Save the CSS file
      fs.writeFileSync(outputFile, cssContent.trim());
      this.cssFiles.push(outputFile);
      console.log(`Generated CSS: ${outputFile}`);
    } else {
      console.log(`Skipped empty CSS for: ${componentName}`);
    }
  }

  private generateSemanticComponent(componentPath: string, stylesMap: Record<string, string>): void {
    try {
      // Read the original component
      const componentContent = fs.readFileSync(componentPath, 'utf-8');

      // Parse with Babel
      const ast = parse(componentContent, {
        sourceType: 'module',
        plugins: ['jsx', 'typescript']
      });

      // Modify the AST, replacing utility classes with semantic classes
      traverse(ast, {
        JSXAttribute(path) {
          if (path.node.name.name === 'data-slot' && t.isStringLiteral(path.node.value)) {
            const slotName = path.node.value.value;

            // If we have styles for this slot
            if (stylesMap[slotName]) {
              // Find the parent JSX element
              const jsxElementPath = path.findParent(p => p.isJSXOpeningElement());
              if (!jsxElementPath || !t.isJSXOpeningElement(jsxElementPath.node)) return;

              // Now we have a correctly typed JSXOpeningElement
              const jsxElement = jsxElementPath.node;

              // Find className attribute
              const classNameAttr = jsxElement.attributes.find(
                (attr): attr is t.JSXAttribute =>
                  t.isJSXAttribute(attr) &&
                  t.isJSXIdentifier(attr.name) &&
                  attr.name.name === 'className'
              );

              if (classNameAttr && t.isJSXExpressionContainer(classNameAttr.value)) {
                // Check if this is a call to the cn() function
                const expr = classNameAttr.value.expression;
                if (t.isCallExpression(expr) &&
                  (t.isIdentifier(expr.callee) && expr.callee.name === 'cn')) {

                  // Replace the first argument of cn() with the semantic class
                  expr.arguments[0] = t.stringLiteral(slotName);
                }
              }
            }
          }
        }
      });

      // Generate code from the modified AST
      const output = generate(ast, { retainLines: true });

      // Create a directory for semantic components
      const relativePath = path.relative(this.config.inputDir, path.dirname(componentPath));
      const outputDir = path.join(this.config.componentsOutputDir, relativePath);
      fs.mkdirSync(outputDir, { recursive: true });

      // Save the modified component
      const fileName = path.basename(componentPath);
      const outputFile = path.join(outputDir, fileName);
      fs.writeFileSync(outputFile, output.code);
      console.log(`Generated semantic component: ${outputFile}`);
    } catch (err) {
      console.error(`Error generating semantic component for ${componentPath}:`, err);
    }
  }

  private generateIndexCss(): void {
    if (this.cssFiles.length === 0) {
      console.log('No CSS files to include in index.css');
      return;
    }

    const stylesDirPath = path.join(this.config.stylesOutputDir, 'components');
    const indexPath = path.join(stylesDirPath, 'index.css');

    const imports = this.cssFiles.map(file =>
      `@import "./${path.basename(file)}";`
    ).join('\n');

    fs.writeFileSync(indexPath, imports);
    console.log(`Generated index CSS: ${indexPath}`);
  }

  private getComponentName(componentPath: string): string {
    // Get the component name from the file path
    const basename = path.basename(componentPath, path.extname(componentPath));

    // Convert CamelCase or PascalCase to kebab-case for CSS files
    return basename.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
  }

  public startWatching(): void {
    if (this.isWatching) {
      console.log('Watching for changes in component files...');

      const watchDir = path.resolve(this.config.inputDir);
      fs.watch(watchDir, { recursive: true }, async (_, filename) => {
        if (!filename) return;
        if (!filename.endsWith('.tsx')) return;

        const fullPath = path.join(watchDir, filename);
        console.log(`File changed: ${fullPath}`);

        // Small delay for file system stabilization
        setTimeout(async () => {
          try {
            // Process only the changed file
            await this.processComponent(fullPath);
            this.generateIndexCss();
            console.log('Regenerated files for the changed component');
          } catch (err) {
            console.error('Error regenerating files:', err);
          }
        }, 100);
      });
    }
  }
}

// Run the script
const parser = new DataSlotParser();

async function run() {
  try {
    await parser.generateAll();

    // Start watching mode if the --watch flag is specified
    if (parser.isWatching) {
      parser.startWatching();
    }
  } catch (err) {
    console.error('Error running data-slot-parser:', err);
    process.exit(1);
  }
}

run(); 