// cd .workspaces/frontend && bun run scripts/fixed-test.ts
import fs from 'fs';

// Create a simple test file
const testFile = './test-input.tsx';
const outputFile = './test-output.tsx';

// Simple JSX with components
const testCode = `
import { Article, ArticleHeader } from "@uikits/ui8px/core/source/clsx/components/article";

function Test() {
  return (
    <Article>
      <ArticleHeader>
        <h2>Title</h2>
      </ArticleHeader>
    </Article>
  );
}
`;

// Component map
const componentMap = {
  "Article": {
    "tagName": "article",
    "attributes": {
      "data-slot": "article",
      "className": "article"
    }
  },
  "ArticleHeader": {
    "tagName": "header",
    "attributes": {
      "data-slot": "article-header",
      "className": "article-header"
    }
  }
};

// Write the test file
fs.writeFileSync(testFile, testCode);

// Function to replace components with consideration of spaces and word boundaries
function transformJSX(code: string, componentMap: Record<string, any>) {
  let result = code;

  // Sort components by name length (from longest to shortest)
  // to avoid partial replacements
  const sortedComponents = Object.keys(componentMap)
    .sort((a, b) => b.length - a.length);

  for (const componentName of sortedComponents) {
    const template = componentMap[componentName];

    // Create a string of attributes
    const attributesStr = Object.entries(template.attributes)
      .map(([name, value]) => `${name}="${value}"`)
      .join(' ');

    // Replace opening tags with consideration of spaces and word boundaries
    // Use a regex with word boundaries
    const openRegex = new RegExp(`<${componentName}(\\s|>|/)`, 'g');
    result = result.replace(openRegex, `<${template.tagName} ${attributesStr}$1`);

    // Replace closing tags
    const closeRegex = new RegExp(`</${componentName}>`, 'g');
    result = result.replace(closeRegex, `</${template.tagName}>`);
  }

  return result;
}

// Transform the code
const transformedCode = transformJSX(testCode, componentMap);

// Save the result
fs.writeFileSync(outputFile, transformedCode);
console.log(`Test completed. Check file ${outputFile}`);
console.log('Original code:', testCode);
console.log('Transformed code:', transformedCode);
