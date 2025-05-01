import fs from 'fs';
import path from 'path';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import generate from '@babel/generator';
import * as t from '@babel/types';

// Конфигурация шаблонизаторов
const templateEngines = {
  twig: {
    fileExt: '.html.twig',
    variable: (path) => `{{ ${path} }}`,
    ifStart: (cond) => `{% if ${cond} %}`,
    ifEnd: () => '{% endif %}',
    forStart: (item, collection) => `{% for ${item} in ${collection} %}`,
    forEnd: () => '{% endfor %}',
    attributeWrap: (name, value) => `${name}="${value}"`,
    componentAttr: (name, props) => {
      const propsStr = Object.entries(props)
        .map(([key, value]) => `${key}: ${value}`)
        .join(', ');
      return `react_component('${name}', { ${propsStr} })`;
    },
    classAttr: 'class'
  },
  latte: {
    fileExt: '.latte',
    variable: (path) => `{$${path}}`,
    ifStart: (cond) => `{if isset(${cond})}`,
    ifEnd: () => '{/if}',
    forStart: (item, collection) => `{foreach ${collection} as ${item}}`,
    forEnd: () => '{/foreach}',
    attributeWrap: (name, value) => `${name}="${value}"`,
    componentAttr: (name, props) => {
      const propsStr = Object.entries(props)
        .map(([key, value]) => `${key} => ${value}`)
        .join(', ');
      return `react_component(${name}, [${propsStr}])`;
    },
    classAttr: 'class'
  }
};

// Функция получения всех React компонентов
async function getReactComponents(componentsDir) {
  const result = {};

  if (!fs.existsSync(componentsDir)) {
    console.error(`Components directory not found: ${componentsDir}`);
    return result;
  }

  const files = fs.readdirSync(componentsDir);
  for (const file of files) {
    if (file.endsWith('.jsx') || file.endsWith('.tsx')) {
      const name = path.basename(file, path.extname(file));
      const filePath = path.join(componentsDir, file);

      try {
        const code = fs.readFileSync(filePath, 'utf-8');
        result[name] = {
          code,
          path: filePath
        };
      } catch (error) {
        console.error(`Error reading component ${name}:`, error);
      }
    }
  }

  return result;
}

