import { DocGenerator } from './generator';
import fs from 'fs/promises';
import path from 'path';

interface DocTree {
  title: string;
  path: string;
  children: DocTree[];
}

async function buildDocTree(sourcePath: string): Promise<DocTree[]> {
  const tree: DocTree[] = [];
  const entries = await fs.readdir(sourcePath, { withFileTypes: true });

  for (const entry of entries) {
    const fullPath = path.join(sourcePath, entry.name);
    
    if (entry.isDirectory()) {
      const children = await buildDocTree(fullPath);
      if (children.length > 0) {
        tree.push({
          title: entry.name,
          path: fullPath,
          children
        });
      }
    } else if (entry.name.endsWith('.php')) {
      tree.push({
        title: path.basename(entry.name, '.php'),
        path: fullPath,
        children: []
      });
    }
  }

  return tree;
}

async function ensureDirectoryExists(dirPath: string) {
  try {
    await fs.mkdir(dirPath, { recursive: true });
  } catch (error) {
    if ((error as NodeJS.ErrnoException).code !== 'EEXIST') {
      throw error;
    }
  }
}

async function generateDocs(tree: DocTree[], generator: DocGenerator, baseOutputPath: string) {
  for (const node of tree) {
    if (node.children.length > 0) {
      // Создаем директорию для вложенных файлов
      const outputDir = path.join(baseOutputPath, path.relative('./wp-fasty-storefront/fasty', node.path));
      await ensureDirectoryExists(outputDir);
      
      // Рекурсивно обрабатываем вложенные файлы
      await generateDocs(node.children, generator, baseOutputPath);
    } else if (node.path.endsWith('.php')) {
      // Генерируем markdown для PHP файла
      const markdown = await generator.parsePhpFile(node.path);
      
      // Создаем путь для markdown файла
      const relativePath = path.relative('./wp-fasty-storefront/fasty', node.path);
      const mdPath = path.join(
        baseOutputPath,
        path.dirname(relativePath),
        `${path.basename(relativePath, '.php')}.md`
      );
      
      // Создаем директорию если нужно
      await ensureDirectoryExists(path.dirname(mdPath));
      
      // Сохраняем markdown файл
      await fs.writeFile(mdPath, markdown);
      
      // Обновляем extensions из markdown
      await generator.updateExtensionsFromMd(mdPath);
    }
  }
}

function generateIndex(tree: DocTree[], level: number = 0): string {
  let index = '';
  const indent = '  '.repeat(level);

  for (const node of tree) {
    const relativePath = path.relative(
      './docs',
      node.path.replace('.php', '.md')
    );
    
    if (node.children.length > 0) {
      index += `${indent}- ${node.title}/\n`;
      index += generateIndex(node.children, level + 1);
    } else {
      index += `${indent}- [${node.title}](${relativePath})\n`;
    }
  }

  return index;
}

async function initializeExtensions() {
  const extensionsDir = './docs-data';
  const extensionsFile = path.join(extensionsDir, 'extensions.json');

  // Создаем директорию docs-data если её нет
  await ensureDirectoryExists(extensionsDir);

  // Создаем пустой extensions.json если его нет
  try {
    await fs.access(extensionsFile);
  } catch {
    await fs.writeFile(
      extensionsFile,
      JSON.stringify({ extensions: {} }, null, 2)
    );
  }
}

async function main() {
  // Инициализируем структуру для extensions
  await initializeExtensions();

  const generator = new DocGenerator();
  await generator.loadExtensions();

  // Строим дерево документации
  const sourceDir = './wp-fasty-storefront/fasty';
  const outputDir = './docs';
  
  console.log('Building documentation tree...');
  const docTree = await buildDocTree(sourceDir);

  // Создаем базовую директорию для документации
  await ensureDirectoryExists(outputDir);

  // Генерируем документацию
  console.log('Generating documentation...');
  await generateDocs(docTree, generator, outputDir);

  // Генерируем индексный файл
  console.log('Generating index...');
  const indexContent = `# Documentation Index\n\n${generateIndex(docTree)}`;
  await fs.writeFile(path.join(outputDir, 'index.md'), indexContent);

  console.log('Documentation generated successfully!');
}

main().catch(error => {
  console.error('Error generating documentation:', error);
  process.exit(1);
});