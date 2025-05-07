import traverse from '@babel/traverse';
import * as t from '@babel/types';
import { transformJSXToLatte } from './jsx-to-latte';

/**
 * Generates Latte template header with documentation
 */
function generateLatteHeader(params: string[]): string {
  let header = '{**\n';
  header += ' * Component Template\n';
  header += ' *\n';
  header += ' * Required context:\n';

  if (params.length > 0) {
    params.forEach(param => {
      header += ` * - ${param}: Component parameter\n`;
    });
  } else {
    header += ' * - No parameters required\n';
  }

  header += ' *}\n\n';
  return header;
}

/**
 * Converts the AST of a React component to a Latte template
 */
export async function transformReactToLatte(ast: any, imports: Record<string, any>) {
  let latteTemplate = '';
  let componentParams: string[] = [];

  // Extract the component parameters
  traverse(ast, {
    ArrowFunctionExpression(path) {
      // Find the main component (the first top-level arrow function)
      if (t.isVariableDeclarator(path.parent) &&
        t.isObjectPattern(path.node.params[0])) {

        const props = path.node.params[0].properties;
        componentParams = props.map((prop: any) =>
          t.isObjectProperty(prop) ? (prop.key as any).name : (prop.argument as any).name
        );
      }
    }
  });

  // Generate the template header with documentation
  latteTemplate += generateLatteHeader(componentParams);

  // Find the component body (its JSX)
  let rootJSX: any = null;
  traverse(ast, {
    ReturnStatement(path) {
      if (path.node.argument && t.isJSXElement(path.node.argument)) {
        rootJSX = path.node.argument;
      } else if (path.node.argument && t.isJSXFragment(path.node.argument)) {
        rootJSX = path.node.argument;
      }
    }
  });

  if (!rootJSX) {
    throw new Error('Root JSX element not found');
  }

  // Convert JSX to Latte
  const jsxToLatteResult = transformJSXToLatte(rootJSX, imports);
  latteTemplate += jsxToLatteResult;

  return latteTemplate;
}
