import * as t from '@babel/types';
import { processConditional } from './conditional-processor';
import { processLoop } from './loop-processor';

/**
 * Преобразует JSX в Latte синтаксис
 */
export function transformJSXToLatte(jsxNode: any, imports: Record<string, any>, indent = 0): string {
  const indentation = ' '.repeat(indent);

  // Если это фрагмент
  if (t.isJSXFragment(jsxNode)) {
    return jsxNode.children
      .filter((child: any) => !t.isJSXText(child) || child.value.trim() !== '')
      .map((child: any) => transformJSXToLatte(child, imports, indent))
      .join('\n');
  }

  // Если это условный оператор
  if (t.isJSXExpressionContainer(jsxNode) &&
    t.isLogicalExpression(jsxNode.expression) &&
    jsxNode.expression.operator === '&&') {
    return processConditional(jsxNode, imports, indent, transformJSXToLatte);
  }

  // Если это map (цикл)
  if (t.isJSXExpressionContainer(jsxNode) &&
    t.isCallExpression(jsxNode.expression) &&
    t.isMemberExpression(jsxNode.expression.callee) &&
    jsxNode.expression.callee.property.name === 'map') {
    return processLoop(jsxNode, imports, indent, transformJSXToLatte);
  }

  // Если это внешний компонент
  if (t.isJSXElement(jsxNode) && !jsxNode.openingElement.name.name.match(/^[a-z]/)) {
    const componentName = jsxNode.openingElement.name.name;
    if (imports[componentName]) {
      // Рекурсивно обрабатываем импортированный компонент
      return processImportedComponent(componentName, jsxNode, imports, indent);
    }
  }

  // Обычный HTML элемент
  if (t.isJSXElement(jsxNode)) {
    const tag = jsxNode.openingElement.name.name;
    const attributes = processAttributes(jsxNode.openingElement.attributes);
    let result = '';

    // Открывающий тег
    result += `${indentation}<${tag}${attributes.length ? ' ' + attributes.join(' ') : ''}>`;

    // Если есть дочерние элементы
    if (jsxNode.children.length > 0) {
      result += '\n';
      jsxNode.children.forEach((child: any) => {
        if (t.isJSXText(child)) {
          if (child.value.trim() !== '') {
            result += `${indentation}  ${child.value.trim()}\n`;
          }
        } else {
          result += transformJSXToLatte(child, imports, indent + 2) + '\n';
        }
      });
      result += `${indentation}</${tag}>`;
    } else {
      result += `</${tag}>`;
    }

    return result;
  }

  // JSX-выражение (переменная)
  if (t.isJSXExpressionContainer(jsxNode)) {
    if (t.isIdentifier(jsxNode.expression)) {
      return `${indentation}{$${jsxNode.expression.name}}`;
    } else if (t.isMemberExpression(jsxNode.expression)) {
      return `${indentation}{$${memberExpressionToLatteVar(jsxNode.expression)}}`;
    }
  }

  // JSX-текст
  if (t.isJSXText(jsxNode)) {
    const text = jsxNode.value.trim();
    return text ? `${indentation}${text}` : '';
  }

  return '';
}

/**
 * Обработка атрибутов JSX-элемента
 */
function processAttributes(attributes: any[]): string[] {
  return attributes.map(attr => {
    if (t.isJSXAttribute(attr)) {
      const name = attr.name.name;

      // Если значение атрибута - строка
      if (t.isStringLiteral(attr.value)) {
        return `${name}="${attr.value.value}"`;
      }

      // Если значение атрибута - выражение
      if (t.isJSXExpressionContainer(attr.value)) {
        if (t.isIdentifier(attr.value.expression)) {
          return `${name}="{$${attr.value.expression.name}}"`;
        } else if (t.isMemberExpression(attr.value.expression)) {
          return `${name}="{$${memberExpressionToLatteVar(attr.value.expression)}}"`;
        }
      }

      // Если атрибут без значения
      if (!attr.value) {
        return name;
      }
    }

    return '';
  }).filter(Boolean);
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

/**
 * Обрабатывает импортированный компонент
 */
function processImportedComponent(componentName: string, jsxNode: any, imports: Record<string, any>, indent: number): string {
  // Получаем параметры, передаваемые компоненту
  const props: Record<string, string> = {};
  jsxNode.openingElement.attributes.forEach((attr: any) => {
    if (t.isJSXAttribute(attr)) {
      const name = attr.name.name;
      if (t.isJSXExpressionContainer(attr.value)) {
        if (t.isIdentifier(attr.value.expression)) {
          props[name] = attr.value.expression.name;
        } else if (t.isMemberExpression(attr.value.expression)) {
          props[name] = memberExpressionToLatteVar(attr.value.expression);
        }
      }
    }
  });

  // Здесь нужно рекурсивно преобразовать импортированный компонент
  // Такой подход требует загрузки и анализа всех импортированных компонентов

  // Для примера включим содержимое компонента как отдельный блок
  const indentation = ' '.repeat(indent);
  return `${indentation}{* Начало компонента ${componentName} *}\n${indentation}{include '${componentName.toLowerCase()}.latte', ${Object.entries(props).map(([k, v]) => `${k}: $${v}`).join(', ')}}\n${indentation}{* Конец компонента ${componentName} *}`;
}
