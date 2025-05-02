// scripts/templates.ts FrontPage
import * as fs from 'fs';
import * as path from 'path';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import generate from '@babel/generator';
import * as t from '@babel/types';

// Путь к директории компонентов
const COMPONENTS_DIR = path.resolve(process.cwd(), 'src/uikits/latte/components');

/**
 * Генерирует шаблон на основе React компонента
 * @param componentName Имя компонента для преобразования
 */
function generateTemplate(componentName: string): void {
  const componentPath = path.join(COMPONENTS_DIR, `${componentName}.tsx`);

  if (!fs.existsSync(componentPath)) {
    console.error(`Компонент ${componentName} не найден в ${COMPONENTS_DIR}`);
    process.exit(1);
  }

  const sourceCode = fs.readFileSync(componentPath, 'utf-8');

  // Парсим исходный код
  const ast = parse(sourceCode, {
    sourceType: 'module',
    plugins: ['jsx', 'typescript']
  });

  // Обходим AST и добавляем атрибуты data-var и data-loop
  traverse(ast, {
    // Обработка переменных JSX
    JSXExpressionContainer(path) {
      // Не обрабатываем атрибуты key и другие спец-атрибуты
      if (
        path.parent.type === 'JSXAttribute' &&
        (path.parent.name.name === 'key' || path.parent.name.name === 'dangerouslySetInnerHTML')
      ) {
        return;
      }

      // Проверяем, что это обращение к свойству или переменной
      if (t.isMemberExpression(path.node.expression) || t.isIdentifier(path.node.expression)) {
        const varName = generate(path.node.expression).code;

        // Не обрабатываем переменные внутри циклов
        if (varName.startsWith('post.') || varName.startsWith('category.')) {
          return;
        }

        // Находим ближайший JSX элемент
        let jsxElement = path.findParent(p => p.isJSXElement());
        if (jsxElement && !jsxElement.node.openingElement.attributes.some(attr =>
          t.isJSXAttribute(attr) && attr.name.name === 'data-var'
        )) {
          jsxElement.node.openingElement.attributes.push(
            t.jsxAttribute(
              t.jsxIdentifier('data-var'),
              t.stringLiteral(varName)
            )
          );
        }
      }
    },

    // Обработка циклов
    CallExpression(path) {
      if (
        t.isMemberExpression(path.node.callee) &&
        t.isIdentifier(path.node.callee.property) &&
        path.node.callee.property.name === 'map'
      ) {
        const arrayName = generate(path.node.callee.object).code;

        // Получаем параметр функции в map (например, post, index)
        const iteratorParam = path.node.arguments[0].params[0].name;

        // Находим JSX элемент внутри функции map
        const returnStatement = path.get('arguments')[0].get('body');
        if (returnStatement.isJSXElement()) {
          const jsxElement = returnStatement;

          jsxElement.node.openingElement.attributes.push(
            t.jsxAttribute(
              t.jsxIdentifier('data-loop'),
              t.stringLiteral(arrayName)
            )
          );
        }
      }
    }
  });

  // Генерируем код из модифицированного AST
  const { code } = generate(ast, {
    retainLines: true,
    jsescOption: { minimal: true }
  });

  // Создаем шаблонный файл
  const templatePath = path.join(COMPONENTS_DIR, `${componentName}Template.tsx`);
  fs.writeFileSync(templatePath, code);

  console.log(`Шаблон успешно создан: ${templatePath}`);
}

// Запуск скрипта
if (require.main === module) {
  const args = process.argv.slice(2);
  if (args.length !== 1) {
    console.error('Использование: node generate-template.js ComponentName');
    process.exit(1);
  }

  generateTemplate(args[0]);
}

export { generateTemplate }; 