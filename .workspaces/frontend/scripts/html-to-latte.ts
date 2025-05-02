import { renderToStaticMarkup } from 'react-dom/server';
import { JSDOM } from 'jsdom';
import * as fs from 'fs';
import * as path from 'path';

// Пути к файлам
const TEMPLATES_DIR = path.resolve(process.cwd(), 'src/templates/latte');

// Импортируем DOM типы из JSDOM
const { Element, Node, NodeFilter } = new JSDOM().window;

/**
 * Преобразует React компонент в Latte шаблон
 * @param componentName Имя компонента без расширения и пути
 * @param componentModule Путь к модулю компонента относительно src/
 */
async function reactComponentToLatte(componentName: string, componentModule: string = `uikits/latte/components/${componentName}`): Promise<string> {
  try {
    // Динамически импортируем React компонент
    const componentPath = `../src/${componentModule}`;
    const Component = (await import(componentPath)).default;

    // Рендерим компонент в статический HTML
    const html = renderToStaticMarkup(Component());

    // Преобразуем HTML в Latte
    const latteContent = processHtmlToLatte(html);

    // Формируем название для шаблона Latte (без 'Template' в имени)
    const latteFileName = componentName.replace('Template', '').toLowerCase() + '.htm';
    const latteFilePath = path.join(TEMPLATES_DIR, latteFileName);

    // Добавляем документацию в начало
    const fullLatteContent = `{**
 * ${componentName.replace('Template', '')} template
 *
 * Required context:
 * - page: Current page data (object, if is_page)
 * - posts: Array of posts (array, if is_home)
 * - site: Site data (object)
 *}

${latteContent}`;

    // Сохраняем в файл Latte
    fs.writeFileSync(latteFilePath, fullLatteContent);
    console.log(`Latte шаблон создан: ${latteFilePath}`);

    return fullLatteContent;
  } catch (error) {
    console.error(`Ошибка при преобразовании компонента ${componentName}:`, error);
    throw error;
  }
}

/**
 * Преобразует HTML строку в Latte синтаксис
 */
function processHtmlToLatte(html: string): string {
  try {
    // Создаем временный DOM для обработки
    const dom = new JSDOM(`<div id="root">${html}</div>`);
    const { document } = dom.window;
    const root = document.getElementById('root');

    if (!root) {
      throw new Error('Не удалось найти корневой элемент');
    }

    // 1. Обрабатываем атрибуты data-loop
    processLoopElements(document);

    // 2. Обрабатываем атрибуты data-var
    processVarElements(document);

    // 3. Выполняем специальные преобразования
    specialLatteTransformations(document);

    // Преобразуем DOM обратно в строку
    let result = root.innerHTML;

    // Пост-обработка строки для очистки от служебных атрибутов
    result = cleanupAttributes(result);

    return result;
  } catch (error) {
    console.error('Ошибка при обработке HTML:', error);
    return html; // В случае ошибки возвращаем исходный HTML
  }
}

/**
 * Обрабатывает элементы с атрибутом data-loop
 */
