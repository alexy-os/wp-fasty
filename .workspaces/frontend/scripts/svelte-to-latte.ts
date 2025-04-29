import { parse } from 'svelte/compiler';
import fs from 'node:fs';
import path from 'node:path';
import { glob } from 'glob';

interface LatteGeneratorConfig {
  componentsDir: string;  // Directory with components
  outputDir: string;      // Directory for outputting Latte templates
  componentsGlob: string; // Pattern for finding components
}

class LatteGenerator {
  private config: LatteGeneratorConfig;

  constructor(config: LatteGeneratorConfig) {
    this.config = config;
  }

  public generate(): void {
    const componentFiles = glob.sync(this.config.componentsGlob, {
      cwd: this.config.componentsDir
    });

    for (const file of componentFiles) {
      this.processComponent(file);
    }
  }

  private processComponent(filePath: string): void {
    const fullPath = path.join(this.config.componentsDir, filePath);
    const content = fs.readFileSync(fullPath, 'utf-8');
    // Parse the Svelte component
    const parsed = parse(content);

    // Extract the script
    const scriptContent = parsed.instance?.content?.body || [];

    // Extract the HTML template
    const htmlTemplate = parsed.html;

    // Get the component name from the path
    const componentName = path.basename(filePath, '.svelte');

    // Generate the Latte template
    const latteTemplate = this.convertToLatte(componentName, scriptContent, htmlTemplate);

    // Define the output path
    const outputPath = this.getOutputPath(filePath);

    // Create the directory if it doesn't exist
    fs.mkdirSync(path.dirname(outputPath), { recursive: true });

    // Write the file
    fs.writeFileSync(outputPath, latteTemplate);
    console.log(`Generated: ${outputPath}`);
  }

  private convertToLatte(componentName: string, scriptContent: any[], htmlTemplate: any): string {
    let latteTemplate = '';

    latteTemplate += `{**
 * ${componentName} component
 *
 * Generated from Svelte component
 *}
`;

    // Convert HTML to Latte
    // Replace Svelte directives with Latte 
    // ...

    return latteTemplate;
  }

  private getOutputPath(filePath: string): string {
    const filename = path.basename(filePath, '.svelte');
    const dirname = path.dirname(filePath);

    return path.join(this.config.outputDir, dirname, `${filename}.latte`);
  }
}

const generator = new LatteGenerator({
  componentsDir: './src/components',
  outputDir: './dist/latte',
  componentsGlob: '**/core/*.svelte'
});

generator.generate(); 
