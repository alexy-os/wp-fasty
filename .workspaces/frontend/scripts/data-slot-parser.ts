// cd .workspaces/frontend && bun scripts/data-slot-parser.ts
import fs from 'node:fs';
import path from 'node:path';
import { glob } from 'glob';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import generate from '@babel/generator';
import * as t from '@babel/types';

interface DataSlotParserConfig {
  // Директория, в которой ищем компоненты
  inputDir: string;
  // Глобальный паттерн для поиска компонентов
  componentsGlob: string;
  // Директория для стилей
  stylesOutputDir: string;
  // Директория для компонентов с семантическими классами
  componentsOutputDir: string;
}

const DEFAULT_CONFIG: DataSlotParserConfig = {
  inputDir: './src/uikits/ui8px/core/source',
  componentsGlob: '**/*.tsx',
  stylesOutputDir: './src/uikits/ui8px/core/styles',
  componentsOutputDir: './src/uikits/ui8px/core/components'
};

class DataSlotParser {
  public config: DataSlotParserConfig;
  private cssFiles: string[] = [];
  public isWatching = false;

  constructor(config: Partial<DataSlotParserConfig> = {}) {
    this.config = { ...DEFAULT_CONFIG, ...config };

    // Проверяем флаг --watch
    const args = process.argv.slice(2);
    this.isWatching = args.includes('--watch');
  }

  public async generateAll(): Promise<void> {
    // Используем рекурсивный поиск всех .tsx файлов в указанной директории
    const pattern = `${this.config.inputDir.replace(/\\/g, '/')}/${this.config.componentsGlob}`;
    console.log('Glob pattern:', pattern);

    const componentFiles = await glob(pattern);
    console.log(`Found component files: ${componentFiles.length}`);

    this.cssFiles = []; // Сбрасываем список CSS-файлов перед генерацией

    for (const file of componentFiles) {
      await this.processComponent(file);
    }

    this.generateIndexCss();
  }

  private async processComponent(componentPath: string): Promise<void> {
    try {
      console.log(`Processing component: ${componentPath}`);
      const componentName = this.getComponentName(componentPath);

      // Читаем содержимое файла компонента
      const componentContent = fs.readFileSync(componentPath, 'utf-8');

      // Парсим с помощью Babel
      const ast = parse(componentContent, {
        sourceType: 'module',
        plugins: ['jsx', 'typescript']
      });

      // Структура для хранения извлеченных стилей
      const stylesMap: Record<string, string> = {};

      // Обходим AST и ищем атрибуты data-slot и функцию cn()
      traverse(ast, {
        JSXAttribute(path) {
          // Ищем атрибут data-slot
          if (path.node.name.name === 'data-slot' && t.isStringLiteral(path.node.value)) {
            const slotName = path.node.value.value;

            // Ищем родительский JSX элемент
            const jsxElement = path.findParent(p => p.isJSXOpeningElement());
            if (!jsxElement) return;

            // Ищем атрибут className в этом элементе
            const classNameAttr = jsxElement.node.attributes.find(
              attr => t.isJSXAttribute(attr) &&
                attr.name.name === 'className'
            );

            if (classNameAttr && t.isJSXAttribute(classNameAttr) &&
              t.isJSXExpressionContainer(classNameAttr.value)) {
              // Проверяем, что это вызов функции cn()
              const expr = classNameAttr.value.expression;
              if (t.isCallExpression(expr) &&
                (t.isIdentifier(expr.callee) && expr.callee.name === 'cn')) {

                // Получаем первый аргумент cn() - это наши стили
                const firstArg = expr.arguments[0];
                if (t.isStringLiteral(firstArg)) {
                  // Сохраняем стили для этого слота
                  stylesMap[slotName] = firstArg.value;
                }
              }
            }
          }
        }
      });

      // Если нашли стили, генерируем CSS и копию компонента
      if (Object.keys(stylesMap).length > 0) {
        this.generateCssFile(componentName, stylesMap);
        this.generateSemanticComponent(componentPath, stylesMap);
      }
    } catch (err) {
      console.error(`Error processing component ${componentPath}:`, err);
    }
  }

  private generateCssFile(componentName: string, stylesMap: Record<string, string>): void {
    let cssContent = '';

    // Генерируем CSS для каждого data-slot
    Object.entries(stylesMap).forEach(([slotName, styles]) => {
      if (!styles.trim()) return; // Пропускаем пустые стили
      cssContent += `.${slotName} {\n  @apply ${styles};\n}\n\n`;
    });

    if (cssContent.trim()) {
      // Создаем директорию для стилей, сохраняя структуру исходной директории
      const stylesDirPath = path.join(this.config.stylesOutputDir, 'components');
      fs.mkdirSync(stylesDirPath, { recursive: true });

      // Путь к CSS файлу
      const outputFile = path.join(stylesDirPath, `${componentName}.css`);

      // Сохраняем CSS файл
      fs.writeFileSync(outputFile, cssContent.trim());
      this.cssFiles.push(outputFile);
      console.log(`Generated CSS: ${outputFile}`);
    } else {
      console.log(`Skipped empty CSS for: ${componentName}`);
    }
  }

