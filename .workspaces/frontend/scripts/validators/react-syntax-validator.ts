import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import * as t from '@babel/types';

/**
 * Validates React component syntax using AST parser
 */
export function validateReactSyntax(code: string): string[] {
  const errors: string[] = [];

  try {
    // Parse code to AST
    const ast = parse(code, {
      sourceType: 'module',
      plugins: ['jsx', 'typescript'],
    });

    // Traverse AST to find patterns
    traverse(ast, {
      // Check map patterns
      CallExpression(path) {
        if (
          t.isMemberExpression(path.node.callee) &&
          t.isIdentifier(path.node.callee.property) &&
          path.node.callee.property.name === 'map'
        ) {
          // Check if the callback is an arrow function
          const arg = path.node.arguments[0];
          if (t.isArrowFunctionExpression(arg)) {
            // Check parameter type annotation
            const param = arg.params[0];
            if (
              t.isIdentifier(param) &&
              (!param.typeAnnotation ||
                !t.isTSAnyKeyword(param.typeAnnotation))
            ) {
              errors.push(`The map parameter must have the type ": any" for parameter: ${(param as any).name}`);
            }

            // Check for index parameter
            if (arg.params.length > 1) {
              errors.push(`It is not recommended to use an index in map, use key={item.id} instead`);
            }

            // Check for key prop in JSX elements within map
            if (t.isJSXElement(arg.body)) {
              const hasKey = arg.body.openingElement.attributes.some(attr =>
                t.isJSXAttribute(attr) && attr.name.name === 'key'
              );

              if (!hasKey) {
                errors.push(`Missing key prop in JSX element within map`);
              }
            }
          }
        }
      },

      // Check for redundant checks
      LogicalExpression(path) {
        if (
          path.node.operator === '&&' &&
          t.isIdentifier(path.node.left) &&
          t.isMemberExpression(path.node.right) &&
          t.isIdentifier(path.node.right.object) &&
          path.node.left.name === path.node.right.object.name
        ) {
          errors.push(`Redundant check: use a simple variable check: ${path.node.left.name}`);
        }
      },

      // Check props destructuring
      ArrowFunctionExpression(path) {
        // Check if this is a component function
        if (path.parent && t.isVariableDeclarator(path.parent)) {
          const params = path.node.params;

          if (
            params.length !== 1 ||
            !t.isObjectPattern(params[0])
          ) {
            errors.push('The component must use destructuring of props: const Component = ({ prop1, prop2 }) => {...}');
          }
        }
      }
    });

  } catch (error) {
    // If parsing fails, it's definitely invalid
    errors.push(`Invalid syntax: ${(error as Error).message}`);
  }

  return errors;
}
