import * as fs from 'fs';
import * as path from 'path';
import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import * as t from '@babel/types';

/**
 * Находит и резолвит импорты компонентов
 */
export function resolveImports(ast: any, basePath: string): Record<string, any> {
  const imports: Record<string, any> = {};

  traverse(ast, {
    ImportDeclaration(path) {
      const source = path.node.source.value;

      // Обрабатываем только локальные импорты (не из node_modules)
      if (source.startsWith('./') || source.startsWith('../')) {
        path.node.specifiers.forEach((specifier) => {
          if (t.isImportSpecifier(specifier)) {
            const importName = specifier.imported.name;
            const localName = specifier.local.name;

            // Пытаемся найти компонент
            const componentPath = resolveComponentPath(source, basePath);
            if (componentPath) {
              imports[localName] = {
                path: componentPath,
                name: importName
              };
            }
          }
        });
      }
    }
  });

  return imports;
}

/**
 * Резолвит путь к компоненту
 */
function resolveComponentPath(importPath: string, basePath: string): string | null {
  // Добавляем расширение если нужно
  if (!importPath.endsWith('.tsx') && !importPath.endsWith('.jsx')) {
    importPath = `${importPath}.tsx`;
  }

  const fullPath = path.resolve(basePath, importPath);

  if (fs.existsSync(fullPath)) {
    return fullPath;
  }

  return null;
}
