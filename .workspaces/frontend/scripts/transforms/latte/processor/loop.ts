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
 * Processes loops in JSX (map)
 */
export function processLoop(
  node: any,
  imports: Record<string, any>,
  indent: number,
  transformFunc: (node: any, imports: Record<string, any>, indent: number) => string
): string {
  const indentation = ' '.repeat(indent);
  const expression = node.expression;

  // Get the collection to iterate over
  let collection = '';
  if (t.isMemberExpression(expression.callee.object)) {
    collection = memberExpressionToLatteVar(expression.callee.object);
  } else if (t.isIdentifier(expression.callee.object)) {
    collection = (expression.callee.object as any).name;
  }

  // Get the iterator name
  const iteratorParam = (expression.arguments[0].params[0] as any).name;

  // Get the content of the block
  const body = expression.arguments[0].body;
  let content = '';

  if (t.isJSXElement(body)) {
    content = transformFunc(body, imports, indent + 2);
  } else if (t.isBlockStatement(body) && body.body.length === 1 && t.isReturnStatement(body.body[0])) {
    const returnValue = body.body[0].argument;
    if (t.isJSXElement(returnValue)) {
      content = transformFunc(returnValue, imports, indent + 2);
    }
  }

  return `${indentation}{foreach $${collection} as $${iteratorParam}}\n${content}\n${indentation}{/foreach}`;
}
