import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import * as t from '@babel/types';
import { reactValidationRules } from '../config/ValidationRules';

/**
 * Validates React component syntax using AST parser and validation rules
 * @returns Array of errors. Empty array means validation passed.
 */
export function validateReactSyntax(code: string): string[] {
  const errors: string[] = [];

  try {
    // Parse code to AST
    const ast = parse(code, {
      sourceType: 'module',
      plugins: ['jsx', 'typescript'],
    });

    console.log('Validating React syntax...');

    // Apply each validation rule
    reactValidationRules.forEach(rule => {
      traverse(ast, {
        // Check map expressions
        CallExpression(path) {
          if (
            t.isMemberExpression(path.node.callee) &&
            t.isIdentifier(path.node.callee.property) &&
            path.node.callee.property.name === 'map'
          ) {
            if (rule.name.startsWith('map-') || rule.name === 'index-in-map') {
              if (!rule.validate(path)) {
                errors.push(rule.errorMessage(path));
              }
            }
          }
        },

        // Check components
        ArrowFunctionExpression(path) {
          if (rule.name === 'props-destructuring') {
            if (!rule.validate(path)) {
              errors.push(rule.errorMessage(path));
            }
          }
        }
      });
    });

  } catch (error) {
    // If parsing fails, it's definitely invalid
    errors.push(`Invalid syntax: ${(error as Error).message}`);
  }

  // Display results
  if (errors.length > 0) {
    console.log(`Found ${errors.length} validation issues`);
  } else {
    console.log('Validation passed successfully');
  }

  return errors;
}

/**
 * Validates React component syntax and throws error if validation fails
 * @throws Error with validation messages
 */
export function validateReactSyntaxOrThrow(code: string): void {
  const errors = validateReactSyntax(code);

  if (errors.length > 0) {
    throw new Error(`Syntax validation failed:\n- ${errors.join('\n- ')}`);
  }
}
