import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import * as t from '@babel/types';
import * as fs from 'fs';
import * as path from 'path';
import { transformReactToLatte } from './transforms/react-to-latte-transform';
import { validateReactSyntax } from './validators/react-syntax-validator';
import { resolveImports } from './utils/import-resolver';

/**
 * Преобразует React компонент в Latte шаблон
 */
async function convertReactToLatte(inputFile: string, outputFile: string, options = {}) {
  try {
    // Чтение файла React компонента
    const source = fs.readFileSync(inputFile, 'utf-8');

    // Валидация синтаксиса (проверка map синтаксиса и других паттернов)
    const validationErrors = validateReactSyntax(source);
    if (validationErrors.length > 0) {
      console.error('Syntax validation errors:');
      validationErrors.forEach(err => console.error(`- ${err}`));
      return false;
    }

    // Парсинг кода в AST
    const ast = parse(source, {
      sourceType: 'module',
      plugins: ['jsx', 'typescript'],
    });

    // Находим и резолвим импорты компонентов
    const imports = resolveImports(ast, path.dirname(inputFile));

    // Преобразование AST в Latte-синтаксис
    const latteTemplate = await transformReactToLatte(ast, imports);

    // Запись результата в файл
    fs.writeFileSync(outputFile, latteTemplate, 'utf-8');
    console.log(`Успешно сконвертировано в ${outputFile}`);
    return true;
  } catch (error) {
    console.error('Ошибка конвертации:', error);
    return false;
  }
}

// CLI интерфейс
if (require.main === module) {
  const args = process.argv.slice(2);
  if (args.length < 2) {
    console.log('Использование: node react-to-latte.js <входной-файл.tsx> <выходной-файл.latte>');
    process.exit(1);
  }

  convertReactToLatte(args[0], args[1])
    .then(success => process.exit(success ? 0 : 1));
}

export { convertReactToLatte };