// Анализ JSX кода для выявления структуры компонента и преобразования в шаблон
function transformJsxToTemplate(code, componentName, engine = 'twig', baseContextName = 'site') {
  const templateEngine = templateEngines[engine];
  if (!templateEngine) {
    throw new Error(`Unsupported template engine: ${engine}`);
  }

  // Парсим код в AST
  const ast = parse(code, {
    sourceType: 'module',
    plugins: ['jsx', 'typescript']
  });

  // Собираем информацию о пропсах компонента
  const props = [];
  let returnPath = null;

  traverse(ast, {
    FunctionDeclaration(path) {
      if (path.node.id === null && path.parent.type === 'ExportDefaultDeclaration') {
        // Анализируем параметры компонента
        if (path.node.params.length > 0) {
          const param = path.node.params[0];
          if (t.isObjectPattern(param)) {
            // Деструктуризация props: function ({ title, description })
            param.properties.forEach(prop => {
              if (t.isObjectProperty(prop)) {
                props.push(prop.key.name);
              }
            });
          }
        }
      }
    },
    ArrowFunctionExpression(path) {
      if (path.parent.type === 'ExportDefaultDeclaration') {
        // Анализируем параметры компонента
        if (path.node.params.length > 0) {
          const param = path.node.params[0];
          if (t.isObjectPattern(param)) {
            // Деструктуризация props: ({ title, description }) => ...
            param.properties.forEach(prop => {
              if (t.isObjectProperty(prop)) {
                props.push(prop.key.name);
              }
            });
          }
        }
      }
    },
    ReturnStatement(path) {
      // Находим основной return с JSX
      if (path.node.argument && t.isJSXElement(path.node.argument)) {
        returnPath = path;
      }
    }
  });

  // Если не найден JSX в return, завершаем
  if (!returnPath) {
    return '<!-- No JSX found in component -->';
  }

  // Создаем трансформер для JSX
  const transformedJsx = transformJsxElement(returnPath.node.argument, baseContextName);

  // Создаем обертку для компонента
  const propsObj = {};
  props.forEach(prop => {
    propsObj[prop] = `${baseContextName}.${prop}`;
  });

  // В обертке компонента используем специальный синтаксис для реакт-компонентов
  const componentWrapper = `
{# Generated from React component ${componentName} #}
<div {{ ${templateEngine.componentAttr(componentName, propsObj)} }}>
${indent(transformedJsx, 2)}
</div>
`.trim();

  return componentWrapper;

  // Функция для преобразования JSX элемента в HTML с шаблонными переменными
  function transformJsxElement(jsxNode, contextPath) {
    if (!jsxNode) return '';

    // Обработка разных типов JSX узлов
    if (t.isJSXText(jsxNode)) {
      return jsxNode.value.trim();
    }

    if (t.isJSXExpressionContainer(jsxNode)) {
      return transformJsxExpression(jsxNode.expression, contextPath);
    }

    if (t.isJSXElement(jsxNode)) {
      // Получаем имя тега
      const tagName = jsxNode.openingElement.name.name;

      // Обрабатываем атрибуты
      const attributes = jsxNode.openingElement.attributes.map(attr => {
        if (t.isJSXAttribute(attr)) {
          const name = attr.name.name === 'className' ? templateEngine.classAttr : attr.name.name;
          let value = '';

          if (t.isStringLiteral(attr.value)) {
            value = attr.value.value;
          } else if (t.isJSXExpressionContainer(attr.value)) {
            value = transformJsxExpression(attr.value.expression, contextPath, true);
          }

          return `${name}="${value}"`;
        }
        return '';
      }).filter(Boolean).join(' ');

      // Обрабатываем дочерние элементы
      const children = jsxNode.children.map(child =>
        transformJsxElement(child, contextPath)
      ).join('');

      // Формируем HTML тег
      return `<${tagName}${attributes ? ' ' + attributes : ''}>${children}</${tagName}>`;
    }

    return '';
  }

  // Функция для преобразования JSX выражений
  function transformJsxExpression(expr, contextPath, isAttr = false) {
    // Обработка идентификаторов (простые переменные)
    if (t.isIdentifier(expr)) {
      return templateEngine.variable(`${contextPath}.${expr.name}`);
    }

    // Обработка доступа к свойствам (obj.prop)
    if (t.isMemberExpression(expr)) {
      const path = [];
      let current = expr;

      while (t.isMemberExpression(current)) {
        if (t.isIdentifier(current.property)) {
          path.unshift(current.property.name);
        }
        current = current.object;
      }

      if (t.isIdentifier(current)) {
        path.unshift(current.name);
      }

      // Определяем, является ли это переменной из локального контекста цикла
      const pathPrefix = path[0];
      if (contextPath !== baseContextName && pathPrefix === contextPath) {
        // Это переменная из цикла
        return templateEngine.variable(path.join('.'));
      }

      // Формируем путь к свойству
      if (path[0] === 'props') {
        path.shift(); // Убираем 'props' из пути
      }

      return templateEngine.variable(`${contextPath}.${path.join('.')}`);
    }

    // Обработка логических выражений (condition && ...)
    if (t.isLogicalExpression(expr) && expr.operator === '&&') {
      const condition = generateConditionPath(expr.left, contextPath);
      const thenBranch = transformJsxExpression(expr.right, contextPath);

      if (isAttr) {
        // Для атрибутов возвращаем условное выражение
        return `{{ ${condition} ? '${thenBranch}' : '' }}`;
      }

      return `${templateEngine.ifStart(condition)}
${indent(thenBranch, 2)}
${templateEngine.ifEnd()}`;
    }

    // Обработка вызовов map (циклы)
    if (t.isCallExpression(expr) &&
      t.isMemberExpression(expr.callee) &&
      t.isIdentifier(expr.callee.property) &&
      expr.callee.property.name === 'map') {

      const collectionPath = generateCollectionPath(expr.callee.object, contextPath);

      if (expr.arguments.length > 0 &&
        (t.isArrowFunctionExpression(expr.arguments[0]) || t.isFunctionExpression(expr.arguments[0]))) {
        const func = expr.arguments[0];
        const itemParam = func.params[0].name;
        const loopBody = transformJsxExpression(func.body, itemParam);

        return `${templateEngine.forStart(itemParam, collectionPath)}
${indent(loopBody, 2)}
${templateEngine.forEnd()}`;
      }
    }

    // Обработка тернарных операторов (condition ? then : else)
    if (t.isConditionalExpression(expr)) {
      const condition = generateConditionPath(expr.test, contextPath);
      const thenBranch = transformJsxExpression(expr.consequent, contextPath);
      const elseBranch = transformJsxExpression(expr.alternate, contextPath);

      if (isAttr) {
        return `{{ ${condition} ? '${thenBranch}' : '${elseBranch}' }}`;
      }

      return `${templateEngine.ifStart(condition)}
${indent(thenBranch, 2)}
${templateEngine.ifStart(`not (${condition})`)}
${indent(elseBranch, 2)}
${templateEngine.ifEnd()}
${templateEngine.ifEnd()}`;
    }

    // Обработка шаблонных литералов (`string ${expr}`)
    if (t.isTemplateLiteral(expr)) {
      let result = '';

      expr.quasis.forEach((quasi, i) => {
        result += quasi.value.raw;

        if (i < expr.expressions.length) {
          const exprValue = transformJsxExpression(expr.expressions[i], contextPath, true);
          // Убираем обертки шаблонизатора для вставки внутри строки
          result += exprValue.replace(/\{\{|\}\}|\{\$|\}/g, '');
        }
      });

      if (isAttr) return result;
      return `"${result}"`;
    }

    // Для других типов выражений возвращаем строковое представление
    try {
      return generate(expr).code;
    } catch (error) {
      console.error('Error generating code for expression:', error);
      return '';
    }
  }

  // Вспомогательная функция для генерации пути к условию
  function generateConditionPath(expr, contextPath) {
    if (t.isIdentifier(expr)) {
      return `${contextPath}.${expr.name}`;
    }

    if (t.isMemberExpression(expr)) {
      const path = [];
      let current = expr;

      while (t.isMemberExpression(current)) {
        if (t.isIdentifier(current.property)) {
          path.unshift(current.property.name);
        }
        current = current.object;
      }

      if (t.isIdentifier(current)) {
        path.unshift(current.name);
      }

      if (path[0] === 'props') {
        path.shift();
      }

      return `${contextPath}.${path.join('.')}`;
    }

    return generate(expr).code;
  }

  // Вспомогательная функция для генерации пути к коллекции
  function generateCollectionPath(expr, contextPath) {
    if (t.isIdentifier(expr)) {
      return `${contextPath}.${expr.name}`;
    }

    if (t.isMemberExpression(expr)) {
      const path = [];
      let current = expr;

      while (t.isMemberExpression(current)) {
        if (t.isIdentifier(current.property)) {
          path.unshift(current.property.name);
        }
        current = current.object;
      }

      if (t.isIdentifier(current)) {
        path.unshift(current.name);
      }

      if (path[0] === 'props') {
        path.shift();
      }

      return `${contextPath}.${path.join('.')}`;
    }

    return generate(expr).code;
  }
}

