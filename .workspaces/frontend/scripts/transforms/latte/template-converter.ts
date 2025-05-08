import { parse } from '@babel/parser';
import * as fs from 'fs';
import * as path from 'path';
import { transformReactToLatte } from './jsx-to-latte';
import { convertDotToArrayNotation } from '@scripts/utils/dot-to-array-notation';
import { validateReactSyntaxOrThrow } from '@scripts/validators/react-syntax-validator';
import { resolveImports } from '@scripts/utils/import-resolver';
import { TemplatesBuildConfig } from '@scripts/config/TemplatesBuildConfig';

/**
 * Converts a React component to a Latte template
 */
async function convertReactToLatte(inputFile: string, outputFile: string) {
  const { dotToArr } = TemplatesBuildConfig;
  try {
    // Read the React component file
    const source = fs.readFileSync(inputFile, 'utf-8');

    // Strict syntax validation - will throw error if validation fails
    validateReactSyntaxOrThrow(source);

    // If we got here, validation passed
    // Parsing code into AST
    const ast = parse(source, {
      sourceType: 'module',
      plugins: ['jsx', 'typescript'],
    });

    // Find and resolve component imports
    const imports = resolveImports(ast, path.dirname(inputFile));

    // Convert AST to Latte syntax
    const latteTemplate = await transformReactToLatte(ast, imports);

    // Write the result to a file
    fs.writeFileSync(outputFile, latteTemplate, 'utf-8');
    console.log(`Successfully converted to ${outputFile}`);

    // Apply dot to array notation if needed
    if (dotToArr) {
      console.log(`Converting dot notation to array notation for ${outputFile}`);
      const content = fs.readFileSync(outputFile, 'utf8');
      const convertedContent = convertDotToArrayNotation(content);
      fs.writeFileSync(outputFile, convertedContent, 'utf-8');
    }

    return true;
  } catch (error: any) {
    console.error('Conversion error:', error.message);
    return false;
  }
}

export { convertReactToLatte };
