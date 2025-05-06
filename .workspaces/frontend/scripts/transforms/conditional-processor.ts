import * as t from '@babel/types';

/**
 * Обрабатывает условную конструкцию JSX (&&)
 */
export function processConditional(
  node: any,
  imports: Record<string, any>,
  indent: number,
  transformFunc: (node: any, imports: Record<string, any>, indent: number) => string
): string {
  const indentation = ' '.repeat(indent);
  const expression = node.expression;

  // Получаем условие
  let condition = '';
  if (t.isIdentifier(expression.left)) {
    condition = `$${expression.left.name}`;
  } else if (t.isMemberExpression(expression.left)) {
    condition = `$${memberExpressionToLatteVar(expression.left)}`;
  }

  // Получаем содержимое блока
  let content = '';
  if (t.isJSXElement(expression.right)) {
    content = transformFunc(expression.right, imports, indent + 2);
  } else if (t.isJSXFragment(expression.right)) {
    content = transformFunc(expression.right, imports, indent + 2);
  }

  return `${indentation}{if ${condition}}\n${content}\n${indentation}{/if}`;
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
