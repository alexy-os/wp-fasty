// cd .workspaces/frontend && bun run scripts/build-to-latte.ts
import { convertReactToLatte } from './react-to-latte';
import path from 'path';
import fs from 'fs/promises';

// Функция для конвертации точечной нотации в квадратные скобки
function convertDotToArrayNotation(content: string): string {
  // Новое регулярное выражение, которое корректно обрабатывает случаи с переменными Latte
  const regex = /(\$[a-zA-Z0-9_]+)(\.[a-zA-Z0-9_]+)+/g;

  // Рекурсивная функция для обработки всех вхождений
  function processMatches(text: string): string {
    const matches = [...text.matchAll(regex)];

    if (matches.length === 0) {
      return text;
    }

    let result = text;

    // Обрабатываем каждое совпадение, начиная с последнего, чтобы не сбить индексы
    for (let i = matches.length - 1; i >= 0; i--) {
      const match = matches[i];
      const fullMatch = match[0];
      const startIndex = match.index!;
      const endIndex = startIndex + fullMatch.length;

      // Разбиваем на переменную и пути
      const parts = fullMatch.split('.');
      const varName = parts[0];
      const props = parts.slice(1);

      // Строим новое выражение с квадратными скобками
      let newExpression = varName;
      for (const prop of props) {
        newExpression += `['${prop}']`;
      }

      // Заменяем в исходном тексте
      result = result.substring(0, startIndex) + newExpression + result.substring(endIndex);
    }

    // Рекурсивно обрабатываем результат, так как могут появиться новые совпадения
    return processMatches(result);
  }

  return processMatches(content);
}

async function main() {
  //const frontPagePath = path.resolve(__dirname, '../src/uikits/latte/components/NavbarTemplate.tsx');
  //const outputPath = path.resolve(__dirname, '../src/templates/latte/navbar.latte');
  const frontPagePath = path.resolve(__dirname, '../src/uikits/latte/components/FrontPageTemplate.tsx');
  const outputPath = path.resolve(__dirname, '../src/templates/latte/front-page.latte');

  console.log('Starting conversion...');

  try {
    await convertReactToLatte(frontPagePath, outputPath);

    // После конвертации React в Latte, преобразуем точечную нотацию
    console.log('Converting dot notation to array notation...');
    const content = await fs.readFile(outputPath, 'utf8');
    const convertedContent = convertDotToArrayNotation(content);
    await fs.writeFile(outputPath, convertedContent);

    console.log('Conversion complete!');
  } catch (error) {
    console.error('Conversion failed:', error);
    process.exit(1);
  }
}

main();
