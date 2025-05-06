import * as t from '@babel/types';

/**
 * Обрабатывает циклы в JSX (map)
 */
export function processLoop(
  node: any,
  imports: Record<string, any>,
  indent: number,
  transformFunc: (node: any, imports: Record<string, any>, indent: number) => string
): string {
  const indentation = ' '.repeat(indent);
  const expression = node.expression;

  // Получаем коллекцию для перебора
  let collection = '';
  if (t.isMemberExpression(expression.callee.object)) {
    collection = memberExpressionToLatteVar(expression.callee.object);
  } else if (t.isIdentifier(expression.callee.object)) {
    collection = expression.callee.object.name;
  }

  // Получаем имя итератора
  const iteratorParam = expression.arguments[0].params[0].name;

  // Получаем содержимое блока
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

/**
 * Преобразует MemberExpression в строку переменной Latte
 */
function memberExpressionToLatteVar(node: any): string {
  if (t.isIdentifier(node.object)) {
    return `${node.object.name}.${node.property.name}`;
  } else if (t.isMemberExpression(node.object)) {
    return `${memberExpressionToLatteVar(node.object)}.${node.property.name}`;
  }
  return '';
}
