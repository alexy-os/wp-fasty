import fs from 'fs';
import path from 'path';
import { glob } from 'glob';

/**
 * Конвертирует React компоненты в Latte шаблоны 
 * через регулярные выражения для замены синтаксиса
 */
export function convertReactToLatte(
  sourcePattern: string,
  outputDir: string,
  contextName: string = 'site'
): void {
  // Находим все React компоненты
  const files = glob.sync(sourcePattern);

  files.forEach(file => {
    try {
      // Читаем содержимое файла
      const code = fs.readFileSync(file, 'utf-8');

      // Находим JSX разметку в компоненте (между return и ;/})
      const jsxMatch = code.match(/return\s*\(\s*([^]*?)\s*\)(;|})/);

      if (!jsxMatch) {
        console.warn(`Не удалось найти JSX в файле ${file}`);
        return;
      }

      // Получаем JSX часть
      const jsx = jsxMatch[1];

      // Конвертируем JSX в Latte
      const latteTemplate = convertJsxToLatte(jsx, contextName);

      // Создаем имя выходного файла
      const componentName = path.basename(file, '.tsx')
        .replace('Latte', '')
        .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
        .toLowerCase();

      const outputPath = path.join(outputDir, `${componentName}.latte`);

      // Создаем директорию и сохраняем файл
      fs.mkdirSync(path.dirname(outputPath), { recursive: true });
      fs.writeFileSync(outputPath, latteTemplate);

      console.log(`Сконвертирован ${file} в ${outputPath}`);
    } catch (error) {
      console.error(`Ошибка при конвертации ${file}:`, error);
    }
  });
}

/**
 * Конвертирует JSX в Latte шаблон
 */
function convertJsxToLatte(jsx: string, contextName: string): string {
  let latte = jsx;

  // Шаг 1: Заменяем условные выражения на Latte условия
  latte = latte.replace(
    /{(\w+)(?:\.(\w+))?(?:\.(\w+))?\s*&&\s*\(/g,
    (_, obj, prop1, prop2) => {
      const path = [obj, prop1, prop2].filter(Boolean).join('.');
      return `{if isset($${contextName}.${path})}`;
    }
  );

  // Закрываем условные блоки
  latte = latte.replace(/\s*\)\s*}/g, '{/if}');

  // Шаг 2: Заменяем циклы map на foreach
  latte = latte.replace(
    /{(\w+)(?:\.(\w+))?(?:\.(\w+))?(?:\.(\w+))?\.map\(\((\w+)(?:,\s*\w+)?\)\s*=>\s*\(/g,
    (_, obj, prop1, prop2, prop3, item) => {
      const path = [obj, prop1, prop2, prop3].filter(Boolean).join('.');
      return `{foreach $${contextName}.${path} as $${item}}`;
    }
  );

  // Закрываем циклы
  latte = latte.replace(/\)\)}/g, '{/foreach}');

  // Шаг 3: Заменяем интерполяцию строк на Latte синтаксис
  latte = latte.replace(
    /(\w+)=\{`([^`]*)\$\{([^{}]+)\}([^`]*)`\}/g,
    (_, attr, prefix, expr, suffix) => {
      // Получаем переменную внутри интерполяции
      let latteVar = expr.trim();

      // Если это переменная в цикле (button, image)
      if (latteVar.startsWith('button.') || latteVar.startsWith('image.') || latteVar === 'button' || latteVar === 'image') {
        latteVar = latteVar.replace(/^(button|image)\.?/, '$$$1.');
      } else {
        // Добавляем контекст для остальных переменных
        latteVar = `$${contextName}.${latteVar}`;
      }

      return `${attr}="${prefix}{${latteVar}}${suffix}"`;
    }
  );

  // Шаг 4: Заменяем переменные в атрибутах
  latte = latte.replace(
    /(\w+)=\{([^{}]+)\}/g,
    (_, attr, expr) => {
      // Простые переменные
      if (/^\w+(?:\.\w+)*$/.test(expr)) {
        let varPath = expr;

        // Если это переменная цикла
        if (expr.startsWith('button.') || expr.startsWith('image.')) {
          varPath = expr.replace(/^(button|image)\./, '$$$1.');
        } else {
          varPath = `$${contextName}.${expr}`;
        }

        return `${attr}="{${varPath}}"`;
      }

      // Возвращаем как есть для сложных выражений
      return `${attr}="{${expr}}"`;
    }
  );

  // Шаг 5: Заменяем переменные внутри фигурных скобок
  latte = latte.replace(
    /{(\w+)(?:\.(\w+))?(?:\.(\w+))?(?:\.(\w+))?}/g,
    (_, obj, prop1, prop2, prop3) => {
      // Если это переменная цикла
      if (obj === 'button' || obj === 'image') {
        const path = [obj, prop1, prop2, prop3].filter(Boolean).join('.');
        return `{$${path}}`;
      }

      // Обычная переменная контекста
      const path = [obj, prop1, prop2, prop3].filter(Boolean).join('.');
      return `{$${contextName}.${path}}`;
    }
  );

  // Шаг 6: Стилистические исправления
  latte = latte
    // className -> class
    .replace(/className=/g, 'class=')
    // Удаляем атрибуты key
    .replace(/\s+key=\{[^}]+\}/g, '')
    // Исправляем проблемы с самозакрывающимися тегами
    .replace(/<(img|input|br|hr)([^>]*?)><\/(img|input|br|hr)>/g, '<$1$2 />');

  return latte;
}

// Примери использования
// convertReactToLatte('src/uikits/latte/**/*Latte.tsx', 'src/templates/latte');
