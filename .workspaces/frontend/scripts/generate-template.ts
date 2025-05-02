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

  console.log(`Базовый шаблон создан: ${templatePath}`);

  // Этап 2: Обертывание элементов с data-var в условия
  wrapElementsWithConditions(templatePath);
}

/**
 * Оборачивает элементы с data-var в условные выражения
 * @param filePath Путь к файлу шаблона
 */
function wrapElementsWithConditions(filePath: string): void {
  const code = fs.readFileSync(filePath, 'utf-8');

  // Используем регулярное выражение для поиска и замены JSX элементов с data-var
  // Это более надежный способ, чем работа с AST для такой специфической задачи

  // Регулярное выражение для поиска элементов с data-var
  const dataVarRegex = /<([a-zA-Z][a-zA-Z0-9]*)([^>]*?)data-var="([^"]+)"([^>]*?)>(.*?)<\/\1>/gs;

  // Заменяем на условное выражение
  const processedCode = code.replace(dataVarRegex, (match, tag, attrsBeforeVar, varName, attrsAfterVar, content) => {
    // Убираем data-var атрибут из результата
    const attrs = attrsBeforeVar + attrsAfterVar;

    // Создаем условное выражение
    return `{${varName} && (<${tag}${attrs}>${content}</${tag}>)}`;
  });

  // Если нашли и заменили хотя бы одно совпадение
  if (processedCode !== code) {
    fs.writeFileSync(filePath, processedCode);
    console.log(`Шаблон обработан с условными выражениями: ${filePath}`);
  } else {
    // Пробуем альтернативный подход для самозакрывающихся тегов
    const selfClosingDataVarRegex = /<([a-zA-Z][a-zA-Z0-9]*)([^>]*?)data-var="([^"]+)"([^>]*?)\/>/gs;

    const processedCodeSelfClosing = code.replace(selfClosingDataVarRegex, (match, tag, attrsBeforeVar, varName, attrsAfterVar) => {
      // Убираем data-var атрибут из результата
      const attrs = attrsBeforeVar + attrsAfterVar;

      // Создаем условное выражение для самозакрывающегося тега
      return `{${varName} && (<${tag}${attrs}/>)}`;
    });

    if (processedCodeSelfClosing !== code) {
      fs.writeFileSync(filePath, processedCodeSelfClosing);
      console.log(`Шаблон обработан с условными выражениями (самозакрывающиеся теги): ${filePath}`);
    } else {
      console.log(`Условные выражения не добавлены (не найдено подходящих элементов)`);

      // Еще один подход - пробуем обрабатывать JSX элементы с data-var внутри других JSX элементов
      const jsxCode = fs.readFileSync(filePath, 'utf-8');

      // Выполняем более сложное преобразование для обработки вложенных структур
      try {
        const ast = parse(jsxCode, {
          sourceType: 'module',
          plugins: ['jsx', 'typescript']
        });

        // Флаг для отслеживания изменений
        let modified = false;

        traverse(ast, {
          JSXOpeningElement(path) {
            const attrs = path.node.attributes;
            const dataVarAttr = attrs.find(attr =>
              attr.type === 'JSXAttribute' &&
              attr.name.name === 'data-var'
            );

            if (dataVarAttr && dataVarAttr.value && dataVarAttr.value.type === 'StringLiteral') {
              const varName = dataVarAttr.value.value;
              const elementPath = path.parentPath;

              // Проверяем, что элемент не находится внутри условного выражения
              const isInsideCondition = elementPath.findParent(p =>
                p.isLogicalExpression() ||
                p.isConditionalExpression()
              );

              if (!isInsideCondition && elementPath.isJSXElement()) {
                // Создаем новое выражение: varName && (<element>...</element>)
                const jsxElement = elementPath.node;

                // Создаем условие
                const condition = t.identifier(varName);
                const wrappedElement = t.logicalExpression(
                  '&&',
                  condition,
                  jsxElement
                );

                // Заменяем JSX элемент условным выражением
                elementPath.replaceWith(
                  t.jsxExpressionContainer(wrappedElement)
                );

                modified = true;
              }
            }
          }
        });

        // Если были сделаны изменения, сохраняем файл
        if (modified) {
          const { code: updatedCode } = generate(ast, {
            retainLines: true
          });
          fs.writeFileSync(filePath, updatedCode);
          console.log(`Шаблон обработан с условными выражениями (сложная структура): ${filePath}`);
        }
      } catch (error) {
        console.error('Ошибка при обработке JSX с помощью AST:', error);
      }
    }
  }
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