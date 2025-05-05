// cd .workspaces/frontend && bun scripts/schema-to-typescript.ts
import fs from 'fs';
import { compile } from 'json-schema-to-typescript';
import path from 'path';

// Определяем известные типы данных
const knownTypes = {
  // Ключевые типы
  CategoryItem: {
    name: 'string',
    url: 'string',
    id: 'number',
    slug: 'string',
    description: 'string',
    count: 'number'
  },
  MenuItem: {
    title: 'string',
    url: 'string',
    id: 'number',
    order: 'number',
    parent: 'null | number',
    classes: 'string[]',
    current: 'boolean'
  },
  FeaturedImage: {
    url: 'string',
    alt: 'string',
    width: 'number',
    height: 'number'
  }
};

// Пути известных свойств и их ожидаемые типы
const knownPropertyPaths = {
  'page.site': 'SiteContext',
  'page.page.categories': 'CategoryItem[]',
  'page.page.featuredImage': 'FeaturedImage | null',
  'page.page.thumbnail': 'FeaturedImage | null',
  'page.menu.primary.items': 'MenuItem[]',
  'archive.site': 'SiteContext',
  'archive.posts': 'PostItem[]',
  'archive.menu.primary.items': 'MenuItem[]'
};

async function generateTypeScriptInterfaces() {
  // Чтение исходного JSON
  const rawData = JSON.parse(fs.readFileSync('./source/context.schema.json', 'utf8'));

  // Создаем директорию для типов, если её нет
  const typesDir = './types';
  if (!fs.existsSync(typesDir)) {
    fs.mkdirSync(typesDir, { recursive: true });
  }

  // Генерация базовых типов
  await generateSharedTypes();

  // Преобразование JSON в JSON Schema с улучшенной типизацией
  const pageSchema = createSchemaFromObject(rawData.page, 'PageContext');
  const archiveSchema = createSchemaFromObject(rawData.archive, 'ArchiveContext');

  // Генерация интерфейсов для PageContext
  const pageTypeScript = await compile(pageSchema, 'PageContext', {
    bannerComment: '/* Auto-generated from context.schema.json - PageContext */',
    style: { singleQuote: true, semi: true, tabWidth: 2 },
    additionalProperties: false
  });

  // Генерация интерфейсов для ArchiveContext  
  const archiveTypeScript = await compile(archiveSchema, 'ArchiveContext', {
    bannerComment: '/* Auto-generated from context.schema.json - ArchiveContext */',
    style: { singleQuote: true, semi: true, tabWidth: 2 },
    additionalProperties: false
  });

  // Создаем дополнительные импорты
  const pageImports = `import { SiteContext, CategoryItem, MenuItem, FeaturedImage } from './shared-types';\n\n`;
  const archiveImports = `import { SiteContext, MenuItem, PostItem } from './shared-types';\n\n`;

  // Создаем общий тип Context
  const contextTypeScript = `
/* Auto-generated from context.schema.json - Context */

import { PageContext } from './page-context';
import { ArchiveContext } from './archive-context';

/**
 * Объединенный тип контекста
 * Представляет либо PageContext, либо ArchiveContext в зависимости от типа запрашиваемой страницы
 */
export type Context = PageContext | ArchiveContext;

/**
 * Утилита для определения типа контекста
 */
export function isPageContext(context: Context): context is PageContext {
  return 'page' in context;
}

/**
 * Утилита для определения типа контекста
 */
export function isArchiveContext(context: Context): context is ArchiveContext {
  return 'archive' in context;
}
`;

  // Запись результатов с добавлением импортов
  fs.writeFileSync('./types/page-context.ts', pageImports + replaceKnownTypes(pageTypeScript));
  fs.writeFileSync('./types/archive-context.ts', archiveImports + replaceKnownTypes(archiveTypeScript));
  fs.writeFileSync('./types/context.types.ts', contextTypeScript);

  console.log('TypeScript interfaces generated successfully');
}

// Функция для создания JSON Schema из объекта
function createSchemaFromObject(obj, title) {
  const schema = {
    $schema: 'http://json-schema.org/draft-07/schema#',
    title,
    type: 'object',
    properties: {},
    required: [],
    additionalProperties: false
  };

  // Рекурсивно преобразуем каждое свойство
  for (const [key, value] of Object.entries(obj)) {
    schema.properties[key] = convertToJsonSchemaType(value, `${title.toLowerCase()}.${key}`);
    schema.required.push(key);
  }

  return schema;
}

// Функция для определения типа JSON Schema с учетом известных типов
function convertToJsonSchemaType(value, path = '') {
  // Проверяем, есть ли это свойство в известных путях
  if (knownPropertyPaths[path]) {
    return { $ref: `#/definitions/${knownPropertyPaths[path]}` };
  }

  if (value === null) {
    return { type: 'null' };
  }

  if (Array.isArray(value)) {
    if (value.length === 0) {
      return {
        type: 'array',
        items: { type: 'object' }
      };
    }

    return {
      type: 'array',
      items: convertToJsonSchemaType(value[0], `${path}[]`)
    };
  }

  if (typeof value === 'object') {
    const properties = {};
    const required = [];

    for (const [k, v] of Object.entries(value)) {
      properties[k] = convertToJsonSchemaType(v, `${path}.${k}`);
      required.push(k);
    }

    return {
      type: 'object',
      properties,
      required,
      additionalProperties: false
    };
  }

  // Примитивные типы
  return { type: typeof value };
}

// Функция для генерации общих типов
async function generateSharedTypes() {
  let sharedTypesContent = `/* Auto-generated shared types */

`;

  // Добавляем каждый известный тип
  for (const [typeName, properties] of Object.entries(knownTypes)) {
    sharedTypesContent += `export interface ${typeName} {\n`;
    for (const [propName, propType] of Object.entries(properties)) {
      sharedTypesContent += `  ${propName}: ${propType};\n`;
    }
    sharedTypesContent += `}\n\n`;
  }

  // Добавляем PostItem для архивных страниц
  sharedTypesContent += `export interface PostItem {
  title: string;
  url: string;
  id: number;
  excerpt: string;
  thumbnail: FeaturedImage | null;
  categories: CategoryItem[];
  date: {
    formatted: string;
    display: string;
  };
}\n\n`;

  // Добавляем SiteContext
  sharedTypesContent += `export interface SiteContext {
  title: string;
  url: string;
  theme_url: string;
  lang: string;
  description: string;
  charset: string;
}\n`;

  fs.writeFileSync('./types/shared-types.ts', sharedTypesContent);
}

// Функция для замены сгенерированных типов на более специфичные
function replaceKnownTypes(typescript) {
  let result = typescript;

  // Заменяем ссылки на известные типы
  for (const [path, type] of Object.entries(knownPropertyPaths)) {
    const typeWithoutArrayBrackets = type.replace('[]', '');

    // Пытаемся найти сгенерированный объект и заменить его на ссылку
    const objectPattern = new RegExp(`${path.split('.').pop()}: {[\\s\\S]*?};`, 'g');
    result = result.replace(objectPattern, `${path.split('.').pop()}: ${type};`);
  }

  // Удаляем [k: string]: unknown; из интерфейсов
  result = result.replace(/\[k: string\]: unknown;/g, '');

  return result;
}

generateTypeScriptInterfaces();