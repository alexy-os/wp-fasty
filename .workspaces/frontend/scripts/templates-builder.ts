// cd .workspaces/frontend && bun run scripts/templates-builder.ts
import { convertReactToLatte } from './react-to-latte';
import { convertDotToArrayNotation } from './transforms/dot-to-array-notation';
import path from 'node:path';
import { glob } from 'glob';
import fs from 'fs/promises';
import { TemplatesBuildConfig } from './config/TemplatesBuildConfig';

async function main() {
  const { uikitDir, templatesDir, engine, dotToArr } = TemplatesBuildConfig;
  const inputDir = path.resolve(uikitDir, engine, 'components');
  const outputDir = path.resolve(templatesDir, engine);

  // Create output directory if it doesn't exist
  await fs.mkdir(outputDir, { recursive: true });

  console.log('Starting conversion...');

  try {
    // Find all template files
    const templateFiles = await glob(`${inputDir}/**/*Template.tsx`);

    if (templateFiles.length === 0) {
      console.log('No template files found');
      return;
    }

    console.log(`Found ${templateFiles.length} template files`);

    // Process each template file
    for (const templateFile of templateFiles) {
      // Extract relative path from inputDir
      const relativePath = path.relative(inputDir, templateFile);
      // Extract directory structure
      const dirStructure = path.dirname(relativePath);

      // Get component name from file name
      const fileName = path.basename(templateFile, '.tsx');
      const componentName = fileName.replace('Template', '');

      // Convert to kebab-case
      const kebabName = componentName
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .toLowerCase();

      // Create output file path
      const outputFilePath = path.join(
        outputDir,
        dirStructure,
        `${kebabName}.${engine}`
      );

      // Ensure output directory exists
      await fs.mkdir(path.dirname(outputFilePath), { recursive: true });

      console.log(`Converting ${templateFile} to ${outputFilePath}`);

      // Convert React to Latte
      await convertReactToLatte(templateFile, outputFilePath);

      // Apply dot to array notation if needed
      if (dotToArr) {
        console.log(`Converting dot notation to array notation for ${outputFilePath}`);
        const content = await fs.readFile(outputFilePath, 'utf8');
        const convertedContent = convertDotToArrayNotation(content);
        await fs.writeFile(outputFilePath, convertedContent);
      }
    }

    console.log('Conversion complete!');
  } catch (error) {
    console.error('Conversion failed:', error);
    process.exit(1);
  }
}

main();
