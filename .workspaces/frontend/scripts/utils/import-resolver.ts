import * as fs from 'fs';
import * as path from 'path';
//import { parse } from '@babel/parser';
import traverse from '@babel/traverse';
import * as t from '@babel/types';

/**
 * Finds and resolves component imports
 */
export function resolveImports(ast: any, basePath: string): Record<string, any> {
  const imports: Record<string, any> = {};

  traverse(ast, {
    ImportDeclaration(path) {
      const source = path.node.source.value;

      // Process only local imports (not from node_modules)
      if (source.startsWith('./') || source.startsWith('../')) {
        path.node.specifiers.forEach((specifier: any) => {
          if (t.isImportSpecifier(specifier)) {
            // Simply use any type casting for now
            const importName = (specifier.imported as any).name;
            const localName = (specifier.local as any).name;

            // Try to find the component
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
 * Resolves the path to the component
 */
function resolveComponentPath(importPath: string, basePath: string): string | null {
  // Add the extension if needed
  if (!importPath.endsWith('.tsx') && !importPath.endsWith('.jsx')) {
    importPath = `${importPath}.tsx`;
  }

  const fullPath = path.resolve(basePath, importPath);

  if (fs.existsSync(fullPath)) {
    return fullPath;
  }

  return null;
}
