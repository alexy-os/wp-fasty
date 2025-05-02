import { renderToStaticMarkup } from 'react-dom/server';
import { JSDOM } from 'jsdom';
import * as fs from 'fs';
import * as path from 'path';

// Пути к файлам
const TEMPLATES_DIR = path.resolve(process.cwd(), 'src/templates/latte');

/**
 * Преобразует React компонент в Latte шаблон
 * @param componentName Имя компонента без расширения и пути
 * @param componentModule Путь к модулю компонента относительно src/
 */
async function reactComponentToLatte(componentName: string, componentModule: string = `uikits/latte/components/${componentName}`): Promise<void> {
  try {
    // Динамически импортируем React компонент
    const componentPath = `../src/${componentModule}`;
    const Component = (await import(componentPath)).default;

    // Рендерим компонент в статический HTML
    const html = renderToStaticMarkup(Component());

    // Преобразуем HTML в Latte
    const latteContent = processHtmlToLatte(html);

    // Формируем название для шаблона Latte (без 'Template' в имени)
    const latteFileName = componentName.replace('Template', '').toLowerCase() + '.latte';
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
  // Создаем временный DOM для обработки
  const dom = new JSDOM(`<div id="root">${html}</div>`);
  const { document } = dom.window;
  const root = document.getElementById('root');

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
}

/**
 * Обрабатывает элементы с атрибутом data-loop
 */
function processLoopElements(document: Document): void {
  const loopElements = document.querySelectorAll('[data-loop]');

  loopElements.forEach(element => {
    const arrayName = element.getAttribute('data-loop');
    const parentNode = element.parentNode;
    element.removeAttribute('data-loop');

    if (parentNode && arrayName) {
      // Определяем имя элемента в цикле (например, из posts получаем post)
      const itemName = arrayName.includes('.')
        ? arrayName.split('.')[1]
        : arrayName.endsWith('s')
          ? arrayName.slice(0, -1)
          : arrayName + 'Item';

      // Сохраняем оригинальный HTML элемента
      const tempContainer = document.createElement('div');
      tempContainer.appendChild(element.cloneNode(true));

      // Создаем маркер для Latte цикла
      const startLoop = document.createComment(`foreach $${arrayName} as $${itemName}`);
      const endLoop = document.createComment('/foreach');

      // Вставляем маркеры и элемент обратно в родителя
      parentNode.insertBefore(startLoop, element);
      parentNode.insertBefore(endLoop, element.nextSibling);

      // Помечаем родительский элемент для последующей обработки
      parentNode.setAttribute('data-contains-loop', arrayName);
    }
  });
}

/**
 * Обрабатывает элементы с атрибутом data-var
 */
function processVarElements(document: Document): void {
  const varElements = document.querySelectorAll('[data-var]');

  varElements.forEach(element => {
    const varName = element.getAttribute('data-var');
    if (!varName) return;

    // Находим текстовые узлы для замены
    processTextNodesInElement(element, varName);

    // Удаляем служебный атрибут
    element.removeAttribute('data-var');
  });
}

/**
 * Обрабатывает текстовые узлы в элементе, заменяя их на переменные Latte
 */
function processTextNodesInElement(element: Element, varName: string): void {
  const walker = document.createTreeWalker(
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
}

/**
 * Выполняет специальные преобразования для Latte на основе атрибутов и структуры
 */
function specialLatteTransformations(document: Document): void {
  // Общая функция для преобразования атрибутов на основе их значений
  const transformElementAttributes = (selector: string, attributeName: string,
    transformFn: (value: string, element: Element) => string): void => {

    document.querySelectorAll(`${selector}[${attributeName}]`).forEach(element => {
      const attrValue = element.getAttribute(attributeName);
      if (attrValue) {
        const newValue = transformFn(attrValue, element);
        element.setAttribute(attributeName, newValue);
      }
    });
  };

  // 1. Преобразование src атрибутов изображений
  transformElementAttributes('img', 'src', (value, element) => {
    // Проверяем наличие родителя с data-loop
    const inLoop = !!element.closest('[data-contains-loop]');
    const parent = element.closest('[data-contains-loop]');
    const loopVar = parent?.getAttribute('data-contains-loop');

    // В зависимости от контекста цикла используем разные переменные
    if (inLoop && loopVar) {
      const itemName = loopVar.includes('.')
        ? loopVar.split('.')[1]
        : loopVar.endsWith('s') ? loopVar.slice(0, -1) : loopVar + 'Item';

      return `{$${itemName}['thumbnail']['url']}`;
    }

    return value;
  });

  // 2. Преобразование alt атрибутов
  transformElementAttributes('img', 'alt', (value, element) => {
    const inLoop = !!element.closest('[data-contains-loop]');
    const parent = element.closest('[data-contains-loop]');
    const loopVar = parent?.getAttribute('data-contains-loop');

    if (inLoop && loopVar) {
      const itemName = loopVar.includes('.')
        ? loopVar.split('.')[1]
        : loopVar.endsWith('s') ? loopVar.slice(0, -1) : loopVar + 'Item';

      return `{$${itemName}['thumbnail']['alt']}`;
    }

    return value;
  });

  // 3. Преобразование href ссылок
  transformElementAttributes('a', 'href', (value, element) => {
    // Пропускаем ссылки, которые являются якорями или абсолютными URL
    if (value.startsWith('#') || value.startsWith('http')) {
      return value;
    }

    const inLoop = !!element.closest('[data-contains-loop]');
    const parent = element.closest('[data-contains-loop]');
    const loopVar = parent?.getAttribute('data-contains-loop');

    if (inLoop && loopVar) {
      const itemName = loopVar.includes('.')
        ? loopVar.split('.')[1]
        : loopVar.endsWith('s') ? loopVar.slice(0, -1) : loopVar + 'Item';

      return `{$${itemName}['url']}`;
    }

    // Для других ссылок можно использовать site.url
    if (value.startsWith('/')) {
      return `{$site['url']}${value}`;
    }

    return value;
  });

  // 4. Преобразование datetime для time элементов
  transformElementAttributes('time', 'dateTime', (value, element) => {
    const inLoop = !!element.closest('[data-contains-loop]');
    const parent = element.closest('[data-contains-loop]');
    const loopVar = parent?.getAttribute('data-contains-loop');

    if (inLoop && loopVar) {
      const itemName = loopVar.includes('.')
        ? loopVar.split('.')[1]
        : loopVar.endsWith('s') ? loopVar.slice(0, -1) : loopVar + 'Item';

      return `{$${itemName}['date']['formatted']}`;
    }

    return value;
  });

  // 5. Преобразование HTML содержимого для специфических контейнеров
  const processContentDivs = (selector: string, templateExpr: string): void => {
    document.querySelectorAll(selector).forEach(element => {
      const inLoop = !!element.closest('[data-contains-loop]');
      const parent = element.closest('[data-contains-loop]');
      const loopVar = parent?.getAttribute('data-contains-loop');

      if (inLoop && loopVar) {
        const itemName = loopVar.includes('.')
          ? loopVar.split('.')[1]
          : loopVar.endsWith('s') ? loopVar.slice(0, -1) : loopVar + 'Item';

        // Сохраняем классы и другие атрибуты
        const className = element.getAttribute('class');
        element.innerHTML = templateExpr.replace('$item', `$${itemName}`);
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
}

/**
 * Очищает строку от временных служебных атрибутов
 */
function cleanupAttributes(html: string): string {
  let result = html;

  // Заменяем комментарии-маркеры на Latte теги
  result = result.replace(/<!--\s*foreach \$([^\s]+) as \$([^\s]+)\s*-->/g, '{foreach $1 as $2}');
  result = result.replace(/<!--\s*\/foreach\s*-->/g, '{/foreach}');

  // Удаляем временные служебные атрибуты
  result = result.replace(/\s*data-contains-loop="[^"]*"/g, '');
  result = result.replace(/\s*data-var="[^"]*"/g, '');
  result = result.replace(/\s*data-loop="[^"]*"/g, '');

  return result;
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
