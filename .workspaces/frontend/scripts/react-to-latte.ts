// cd .workspaces/frontend && bun run scripts/react-to-latte.ts
// bun run scripts/react-to-latte.ts <input-file.tsx> <output-file.latte>
import { parse } from '@babel/parser';
//import traverse from '@babel/traverse';
//import * as t from '@babel/types';
import * as fs from 'fs';
import * as path from 'path';
import { transformReactToLatte } from './transforms/react-to-latte-transform';
import { validateReactSyntax } from './validators/react-syntax-validator';
import { resolveImports } from './utils/import-resolver';

/**
 * Converts a React component to a Latte template
 */
async function convertReactToLatte(inputFile: string, outputFile: string) {
  try {
    // Read the React component file
    const source = fs.readFileSync(inputFile, 'utf-8');

    // Syntax validation (check map syntax and other patterns)
    const validationErrors = validateReactSyntax(source);
    if (validationErrors.length > 0) {
      console.error('Syntax validation errors:');
      validationErrors.forEach(err => console.error(`- ${err}`));
      return false;
    }

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
    return true;
  } catch (error) {
    console.error('Conversion error:', error);
    return false;
  }
}

// CLI interface
if (require.main === module) {
  const args = process.argv.slice(2);
  if (args.length < 2) {
    console.log('Usage: bun run scripts/react-to-latte.ts <input-file.tsx> <output-file.latte>');
    process.exit(1);
  }

  convertReactToLatte(args[0], args[1])
    .then(success => process.exit(success ? 0 : 1));
}

export { convertReactToLatte };
