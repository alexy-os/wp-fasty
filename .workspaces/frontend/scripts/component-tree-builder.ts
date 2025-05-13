// cd .workspaces/frontend && bun scripts/component-tree-builder.ts
import fs from 'fs';
import path from 'path';
import * as babel from '@babel/parser';
import traverse from '@babel/traverse';

const UI_KIT_PATH = './src/uikits/ui8px/core/tailwind';

interface ComponentExport {
  name: string;
  filePath: string;
}

function buildComponentTree(directoryPath: string): void {
  const components: ComponentExport[] = [];

  function scanDirectory(dir: string): void {
    const entries = fs.readdirSync(dir, { withFileTypes: true });

    for (const entry of entries) {
      const fullPath = path.join(dir, entry.name);

      if (entry.isDirectory()) {
        scanDirectory(fullPath);
      } else if (entry.isFile() && entry.name.endsWith('.tsx')) {
        const exports = extractExports(fullPath);
        if (exports.length > 0) {
          const relativePath = path.relative(UI_KIT_PATH, fullPath);
          console.log(`${relativePath}:`);
          console.log('exports:');
          exports.forEach(exp => console.log(`  ${exp}`));
          console.log('');

          exports.forEach(exportName => {
            components.push({
              name: exportName,
              filePath: relativePath
            });
          });
        }
      }
    }
  }

  scanDirectory(directoryPath);

  // Save the component tree to a JSON file
  fs.writeFileSync(
    path.join(UI_KIT_PATH, 'component-tree.json'),
    JSON.stringify(components, null, 2)
  );
}

function extractExports(filePath: string): string[] {
  const exports: string[] = [];
  const code = fs.readFileSync(filePath, 'utf-8');

  try {
    const ast = babel.parse(code, {
      sourceType: 'module',
      plugins: ['typescript', 'jsx']
    });

    traverse(ast, {
      ExportNamedDeclaration(path) {
        if (path.node.declaration) {
          if (path.node.declaration.type === 'VariableDeclaration') {
            path.node.declaration.declarations.forEach(declaration => {
              if (declaration.id.type === 'Identifier') {
                exports.push(declaration.id.name);
              }
            });
          } else if (path.node.declaration.type === 'FunctionDeclaration' ||
            path.node.declaration.type === 'ClassDeclaration' ||
            path.node.declaration.type === 'TSDeclareFunction' ||
            path.node.declaration.type === 'TSInterfaceDeclaration' ||
            path.node.declaration.type === 'TSTypeAliasDeclaration') {
            if (path.node.declaration.id) {
              exports.push(path.node.declaration.id.name);
            }
          }
        }

        // Handle named exports like: export { Button, Card }
        if (path.node.specifiers) {
          path.node.specifiers.forEach(specifier => {
            if (specifier.type === 'ExportSpecifier' && specifier.exported.type === 'Identifier') {
              exports.push(specifier.exported.name);
            }
          });
        }
      },
      ExportDefaultDeclaration() {
        exports.push('default');
      }
    });

    return exports;
  } catch (error) {
    console.error(`Error parsing ${filePath}:`, error);
    return [];
  }
}

// Run the script
buildComponentTree(UI_KIT_PATH); 