import traverse from '@babel/traverse';
import * as t from '@babel/types';
import { transformJSXToLatte } from './jsx-to-latte';

/**
 * Преобразует AST React-компонента в Latte шаблон
 */
export async function transformReactToLatte(ast: any, imports: Record<string, any>) {
  let latteTemplate = '';
  let componentParams: string[] = [];

  // Извлекаем параметры компонента
  traverse(ast, {
    ArrowFunctionExpression(path) {
      // Находим главный компонент (первая стрелочная функция верхнего уровня)
      if (t.isVariableDeclarator(path.parent) &&
        t.isObjectPattern(path.node.params[0])) {

        const props = path.node.params[0].properties;
        componentParams = props.map((prop: any) =>
          t.isObjectProperty(prop) ? prop.key.name : prop.argument.name
        );
      }
    }
  });

  // Формируем заголовок шаблона с документацией
  latteTemplate += generateLatteHeader(componentParams);

  // Находим тело компонента (его JSX)
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
    throw new Error('Не найден корневой JSX элемент');
  }

  // Преобразуем JSX в Latte
  const jsxToLatteResult = transformJSXToLatte(rootJSX, imports);
  latteTemplate += jsxToLatteResult;

  return latteTemplate;
}

/**
 * Генерирует заголовок Latte-шаблона
 */
function generateLatteHeader(params: string[]): string {
  const today = new Date().toISOString().split('T')[0];

  return `{**
 * Generated Latte template
 * Date: ${today}
 *
 * @param ${params.join(' - Required parameter\n * @param ')} - Required parameter
 *}

`;
}
