// doc-generator.ts
import { parse } from 'doctrine';
import fs from 'fs/promises';
import path from 'path';

interface DocBlock {
  description?: string;
  tags: Array<{
    title: string;
    name?: string;
    description?: string;
    type?: {
      name: string;
    };
  }>;
}

interface DocExtensions {
  extensions: {
    [key: string]: {
      description?: string;
      methods: {
        [key: string]: {
          content: string;
          examples: Array<{
            title: string;
            items: string[];
          }>;
        };
      };
    };
  };
}

type ExtendMatch = RegExpMatchArray & {
    groups?: {
        key: string;
        content: string;
    };
};

export class DocGenerator {
  private extensions: DocExtensions;

  constructor() {
    this.extensions = { extensions: {} };
  }

  async loadExtensions() {
    try {
      const data = await fs.readFile('./docs-data/extensions.json', 'utf-8');
      this.extensions = JSON.parse(data);
    } catch (e) {
      console.log('No extensions found, creating new file');
    }
  }

  async parsePhpFile(filePath: string) {
    const content = await fs.readFile(filePath, 'utf-8');
    const className = path.basename(filePath, '.php');
    
    const docs: DocBlock[] = [];
    
    // Получаем PHPDoc комментарий класса
    const classMatch = content.match(/\/\*\*([\s\S]*?)\*\/\s*(?:abstract\s+)?class\s+(\w+)/);
    if (classMatch && classMatch[1]) {
      const classDoc = parse(classMatch[1], { unwrap: true }) as DocBlock;
      docs.push(classDoc);
    } else {
      // Добавляем пустой объект, если не нашли документацию класса
      docs.push({
        description: '',
        tags: []
      });
    }
    
    // Получаем PHPDoc комментарии и сигнатуры методов
    const methodRegex = /\/\*\*([\s\S]*?)\*\/\s*(?:public|private|protected)?\s*(?:static\s+)?function\s+(\w+)\s*\(([^)]*)\)(?:\s*:\s*([^{;\n]+))?/g;
    
    let methodMatch;
    while ((methodMatch = methodRegex.exec(content)) !== null) {
      if (methodMatch.length >= 3) { // проверяем наличие минимум 3-х групп
        const docComment = methodMatch[1];
        const methodName = methodMatch[2];
        const argsString = methodMatch[3] || '';  // пустая строка если undefined
        const returnType = methodMatch[4] || '';  // пустая строка если undefined
        
        if (docComment && methodName) {
          const methodDoc = parse(docComment, { unwrap: true }) as DocBlock;
          
          // Добавляем тег method
          methodDoc.tags.push({
            title: 'method',
            name: methodName,
            description: ''
          });
          
          // Добавляем параметры на основе сигнатуры
          if (argsString.trim()) {
            const args = argsString.split(',').map(arg => arg.trim());
            
            args.forEach(arg => {
              // Разбираем аргумент на тип и имя
              const argMatch = arg.match(/(?:([^\s$]+)\s+)?(\$\w+)(?:\s*=\s*(.+))?/);
              if (argMatch && argMatch.length >= 3) {
                const argType = argMatch[1] || '';
                const argName = argMatch[2];
                
                if (argName) {
                  // Проверяем есть ли уже тег @param для этого аргумента
                  const paramExists = methodDoc.tags.some(tag => 
                    tag.title === 'param' && tag.name === argName.substring(1)
                  );
                  
                  if (!paramExists) {
                    methodDoc.tags.push({
                      title: 'param',
                      name: argName.substring(1), // Убираем $ из имени
                      description: argType || 'mixed'
                    });
                  }
                }
              }
            });
          }
          
          // Добавляем возвращаемое значение на основе сигнатуры
          if (returnType.trim()) {
            // Проверяем есть ли уже тег @return
            const returnExists = methodDoc.tags.some(tag => tag.title === 'return');
            
            if (!returnExists) {
              methodDoc.tags.push({
                title: 'return',
                description: returnType.trim()
              });
            }
          }
          
          docs.push(methodDoc);
        }
      }
    }

    return this.generateMarkdown(className, docs);
  }

