const fs = require('fs');
const path = require('path');

// Корневая директория документации
const DOCS_ROOT = './docs';
const OUTPUT_FILE = './docs/llms.txt';

// Функция для получения структуры директории
function getDirectoryStructure(dir, prefix = '') {
  let result = '';
  const items = fs.readdirSync(dir, { withFileTypes: true });
  
  items.forEach((item, index) => {
    const isLast = index === items.length - 1;
    const itemPrefix = prefix + (isLast ? '└── ' : '├── ');
    const fullPath = path.join(dir, item.name);
    
    if (item.isDirectory()) {
      result += `${itemPrefix}${item.name}\n`;
      const nestedPrefix = prefix + (isLast ? '    ' : '│   ');
      result += getDirectoryStructure(fullPath, nestedPrefix);
    } else {
      result += `${itemPrefix}${item.name}\n`;
    }
  });
  
  return result;
}

// Функция для получения краткого содержания файла MD
function getMdSummary(filePath) {
  try {
    const content = fs.readFileSync(filePath, 'utf8');
    const lines = content.split('\n');
    
    // Извлекаем заголовок (если есть)
    let title = '';
    for (const line of lines) {
      if (line.startsWith('# ')) {
        title = line.substring(2).trim();
        break;
      }
    }
    
    // Извлекаем короткое описание (первый параграф)
    let description = '';
    let inParagraph = false;
    for (const line of lines) {
      if (line.trim() === '' && inParagraph) break;
      if (line.trim() !== '' && !line.startsWith('#')) {
        description += line + ' ';
        inParagraph = true;
      }
    }
    
    return {
      title: title || path.basename(filePath, '.md'),
      description: description.trim()
    };
  } catch (error) {
    return {
      title: path.basename(filePath, '.md'),
      description: 'No description available'
    };
  }
}

// Функция для рекурсивного сбора документации
function collectDocumentation(dir, relativePath = '') {
  let result = [];
  const items = fs.readdirSync(dir, { withFileTypes: true });
  
  for (const item of items) {
    const itemRelativePath = path.join(relativePath, item.name);
    const fullPath = path.join(dir, item.name);
    
    if (item.isDirectory()) {
      // Добавляем раздел для директории
      result.push({
        type: 'section',
        title: item.name,
        path: itemRelativePath,
        children: collectDocumentation(fullPath, itemRelativePath)
      });
    } else if (item.name.endsWith('.md')) {
      // Добавляем файл документации
      const summary = getMdSummary(fullPath);
      result.push({
        type: 'document',
        title: summary.title,
        description: summary.description,
        path: itemRelativePath
      });
    }
  }
  
  return result;
}

// Функция для генерации текста llms-full.txt
function generateLlmsText() {
  let output = `# WP-Fasty Documentation - /llms-full.txt

This is the /llms-full.txt file for WP-Fasty documentation, providing a comprehensive snapshot of all documentation content in a format optimized for Large Language Models (LLMs) while maintaining human readability.

**Directory Structure**

The 'Directory Structure' section presents a hierarchical view of the documentation files:

## Directory Structure

\`\`\`
${getDirectoryStructure(DOCS_ROOT)}
\`\`\`

## Documentation Content

`;

  // Получаем документацию
  const docs = collectDocumentation(DOCS_ROOT);
  
  // Функция для рекурсивного форматирования документации
  function formatDocs(items, level = 0) {
    let text = '';
    const indent = '  '.repeat(level);
    
    for (const item of items) {
      if (item.type === 'section') {
        text += `${indent}### ${item.title}\n\n`;
        if (item.children && item.children.length > 0) {
          text += formatDocs(item.children, level + 1);
        }
      } else {
        text += `${indent}- [${item.title}](/${item.path}): ${item.description}\n\n`;
      }
    }
    
    return text;
  }
  
  output += formatDocs(docs);
  
  return output;
}

// Генерируем и сохраняем файл
const llmsText = generateLlmsText();
fs.writeFileSync(OUTPUT_FILE, llmsText);

console.log(`llms-full.txt успешно создан: ${OUTPUT_FILE}`);