function processLoopElements(document: Document): void {
  try {
    // Первым шагом группируем элементы с одинаковыми data-loop атрибутами
    const loopGroups = new Map<string, any[]>();

    // Собираем элементы по группам
    document.querySelectorAll('[data-loop]').forEach(element => {
      const arrayName = element.getAttribute('data-loop') || '';
      if (!loopGroups.has(arrayName)) {
        loopGroups.set(arrayName, []);
      }
      loopGroups.get(arrayName)?.push(element);
    });

    // Для каждой группы обрабатываем только первый элемент и удаляем остальные
    loopGroups.forEach((elements, arrayName) => {
      // Пропускаем пустые группы
      if (elements.length === 0) return;

      // Берем первый элемент как шаблон
      const templateElement = elements[0];
      const parentNode = templateElement.parentNode;

      if (!parentNode) return;

      // Удаляем атрибут data-loop с шаблонного элемента
      templateElement.removeAttribute('data-loop');

      // Определяем корректные имена переменных
      // Для верхнего уровня: posts -> post
      // Для вложенных: post.categories -> category
      let itemName: string;

      if (arrayName.includes('.')) {
        // Разбираем вложенную структуру, например post.categories
        const parts = arrayName.split('.');
        const parentVar = parts[0]; // например, post
        const childCollection = parts[1]; // например, categories

        // Определяем корректное имя для итератора
        if (childCollection.endsWith('s')) {
          // Для множественного числа убираем s на конце
          itemName = childCollection.slice(0, -1); // categories -> category
        } else if (childCollection.endsWith('ies')) {
          // Для особых случаев множественного числа (entities -> entity)
          itemName = childCollection.slice(0, -3) + 'y';
        } else {
          // Для других случаев просто добавляем Item
          itemName = childCollection + 'Item';
        }

        // Для вложенных циклов используем полные пути к переменным
        arrayName = `${parentVar}['${childCollection}']`;
      } else {
        // Для верхнего уровня (например, posts)
        if (arrayName.endsWith('s')) {
          itemName = arrayName.slice(0, -1); // posts -> post
        } else if (arrayName.endsWith('ies')) {
          itemName = arrayName.slice(0, -3) + 'y'; // entities -> entity
        } else {
          itemName = arrayName + 'Item';
        }
      }

      // Создаем маркеры для Latte цикла с правильным синтаксисом
      const startLoop = document.createComment(`foreach $${arrayName} as $${itemName}`);
      const endLoop = document.createComment('/foreach');

      // Вставляем маркеры для цикла и проверяем, что parentNode поддерживает insertBefore
      if (parentNode.nodeType === Node.ELEMENT_NODE) {
        // Вставляем маркеры для цикла
        parentNode.insertBefore(startLoop, templateElement);
        parentNode.insertBefore(endLoop, templateElement.nextSibling);

        // Помечаем родительский элемент для последующей обработки
        parentNode.setAttribute('data-contains-loop', arrayName);
        // Также сохраняем имя итератора для дальнейшей обработки
        parentNode.setAttribute('data-iterator', itemName);
      }

      // Для всех элементов кроме первого в группе, удаляем их
      // так как они будут сгенерированы циклом
      elements.slice(1).forEach(duplicateElement => {
        duplicateElement.parentNode?.removeChild(duplicateElement);
      });

      // Рекурсивно обрабатываем вложенные циклы в текущем шаблоне
      processNestedLoops(templateElement, itemName);
    });
  } catch (error) {
    console.error('Ошибка при обработке циклов:', error);
  }
}

/**
 * Обрабатывает вложенные циклы внутри элемента
 */
function processNestedLoops(element: any, parentIterator: string): void {
  try {
    // Обрабатываем вложенные циклы с учетом родительского итератора
    const nestedLoops = element.querySelectorAll('[data-loop]');

    nestedLoops.forEach(nested => {
      const loopValue = nested.getAttribute('data-loop');

      // Если цикл уже обработан или значение отсутствует, пропускаем
      if (!loopValue) return;

      // Проверяем, является ли это дочерним элементом родительского итератора
      if (loopValue.startsWith('post.') && parentIterator === 'post') {
        // Заменяем post.categories на $post['categories']
        const nestedProperty = loopValue.split('.')[1];

        // Удаляем текущий атрибут
        nested.removeAttribute('data-loop');

        // Устанавливаем новый атрибут с полным путем к переменной
        nested.setAttribute('data-loop', `${parentIterator}['${nestedProperty}']`);
      }
    });
  } catch (error) {
    console.error('Ошибка при обработке вложенных циклов:', error);
  }
}

/**
 * Обрабатывает элементы с атрибутом data-var
 */
function processVarElements(document: Document): void {
  try {
    const varElements = document.querySelectorAll('[data-var]');

    varElements.forEach(element => {
      const varName = element.getAttribute('data-var');
      if (!varName) return;

      // Находим текстовые узлы для замены
      processTextNodesInElement(element, varName);

      // Удаляем служебный атрибут
      element.removeAttribute('data-var');
    });
  } catch (error) {
    console.error('Ошибка при обработке переменных:', error);
  }
}

/**
 * Обрабатывает текстовые узлы в элементе, заменяя их на переменные Latte
 */
function processTextNodesInElement(element: any, varName: string): void {
  try {
    const walker = element.ownerDocument.createTreeWalker(
      element,
      NodeFilter.SHOW_TEXT,
      null
    );

    const textNodes: Text[] = [];
    let node;
    while (node = walker.nextNode()) {
      if (node.textContent && node.textContent.trim()) {
        textNodes.push(node as Text);
      }
    }

    textNodes.forEach(node => {
      const text = node.textContent?.trim();
      if (text) {
        // Заменяем только если текст совпадает с частью переменной
        const varParts = varName.split('.');
        const lastPart = varParts[varParts.length - 1];

        if (node.textContent?.includes(text)) {
          node.textContent = node.textContent.replace(
            text,
            `{$${varName}}`
          );
        }
      }
    });
  } catch (error) {
    console.error('Ошибка при обработке текстовых узлов:', error);
  }
}

