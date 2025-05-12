// cd .workspaces/frontend && bun run scripts/templates-builder.ts
import * as transformModules from './transforms';
import path from 'node:path';
import { glob } from 'glob';
import fs from 'fs/promises';
import { TemplatesBuildConfig } from './config/TemplatesBuildConfig';

async function main() {
  const { uikitDir, templatesDir, engine } = TemplatesBuildConfig;
  const inputDir = path.resolve(uikitDir, 'react', 'distribution');
  const outputDir = path.resolve(templatesDir, engine);

  // Get the conversion function depending on the selected engine
  const converterFunctionName = `convertReactTo${engine.charAt(0).toUpperCase() + engine.slice(1)}`;
  const converterFunction = transformModules[converterFunctionName as keyof typeof transformModules];

  if (!converterFunction) {
    throw new Error(`Converter function "${converterFunctionName}" not found in transforms module`);
  }

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

      // Convert React to Engine
      await converterFunction(templateFile, outputFilePath);
    }

    console.log('Conversion complete!');
  } catch (error) {
    console.error('Conversion failed:', error);
    process.exit(1);
  }
}

main();