// Функция для добавления отступов в многострочную строку
function indent(str, spaces = 2) {
  const indent = ' '.repeat(spaces);
  return str.split('\n').map(line => line ? indent + line : line).join('\n');
}

// Основная функция сборки
async function buildTemplates(
  componentsDir,
  outputDir,
  engine = 'twig',
  baseContextName = 'site'
) {
  // Создаем выходную директорию, если она не существует
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  // Получаем все React компоненты
  const components = await getReactComponents(componentsDir);

  // Конвертируем каждый компонент в шаблон
  for (const [componentName, component] of Object.entries(components)) {
    try {
      // Трансформируем JSX в шаблон
      const template = transformJsxToTemplate(
        component.code,
        componentName,
        engine,
        baseContextName
      );

      // Сохраняем результат
      const ext = templateEngines[engine].fileExt;
      const outputPath = path.join(
        outputDir,
        `${componentName.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase()}${ext}`
      );

      fs.writeFileSync(outputPath, template);
      console.log(`Generated ${engine} template: ${outputPath}`);
    } catch (error) {
      console.error(`Error processing component ${componentName}:`, error);

      // Создаем сообщение об ошибке
      const errorMessage = `<!-- Error generating template for ${componentName}: ${error.message} -->`;
      const ext = templateEngines[engine].fileExt;
      const outputPath = path.join(
        outputDir,
        `${componentName.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase()}${ext}`
      );

      fs.writeFileSync(outputPath, errorMessage);
    }
  }

  console.log(`Build complete! Generated ${Object.keys(components).length} ${engine} templates.`);
}

// Точка входа
async function main() {
  // Получаем параметры из аргументов командной строки
  const args = process.argv.slice(2);
  const engine = args[0] || 'twig';  // twig или latte
  const contextName = args[1] || 'site';

  const componentsDir = path.resolve('src/uikits/symfony');
  const outputDir = path.resolve(`dist/templates/${engine}`);

  console.log(`Building ${engine} templates with context "${contextName}"...`);
  await buildTemplates(componentsDir, outputDir, engine, contextName);
}

main().catch(console.error);
