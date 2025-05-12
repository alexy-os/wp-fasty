// cd .workspaces/frontend && bun scripts/react-jsdom-html.ts
import React from 'react';
import ReactDOMServer from 'react-dom/server';
import fs from 'node:fs';
import path from 'node:path';
import { JSDOM } from 'jsdom';

// Read the source file
const sourceFile = './src/uikits/ui8px/core/source/templates/article.tsx';
const targetFile = './src/uikits/ui8px/core/templates/semantic-article.tsx';
const sourceCode = fs.readFileSync(sourceFile, 'utf-8');

// Extract imports
const importMatch = sourceCode.match(/import\s*{([^}]+)}\s*from\s*["']([^"']+)["']/);
if (!importMatch) {
  throw new Error('No imports found in source file');
}

const componentNames = importMatch[1]
  .split(',')
  .map(name => name.trim())
  .filter(Boolean);
const importPath = importMatch[2];

// Load components
const components = require(path.resolve(__dirname, '../src', importPath.replace('@uikits', 'uikits')));

// Function to get the HTML template of a component
function getComponentTemplate(Component: React.ComponentType<any>, props = {}) {
  const html = ReactDOMServer.renderToStaticMarkup(React.createElement(Component, props));
  const dom = new JSDOM(html);
  const element = dom.window.document.body.firstElementChild;

  if (!element) return null;

  return {
    tagName: element.tagName.toLowerCase(),
    attributes: Array.from(element.attributes).reduce((acc, attr) => {
      acc[attr.name] = attr.value;
      return acc;
    }, {} as Record<string, string>)
  };
}

// Create templates for components
const templates = componentNames.reduce((acc, name) => {
  const Component = components[name];
  if (typeof Component === 'function') {
    const props = {};
    const template = getComponentTemplate(Component, props);
    if (template) {
      acc[name] = template;
      console.log(`Created template for ${name}: ${template.tagName}`);
    }
  }
  return acc;
}, {} as Record<string, any>);

// Function to replace components with consideration of spaces and word boundaries
function transformJSX(code: string, templates: Record<string, any>) {
  let result = code;

  // Sort components by name length (from longest to shortest)
  // to avoid partial replacements
  const sortedComponents = Object.keys(templates)
    .sort((a, b) => b.length - a.length);

  for (const name of sortedComponents) {
    const template = templates[name];
    if (template) {
      // Transform attributes, replacing class with className
      const attributes = Object.entries(template.attributes)
        .map(([key, value]) => {
          // Replace class with className for React
          const reactKey = key === 'class' ? 'className' : key;
          return `${reactKey}="${value}"`;
        })
        .join(' ');

      // Replace opening tags with consideration of spaces and boundaries
      const openRegex = new RegExp(`<${name}(\\s|>|/)`, 'g');
      result = result.replace(openRegex, `<${template.tagName} ${attributes}$1`);

      // Replace closing tags
      const closeRegex = new RegExp(`</${name}>`, 'g');
      result = result.replace(closeRegex, `</${template.tagName}>`);
    }
  }

  // Additional replacement of all remaining class attributes with className
  result = result.replace(/\sclass="/g, ' className="');

  return result;
}

// Transform the code
const transformedCode = transformJSX(sourceCode, templates);

// Create the directory if it doesn't exist
fs.mkdirSync(path.dirname(targetFile), { recursive: true });

// Save the transformed code
fs.writeFileSync(targetFile, transformedCode);
console.log(`Transformed code saved to ${targetFile}`);