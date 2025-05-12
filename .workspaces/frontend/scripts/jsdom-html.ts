// cd .workspaces/frontend && bun scripts/jsdom-html.ts
import React from 'react';
import ReactDOMServer from 'react-dom/server';
import fs from 'node:fs';
import path from 'node:path';
import { JSDOM } from 'jsdom';
import {
  Article,
  ArticleHeader,
  ArticleTitle,
  ArticleMeta,
  ArticleTime,
  ArticleContent
} from "../src/uikits/ui8px/core/semantic/components/article";
import * as babel from '@babel/core';
import * as t from '@babel/types';

const config = {
  inputDir: './src/uikits/ui8px/core/source/templates',
  outputDir: './src/uikits/ui8px/core/templates'
};

// Function to get the HTML template of a component
function getComponentTemplate(Component: React.ComponentType<any>, props = {}) {
  const html = ReactDOMServer.renderToStaticMarkup(React.createElement(Component, props));
  const dom = new JSDOM(html);
  const element = dom.window.document.body.firstElementChild;

  if (!element) return null;

  return {
    tagName: element.tagName.toLowerCase(),
    attributes: Array.from(element.attributes).reduce((acc, attr) => {
      // Replace class with className for React
      const attrName = attr.name === 'class' ? 'className' : attr.name;
      acc[attrName] = attr.value;
      return acc;
    }, {} as Record<string, string>)
  };
}

// Get component templates
const componentTemplates = {
  Article: getComponentTemplate(Article),
  ArticleHeader: getComponentTemplate(ArticleHeader),
  ArticleTitle: getComponentTemplate(ArticleTitle),
  ArticleMeta: getComponentTemplate(ArticleMeta),
  ArticleTime: getComponentTemplate(ArticleTime, { dateTime: '' }),
  ArticleContent: getComponentTemplate(ArticleContent)
};

// Babel plugin for transforming components
const transformComponentsPlugin = () => {
  return {
    visitor: {
      JSXElement(path: any) {
        const element = path.node;
        const openingElement = element.openingElement;
        const closingElement = element.closingElement;

        // Check if the element is a custom component
        if (t.isJSXIdentifier(openingElement.name)) {
          const componentName = openingElement.name.name;
          const template = componentTemplates[componentName as keyof typeof componentTemplates];

          if (template) {
            // Create a new JSX identifier for the HTML tag
            const htmlTag = t.jsxIdentifier(template.tagName);

            // Copy all existing attributes
            const attributes = [...openingElement.attributes];

            // Add attributes from the template
            Object.entries(template.attributes).forEach(([name, value]) => {
              // Check if the attribute already exists
              const existingAttr = attributes.find(
                attr => t.isJSXAttribute(attr) && attr.name.name === name
              );

              if (!existingAttr) {
                attributes.push(
                  t.jsxAttribute(
                    t.jsxIdentifier(name),
                    t.stringLiteral(value)
                  )
                );
              }
            });

            // Replace the opening element name
            openingElement.name = htmlTag;
            openingElement.attributes = attributes;

            // Replace the closing element name if it exists
            if (closingElement) {
              closingElement.name = htmlTag;
            }
          }
        }
      },
      // Additional replacement of all class attributes with className
      JSXAttribute(path: any) {
        if (t.isJSXIdentifier(path.node.name) && path.node.name.name === 'class') {
          path.node.name.name = 'className';
        }
      }
    }
  };
};

// Original JSX code
const sourceCode = fs.readFileSync(path.join(config.inputDir, 'article.tsx'), 'utf8');

// Transform the code with Babel
const result = babel.transformSync(sourceCode, {
  plugins: [transformComponentsPlugin],
  parserOpts: {
    plugins: ['jsx', 'typescript']
  }
});

// Write the result to a file
if (result && result.code) {
  fs.writeFileSync(
    path.join(config.inputDir, 'example-article.tsx'),
    result.code
  );
  console.log('Transformation complete!');
} else {
  console.error('Transformation failed!');
}