  private generateMarkdown(className: string, docs: DocBlock[]) {
    let markdown = `# ${className}\n\n`;

    // Добавляем базовое описание класса из PHPDoc
    markdown += `<!-- @doc-source: ${className} -->\n`;
    markdown += `${docs[0]?.description || ''}\n\n`;

    // Добавляем расширенное описание из extensions.json если есть
    const classExtension = this.extensions.extensions[className];
    if (classExtension?.description) {
        markdown += `<!-- @doc-extend: ${className} -->\n`;
        markdown += `${classExtension.description}\n`;
        markdown += `<!-- @doc-extend-end -->\n\n`;
    }

    markdown += `## Methods\n\n`;

    // Генерируем документацию для методов
    docs.slice(1).forEach((doc) => {
        const methodTag = doc.tags.find(t => t.title === 'method');
        const methodName = methodTag?.name;
        
        if (!methodName) return;

        markdown += `### ${methodName}\n`;
        
        // Базовое описание метода из PHPDoc
        markdown += `<!-- @doc-source: ${className}.${methodName} -->\n`;
        markdown += `${doc.description || ''}\n\n`;

        // Добавляем параметры если есть
        const params = doc.tags.filter(t => t.title === 'param');
        if (params.length > 0) {
            markdown += `#### Parameters\n\n`;
            params.forEach(param => {
                const type = param.type?.name || '';
                markdown += `- \`${type}\`: ${param.name} ${param.description || ''}\n`;
            });
            markdown += '\n';
        }

        // Добавляем информацию о возвращаемом значении
        const returnTag = doc.tags.find(t => t.title === 'return');
        if (returnTag) {
            markdown += `#### Returns\n\n`;
            markdown += `${returnTag.type?.name || ''}\n\n`;
        }

        // Добавляем расширенное описание метода из extensions.json если есть
        const methodExtension = classExtension?.methods[methodName];
        if (methodExtension) {
            markdown += `<!-- @doc-extend: ${className}.${methodName} -->\n`;
            markdown += `${methodExtension.content}\n`;
            
            // Добавляем примеры если они есть
            if (methodExtension.examples && methodExtension.examples.length > 0) {
                methodExtension.examples.forEach(example => {
                    markdown += `#### ${example.title}\n`;
                    example.items.forEach(item => {
                        markdown += `- ${item}\n`;
                    });
                    markdown += '\n';
                });
            }
            
            markdown += `<!-- @doc-extend-end -->\n\n`;
        }
    });

    return markdown;
  }

  async updateExtensionsFromMd(mdPath: string) {
    const content = await fs.readFile(mdPath, 'utf-8');
    const extendMatches = content.matchAll(/<!-- @doc-extend: (.*?) -->(.*?)<!-- @doc-extend-end -->/gs);

    for (const match of extendMatches) {
        // Проверяем что у нас есть все необходимые группы
        if (match[1] && match[2]) {
            const key = match[1].trim();
            const contentMatch = match[2].trim();
            
            const [className, methodName] = key.split('.');
            
            // Проверяем что у нас есть имя класса
            if (!className) continue;

            // Инициализируем структуру для класса если её нет
            if (!this.extensions.extensions[className]) {
                this.extensions.extensions[className] = {
                    methods: {}
                };
            }

            // Обрабатываем метод или описание класса
            if (methodName) {
                if (!this.extensions.extensions[className].methods[methodName]) {
                    this.extensions.extensions[className].methods[methodName] = {
                        content: contentMatch,
                        examples: []
                    };
                } else {
                    // Обновляем существующий контент
                    this.extensions.extensions[className].methods[methodName].content = contentMatch;
                }
            } else {
                // Обновляем описание класса
                this.extensions.extensions[className].description = contentMatch;
            }
        }
    }

    await fs.writeFile(
        './docs-data/extensions.json',
        JSON.stringify(this.extensions, null, 2)
    );
  }
}