import fs from 'fs';
import path from 'path';
import { parse } from 'svelte/compiler';

// Read the Svelte component file
const componentPath = path.resolve('./Button.svelte');
const componentSource = fs.readFileSync(componentPath, 'utf-8');

// Parse the Svelte component
const parsed = parse(componentSource);

// Extract the TV configuration
const scriptContent = parsed.instance.content.body
  .find(node =>
    node.type === 'VariableDeclaration' &&
    node.declarations.some(decl => decl.id?.name === 'button')
  );

const buttonConfig = scriptContent.declarations
  .find(decl => decl.id?.name === 'button')
  .init.arguments[0];

// Extract base and variants
const baseClasses = buttonConfig.properties
  .find(prop => prop.key.name === 'base')
  .value.value;

const variantsObj = buttonConfig.properties
  .find(prop => prop.key.name === 'variants')
  .value.properties;

// Generate CSS content
let cssContent = `.button {\n  @apply ${baseClasses};\n}\n\n`;

// Process variants
variantsObj.forEach(variantGroup => {
  const groupName = variantGroup.key.name;
  const variants = variantGroup.value.properties;

  variants.forEach(variant => {
    const variantName = variant.key.name;
    const variantClasses = variant.value.value;

    // Skip 'default' in class naming if it's the default size
    const className = variantName === 'default' && groupName === 'size'
      ? `.button`
      : `.button-${variantName}`;

    cssContent += `${className} {\n  @apply ${variantClasses};\n}\n\n`;
  });
});

// Write to CSS file
fs.writeFileSync(
  path.resolve('./Button.css'),
  cssContent.trim(),
  'utf-8'
);

console.log('Button.css has been generated successfully.');

// .workspaces/frontend/src/components/ui/button/Button.css.js