import fs from 'fs';
import path from 'path';
import { parse } from 'svelte/compiler';

// Function to extract styles from a Svelte component
function extractCSSFromSvelte(filePath) {
  try {
    const source = fs.readFileSync(filePath, 'utf-8');
    const parsed = parse(source);

    if (!parsed.css) {
      return '';
    }

    // Use the verified method for extracting CSS
    if (parsed.css.content && parsed.css.content.styles) {
      return parsed.css.content.styles.trim();
    }

    return '';
  } catch (error) {
    console.error(`Error processing ${filePath}:`, error.message);
    return '';
  }
}

// Function to save CSS to a separate file
function saveCSSToFile(componentPath, outputDir) {
  const componentName = path.basename(componentPath, '.svelte');
  const css = extractCSSFromSvelte(componentPath);

  if (!css) {
    console.log(`No CSS found in ${componentName}`);
    return null;
  }

  // Create directory if it doesn't exist
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  // Output path for CSS
  const outputPath = path.join(outputDir, `${componentName.toLowerCase()}.css`);

  // Write CSS to file
  fs.writeFileSync(outputPath, css);

  console.log(`CSS extracted from ${componentName} to ${outputPath}`);
  return outputPath;
}

// Process all components in the directory
function processAllComponents(srcDir, outputDir) {
  if (!fs.existsSync(srcDir)) {
    console.error(`Source directory ${srcDir} does not exist`);
    return;
  }

  const files = fs.readdirSync(srcDir);
  const cssFiles = [];

  for (const file of files) {
    const filePath = path.join(srcDir, file);
    const stats = fs.statSync(filePath);

    if (stats.isDirectory()) {
      // Recursively process subdirectories
      const subDirOutput = path.join(outputDir, file);
      const subFiles = processAllComponents(filePath, subDirOutput);
      if (subFiles && subFiles.length) {
        cssFiles.push(...subFiles);
      }
    } else if (file.endsWith('.svelte')) {
      const cssPath = saveCSSToFile(filePath, outputDir);
      if (cssPath) {
        cssFiles.push(cssPath);
      }
    }
  }

  // Create index file for importing all styles
  if (cssFiles.length > 0) {
    const indexPath = path.join(outputDir, 'index.css');
    const imports = cssFiles.map(file =>
      `@import "${path.relative(outputDir, file).replace(/\\/g, '/')}";`
    ).join('\n');
    fs.writeFileSync(indexPath, imports);
    console.log(`Created index file at ${indexPath}`);
  }

  return cssFiles;
}

// Run CSS extraction
const srcDir = './src/components/ui';
const cssOutputDir = './src/assets/css/components';

try {
  processAllComponents(srcDir, cssOutputDir);
  console.log('CSS extraction completed successfully!');
} catch (error) {
  console.error('Error during CSS extraction:', error);
  process.exit(1);
}