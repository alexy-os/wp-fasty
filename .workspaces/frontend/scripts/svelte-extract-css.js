import fs from 'fs';
import path from 'path';
import { parse } from 'svelte/compiler';

// Функция для извлечения стилей из Svelte-компонента
function extractCSSFromSvelte(filePath) {
  const source = fs.readFileSync(filePath, 'utf-8');

  // Парсим Svelte-компонент
  const parsed = parse(source);

  // Извлекаем содержимое блока style
  let cssContent = '';

  if (parsed.css && parsed.css.content) {
    cssContent = parsed.css.content.trim();
  }

  return cssContent;
}

// Функция для сохранения CSS в отдельный файл
function saveCSSToFile(componentPath, outputDir) {
  const componentName = path.basename(componentPath, '.svelte');
  const css = extractCSSFromSvelte(componentPath);

  if (!css) {
    console.log(`No CSS found in ${componentName}`);
    return;
  }

  // Создаем директорию, если не существует
  if (!fs.existsSync(outputDir)) {
    fs.mkdirSync(outputDir, { recursive: true });
  }

  // Путь для вывода CSS
  const outputPath = path.join(outputDir, `${componentName.toLowerCase()}.css`);

  // Записываем CSS в файл
  fs.writeFileSync(outputPath, css);

  console.log(`CSS extracted from ${componentName} to ${outputPath}`);
  return outputPath;
}

// Обработка всех компонентов в директории
function processAllComponents(srcDir, outputDir) {
  const files = fs.readdirSync(srcDir);
  const cssFiles = [];

  for (const file of files) {
    if (file.endsWith('.svelte')) {
      const filePath = path.join(srcDir, file);
      const cssPath = saveCSSToFile(filePath, outputDir);
      if (cssPath) {
        cssFiles.push(cssPath);
      }
    }
  }

  // Опционально: создаем индексный файл для импорта всех стилей
  const indexPath = path.join(outputDir, 'index.css');
  const imports = cssFiles.map(file => `@import "${path.relative(outputDir, file)}";`).join('\n');
  fs.writeFileSync(indexPath, imports);

  console.log(`Created index file at ${indexPath}`);
}

// Запускаем извлечение CSS
const srcDir = './src/components/ui';
const cssOutputDir = './src/assets/css/components';

processAllComponents(srcDir, cssOutputDir);