/**
 * Выполняет специальные преобразования для Latte на основе атрибутов и структуры
 */
function specialLatteTransformations(document: Document): void {
  try {
    // Общая функция для преобразования атрибутов на основе их значений
    const transformElementAttributes = (selector: string, attributeName: string,
      transformFn: (value: string, element: any) => string): void => {

      document.querySelectorAll(`${selector}[${attributeName}]`).forEach(element => {
        const attrValue = element.getAttribute(attributeName);
        if (attrValue) {
          const newValue = transformFn(attrValue, element);
          element.setAttribute(attributeName, newValue);
        }
      });
    };

    // Функция для получения имени итератора из родительского цикла
    const getIteratorName = (element: any): string => {
      const loopParent = element.closest('[data-contains-loop]');
      if (!loopParent) return '';

      return loopParent.getAttribute('data-iterator') || '';
    };

    // 1. Преобразование src атрибутов изображений
    transformElementAttributes('img', 'src', (value, element) => {
      const inLoop = !!element.closest('[data-contains-loop]');
      if (!inLoop) return value;

      const iterator = getIteratorName(element);
      if (!iterator) return value;

      return `{$${iterator}['thumbnail']['url']}`;
    });

    // 2. Преобразование alt атрибутов
    transformElementAttributes('img', 'alt', (value, element) => {
      const inLoop = !!element.closest('[data-contains-loop]');
      if (!inLoop) return value;

      const iterator = getIteratorName(element);
      if (!iterator) return value;

      return `{$${iterator}['thumbnail']['alt']}`;
    });

    // 3. Преобразование href ссылок
    transformElementAttributes('a', 'href', (value, element) => {
      // Пропускаем ссылки, которые являются якорями или абсолютными URL
      if (value.startsWith('#') || value.startsWith('http')) {
        return value;
      }

      const inLoop = !!element.closest('[data-contains-loop]');
      if (!inLoop) {
        // Для обычных ссылок вне циклов
        return value.startsWith('/') ? `{$site['url']}${value}` : value;
      }

      const iterator = getIteratorName(element);
      if (!iterator) return value;

      // Проверяем, не является ли это ссылкой категории
      const isCategoryLink = element.classList.contains('card-category');
      if (isCategoryLink) {
        return `{$category['url']}`;
      }

      return `{$${iterator}['url']}`;
    });

    // 4. Преобразование datetime для time элементов
    transformElementAttributes('time', 'dateTime', (value, element) => {
      const inLoop = !!element.closest('[data-contains-loop]');
      if (!inLoop) return value;

      const iterator = getIteratorName(element);
      if (!iterator) return value;

      return `{$${iterator}['date']['formatted']}`;
    });

    // 5. Преобразование HTML содержимого для специфических контейнеров
    const processContentDivs = (selector: string, templateExpr: string): void => {
      document.querySelectorAll(selector).forEach(element => {
        const inLoop = !!element.closest('[data-contains-loop]');
        const parent = element.closest('[data-contains-loop]');

        if (inLoop && parent) {
          const iterator = parent.getAttribute('data-iterator');
          if (!iterator) return;

          // Сохраняем классы и другие атрибуты
          const className = element.getAttribute('class');
          element.innerHTML = templateExpr.replace('$item', `$${iterator}`);
          element.setAttribute('class', className || '');
        } else if (selector.includes('about-content')) {
          // Для about-content используем page.content
          element.innerHTML = templateExpr.replace('$item', '$page');
        }
      });
    };

    // Применяем преобразования для конкретных типов контента
    processContentDivs('.card-excerpt', '{$item[\'excerpt\']|noescape}');
    processContentDivs('.about-content', '{$item[\'content\']|noescape}');

    // 6. Преобразование текста в заголовках hero
    document.querySelectorAll('.hero-title').forEach(element => {
      if (!element.closest('[data-contains-loop]')) {
        element.innerHTML = '{$site[\'title\']}';
      }
    });

    document.querySelectorAll('.hero-description').forEach(element => {
      if (!element.closest('[data-contains-loop]')) {
        element.innerHTML = '{$site[\'description\']}';
      }
    });

    // Добавим условные проверки для элементов с опциональными данными
    document.querySelectorAll('.card-thumbnail').forEach(element => {
      const inLoop = !!element.closest('[data-contains-loop]');
      if (!inLoop) return;

      const iterator = getIteratorName(element);
      if (!iterator) return;

      // Оборачиваем в условную проверку
      const parent = element.parentNode;
      if (!parent || parent.nodeType !== Node.ELEMENT_NODE) return;

      const startIf = document.createComment(`if isset($${iterator}['thumbnail'])`);
      const endIf = document.createComment('/if');

      parent.insertBefore(startIf, element);
      parent.insertBefore(endIf, element.nextSibling);
    });

    // Аналогично для категорий
    document.querySelectorAll('.card-categories').forEach(element => {
      const inLoop = !!element.closest('[data-contains-loop]');
      if (!inLoop) return;

      const iterator = getIteratorName(element);
      if (!iterator) return;

      const parent = element.parentNode;
      if (!parent || parent.nodeType !== Node.ELEMENT_NODE) return;

      const startIf = document.createComment(`if isset($${iterator}['categories']) && !empty($${iterator}['categories'])`);
      const endIf = document.createComment('/if');

      parent.insertBefore(startIf, element);
      parent.insertBefore(endIf, element.nextSibling);
    });
  } catch (error) {
    console.error('Ошибка при специальных преобразованиях:', error);
  }
}