  private generateSemanticComponent(componentPath: string, stylesMap: Record<string, string>): void {
    try {
      // Читаем оригинальный компонент
      const componentContent = fs.readFileSync(componentPath, 'utf-8');

      // Парсим с помощью Babel
      const ast = parse(componentContent, {
        sourceType: 'module',
        plugins: ['jsx', 'typescript']
      });

      // Модифицируем AST, заменяя утилитарные классы на семантические
      traverse(ast, {
        JSXAttribute(path) {
          if (path.node.name.name === 'data-slot' && t.isStringLiteral(path.node.value)) {
            const slotName = path.node.value.value;

            // Если у нас есть стили для этого слота
            if (stylesMap[slotName]) {
              // Ищем родительский JSX элемент
              const jsxElement = path.findParent(p => p.isJSXOpeningElement());
              if (!jsxElement) return;

              // Ищем атрибут className
              const classNameAttr = jsxElement.node.attributes.find(
                attr => t.isJSXAttribute(attr) && attr.name.name === 'className'
              );

              if (classNameAttr && t.isJSXAttribute(classNameAttr) &&
                t.isJSXExpressionContainer(classNameAttr.value)) {
                // Проверяем, что это вызов функции cn()
                const expr = classNameAttr.value.expression;
                if (t.isCallExpression(expr) &&
                  (t.isIdentifier(expr.callee) && expr.callee.name === 'cn')) {

                  // Заменяем первый аргумент cn() на семантический класс
                  expr.arguments[0] = t.stringLiteral(slotName);
                }
              }
            }
          }
        }
      });

      // Генерируем код из модифицированного AST
      const output = generate(ast, { retainLines: true });

      // Создаем директорию для семантических компонентов
      const relativePath = path.relative(this.config.inputDir, path.dirname(componentPath));
      const outputDir = path.join(this.config.componentsOutputDir, relativePath);
      fs.mkdirSync(outputDir, { recursive: true });

      // Сохраняем модифицированный компонент
      const fileName = path.basename(componentPath);
      const outputFile = path.join(outputDir, fileName);
      fs.writeFileSync(outputFile, output.code);
      console.log(`Generated semantic component: ${outputFile}`);
    } catch (err) {
      console.error(`Error generating semantic component for ${componentPath}:`, err);
    }
  }

  private generateIndexCss(): void {
    if (this.cssFiles.length === 0) {
      console.log('No CSS files to include in index.css');
      return;
    }

    const stylesDirPath = path.join(this.config.stylesOutputDir, 'components');
    const indexPath = path.join(stylesDirPath, 'index.css');

    const imports = this.cssFiles.map(file =>
      `@import "./${path.basename(file)}";`
    ).join('\n');

    fs.writeFileSync(indexPath, imports);
    console.log(`Generated index CSS: ${indexPath}`);
  }

  private getComponentName(componentPath: string): string {
    // Получаем имя компонента из пути к файлу
    const basename = path.basename(componentPath, path.extname(componentPath));

    // Преобразуем CamelCase или PascalCase в kebab-case для CSS-файлов
    return basename.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
  }

  public startWatching(): void {
    if (this.isWatching) {
      console.log('Watching for changes in component files...');

      const watchDir = path.resolve(this.config.inputDir);
      fs.watch(watchDir, { recursive: true }, async (_, filename) => {
        if (!filename) return;
        if (!filename.endsWith('.tsx')) return;

        const fullPath = path.join(watchDir, filename);
        console.log(`File changed: ${fullPath}`);

        // Небольшая задержка для стабилизации файловой системы
        setTimeout(async () => {
          try {
            // Обрабатываем только измененный файл
            await this.processComponent(fullPath);
            this.generateIndexCss();
            console.log('Regenerated files for the changed component');
          } catch (err) {
            console.error('Error regenerating files:', err);
          }
        }, 100);
      });
    }
  }
}

// Запуск скрипта
const parser = new DataSlotParser();

async function run() {
  try {
    await parser.generateAll();

    // Запускаем режим наблюдения, если указан флаг --watch
    if (parser.isWatching) {
      parser.startWatching();
    }
  } catch (err) {
    console.error('Error running data-slot-parser:', err);
    process.exit(1);
  }
}

run(); 