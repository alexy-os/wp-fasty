// cd .workspaces/frontend && bun run simple-transform.ts
import fs from 'fs';
import path from 'path';

// File paths
const sourceFile = './src/uikits/ui8px/core/source/templates/article.tsx';
const mapFile = './src/uikits/ui8px/core/source/templates/article-map.json';
const outputFile = './src/uikits/ui8px/core/templates/transformed-article.tsx';

// Read files
let sourceCode = fs.readFileSync(sourceFile, 'utf8');
const componentMap = JSON.parse(fs.readFileSync(mapFile, 'utf8'));

// Simple string replacement for each component
Object.keys(componentMap).forEach(componentName => {
  const template = componentMap[componentName];

  // Create attribute strings
  const attributesStr = Object.entries(template.attributes)
    .map(([name, value]) => `${name}="${value}"`)
    .join(' ');

  // Replace opening tags (simple string replacement)
  sourceCode = sourceCode.split(`<${componentName}`).join(`<${template.tagName} ${attributesStr}`);

  // Replace closing tags
  sourceCode = sourceCode.split(`</${componentName}>`).join(`</${template.tagName}>`);
});

// Create the directory if it doesn't exist
const outputDir = path.dirname(outputFile);
if (!fs.existsSync(outputDir)) {
  fs.mkdirSync(outputDir, { recursive: true });
}

// Save the result
fs.writeFileSync(outputFile, sourceCode);
console.log(`Transformation completed. Result saved to ${outputFile}`);