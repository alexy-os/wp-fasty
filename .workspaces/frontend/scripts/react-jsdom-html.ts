// cd .workspaces/frontend && bun scripts/react-jsdom-html.ts
import React from 'react';
import ReactDOMServer from 'react-dom/server';
import fs from 'node:fs';
import path from 'node:path';
import { JSDOM } from 'jsdom';
import { glob } from 'glob';
import * as babelParser from '@babel/parser';
import traverse from '@babel/traverse';
import generate from '@babel/generator';
import * as t from '@babel/types';

// Configuration
const sourceDir = './src/templates/react/source';
const targetDir = './src/templates/react/distribution';

// Function to get the HTML template of a component
function getComponentTemplate(Component: React.ComponentType<any>, props = {}) {
  const html = ReactDOMServer.renderToStaticMarkup(React.createElement(Component, props));
  const dom = new JSDOM(html);
  const element = dom.window.document.body.firstElementChild;

  if (!element) return null;

  return {
    tagName: element.tagName.toLowerCase(),
    attributes: Array.from(element.attributes).reduce((acc, attr) => {
      // Skip data-slot attribute as we'll remove it later
      if (attr.name !== 'data-slot') {
        acc[attr.name] = attr.value;
      }
      return acc;
    }, {} as Record<string, string>)
  };
}

// Transform JSX using Babel AST
function transformJSXWithAST(code: string, templates: Record<string, any>, importMatch?: RegExpMatchArray) {
  // Parse code to AST
  const ast = babelParser.parse(code, {
    sourceType: 'module',
    plugins: ['jsx', 'typescript'],
  });

  // Helper function to recursively replace JSX elements
  function replaceJSXElements(node: t.Node) {
    if (t.isJSXElement(node)) {
      const opening = node.openingElement;
      if (t.isJSXIdentifier(opening.name)) {
        const name = opening.name.name;
        const template = templates[name];
        if (template) {
          // Replace tag name
          opening.name.name = template.tagName;
          if (node.closingElement && t.isJSXIdentifier(node.closingElement.name)) {
            node.closingElement.name.name = template.tagName;
          }
          // Remove data-slot, class, className attributes
          opening.attributes = opening.attributes.filter(attr => {
            if (!t.isJSXAttribute(attr)) return true;
            const attrName = attr.name.name;
            return attrName !== 'data-slot' && attrName !== 'class' && attrName !== 'className';
          });
          // Add attributes from template
          Object.entries(template.attributes as Record<string, string>).forEach(([key, value]) => {
            const reactKey = key === 'class' ? 'className' : key;
            opening.attributes.push(t.jsxAttribute(
              t.jsxIdentifier(reactKey),
              t.stringLiteral(value)
            ));
          });
        }
      }
      // Recursively process children
      node.children.forEach(child => {
        if (t.isJSXElement(child) || t.isJSXFragment(child)) {
          replaceJSXElements(child);
        }
        // Also process JSXExpressionContainer
        if (t.isJSXExpressionContainer(child) && child.expression) {
          if (t.isJSXElement(child.expression) || t.isJSXFragment(child.expression)) {
            replaceJSXElements(child.expression);
          }
        }
      });
    } else if (t.isJSXFragment(node)) {
      node.children.forEach(child => {
        if (t.isJSXElement(child) || t.isJSXFragment(child)) {
          replaceJSXElements(child);
        }
        if (t.isJSXExpressionContainer(child) && child.expression) {
          if (t.isJSXElement(child.expression) || t.isJSXFragment(child.expression)) {
            replaceJSXElements(child.expression);
          }
        }
      });
    }
  }

  // Traverse AST and replace JSX elements recursively
  traverse(ast, {
    JSXElement(path) {
      replaceJSXElements(path.node);
    },
    JSXFragment(path) {
      replaceJSXElements(path.node);
    }
  });

  // Remove import statement if present
  if (importMatch) {
    const [fullImport] = importMatch;
    code = code.replace(fullImport, '');
  }

  // Generate code from AST
  const output = generate(ast, {}, code).code;
  // Remove leading semicolons and extra empty lines
  return output.replace(/^(;|\s)+/g, '').replace(/\n\s*\n/g, '\n');
}

// Process a single file
async function processFile(sourceFile: string) {
  const relativePath = path.relative(sourceDir, sourceFile);
  const targetFile = path.join(targetDir, relativePath);

  console.log(`Processing ${sourceFile}...`);

  try {
    const sourceCode = fs.readFileSync(sourceFile, 'utf-8');

    // Extract all import statements of the form import { ... } from '...';
    const importRegex = /import\s*{([^}]+)}\s*from\s*["']([^"']+)["']/g;
    let importMatch;
    let allComponentNames: string[] = [];
    let allTemplates: Record<string, any> = {};
    let codeWithoutImports = sourceCode;

    while ((importMatch = importRegex.exec(sourceCode)) !== null) {
      const componentNames = importMatch[1]
        .split(',')
        .map(name => name.trim())
        .filter(Boolean);
      const importPath = importMatch[2];
      let components;
      try {
        components = require(path.resolve(__dirname, '../src', importPath.replace('@uikits', 'uikits')));
      } catch (e) {
        // Skip non-component imports (e.g. context, libraries)
        continue;
      }
      componentNames.forEach(name => {
        const Component = components[name];
        if (typeof Component === 'function') {
          const props = {};
          const template = getComponentTemplate(Component, props);
          if (template) {
            allTemplates[name] = template;
            allComponentNames.push(name);
            console.log(`Created template for ${name}: ${template.tagName}`);
          }
        }
      });
      // Remove this import from code
      codeWithoutImports = codeWithoutImports.replace(importMatch[0], '');
    }

    // Transform the code using AST
    const transformedCode = transformJSXWithAST(codeWithoutImports, allTemplates);

    // Create the directory if it doesn't exist
    fs.mkdirSync(path.dirname(targetFile), { recursive: true });

    // Save the transformed code
    fs.writeFileSync(targetFile, transformedCode);
    console.log(`Transformed ${sourceFile} -> ${targetFile}`);
  } catch (error) {
    console.error(`Error processing file ${sourceFile}:`, error);
  }
}

// Main function to process all files
async function main() {
  try {
    // Find all .tsx files in the source directory
    const files = await glob(`${sourceDir}/**/*.tsx`);
    console.log(`Found ${files.length} .tsx files to process`);

    // Process each file
    for (const file of files) {
      await processFile(file);
    }

    console.log('All files processed successfully');
  } catch (error) {
    console.error('Error processing files:', error);
    process.exit(1);
  }
}

// Run the main function
main();