/**
 * Очищает строку от временных служебных атрибутов и преобразует в Latte синтаксис
 */
function cleanupAttributes(html: string): string {
  try {
    let result = html;

    // Заменяем комментарии-маркеры на Latte теги
    // Используем функцию обратного вызова для правильной замены
    result = result.replace(/<!--\s*foreach \$([^\s]+) as \$([^\s]+)\s*-->/g, (match, p1, p2) => {
      return `{foreach $${p1} as $${p2}}`;
    });

    result = result.replace(/<!--\s*\/foreach\s*-->/g, '{/foreach}');

    // Заменяем условные комментарии на условные теги Latte
    result = result.replace(/<!--\s*if ([^>]*)\s*-->/g, (match, condition) => {
      return `{if ${condition}}`;
    });

    result = result.replace(/<!--\s*\/if\s*-->/g, '{/if}');

    // Правильно форматируем переменные внутри Latte шаблона
    result = result.replace(/\{\$([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\}/g, '{$$1[\'$2\']}');

    // Удаляем временные служебные атрибуты
    result = result.replace(/\s*data-contains-loop="[^"]*"/g, '');
    result = result.replace(/\s*data-iterator="[^"]*"/g, '');
    result = result.replace(/\s*data-var="[^"]*"/g, '');
    result = result.replace(/\s*data-loop="[^"]*"/g, '');

    // Форматирование вывода, добавляем отступы и переносы строк
    result = formatLatteOutput(result);

    return result;
  } catch (error) {
    console.error('Ошибка при очистке атрибутов:', error);
    return html;
  }
}

/**
 * Форматирует вывод Latte для лучшей читаемости
 */
function formatLatteOutput(html: string): string {
  try {
    // Простое форматирование, добавляет переносы строк после основных тегов
    return html
      .replace(/><([\/a-zA-Z])/g, '>\n<$1')
      .replace(/\{foreach/g, '\n{foreach')
      .replace(/\{\/foreach\}/g, '{/foreach}\n')
      .replace(/\{if/g, '\n{if')
      .replace(/\{\/if\}/g, '{/if}\n');
  } catch (error) {
    console.error('Ошибка при форматировании вывода:', error);
    return html;
  }
}

// Запуск скрипта
if (require.main === module) {
  const args = process.argv.slice(2);
  if (args.length < 1) {
    console.error('Использование: bun scripts/html-to-latte.ts ComponentName [ComponentPath]');
    process.exit(1);
  }

  const componentName = args[0];
  const componentPath = args.length > 1 ? args[1] : undefined;

  reactComponentToLatte(componentName, componentPath)
    .catch(error => {
      console.error('Ошибка при выполнении скрипта:', error);
      process.exit(1);
    });
}

export { reactComponentToLatte };
