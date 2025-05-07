import * as t from '@babel/types';

/**
 * Helper function from jsx-to-latte.ts to prevent code duplication
 */
function memberExpressionToLatteVar(node: any): string {
  if (t.isIdentifier(node.object)) {
    return `${(node.object as any).name}.${(node.property as any).name}`;
  } else if (t.isMemberExpression(node.object)) {
    return `${memberExpressionToLatteVar(node.object)}.${(node.property as any).name}`;
  }
  return '';
}

/**
 * Processes the conditional JSX structure (&&)
 */
export function processConditional(
  node: any,
  imports: Record<string, any>,
  indent: number,
  transformFunc: (node: any, imports: Record<string, any>, indent: number) => string
): string {
  const indentation = ' '.repeat(indent);
  const expression = node.expression;

  // Get the condition
  let condition = '';
  if (t.isIdentifier(expression.left)) {
    condition = `$${(expression.left as any).name}`;
  } else if (t.isMemberExpression(expression.left)) {
    condition = `$${memberExpressionToLatteVar(expression.left)}`;
  }

  // Get the content of the block
  let content = '';
  if (t.isJSXElement(expression.right)) {
    content = transformFunc(expression.right, imports, indent + 2);
  } else if (t.isJSXFragment(expression.right)) {
    content = transformFunc(expression.right, imports, indent + 2);
  }

  return `${indentation}{if ${condition}}\n${content}\n${indentation}{/if}`;
}
