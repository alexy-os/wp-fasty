// cd .workspaces/frontend && bun scripts/component-tree-builder.ts
import fs from 'fs';
import path from 'path';
import * as babel from '@babel/parser';
import traverse from '@babel/traverse';
import { FunctionDeclaration, VariableDeclarator } from '@babel/types';

const UI_KIT_PATH = './src/uikits/ui8px/core/tailwind';
const IMPORT_BASE_PATH = '@uikits/ui8px/core/tailwind';

interface ComponentExport {
  name: string;
  filePath: string;
  importPath: string;
  slot?: string;
  validParents?: string[];
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
        const { exports, slots, parents } = extractComponentInfo(fullPath);
        if (exports.length > 0) {
          const relativePath = path.relative(UI_KIT_PATH, fullPath);
          const normalizedFilePath = relativePath.replace(/\\/g, '/');
          const importPathWithoutExt = normalizedFilePath.replace(/\.tsx$/, '');
          const fullImportPath = `${IMPORT_BASE_PATH}/${importPathWithoutExt}`;

          console.log(`${normalizedFilePath}:`);
          console.log('exports:');
          exports.forEach(exp => console.log(`  ${exp}`));
          console.log('import path:');
          console.log(`  ${fullImportPath}`);
          console.log('');

          exports.forEach(exportName => {
            const component: ComponentExport = {
              name: exportName,
              filePath: normalizedFilePath,
              importPath: fullImportPath
            };

            if (slots[exportName]) {
              component.slot = slots[exportName];
            }

            if (parents[exportName] && parents[exportName].length > 0) {
              component.validParents = parents[exportName];
            }

            components.push(component);
          });
        }
      }
    }
  }

  scanDirectory(directoryPath);

  fs.writeFileSync(
    path.join(UI_KIT_PATH, 'component-tree.json'),
    JSON.stringify(components, null, 2)
  );
}

function extractComponentInfo(filePath: string): {
  exports: string[];
  slots: Record<string, string>;
  parents: Record<string, string[]>;
} {
  const exports: string[] = [];
  const slots: Record<string, string> = {};
  const parents: Record<string, string[]> = {};
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

    traverse(ast, {
      JSXAttribute(path) {
        if (path.node.name.name === 'data-slot' &&
          path.node.value &&
          path.node.value.type === 'StringLiteral') {

          const slotValue = path.node.value.value;

          let functionPath = path.findParent(p =>
            p.isFunctionDeclaration() ||
            p.isArrowFunctionExpression() ||
            p.isFunctionExpression()
          );

          if (functionPath) {
            if (functionPath.isFunctionDeclaration() &&
              functionPath.node.type === 'FunctionDeclaration') {
              const funcNode = functionPath.node as FunctionDeclaration;
              if (funcNode.id && funcNode.id.type === 'Identifier') {
                const componentName = funcNode.id.name;
                slots[componentName] = slotValue;

                const parentMatch = slotValue.match(/^([a-z]+)-/);
                if (parentMatch && parentMatch[1]) {
                  const possibleParent = parentMatch[1].charAt(0).toUpperCase() + parentMatch[1].slice(1);
                  if (!parents[componentName]) {
                    parents[componentName] = [];
                  }
                  parents[componentName].push(possibleParent);
                }
              }
            }
            else {
              const variableDeclarator = functionPath.findParent(p => p.isVariableDeclarator());
              if (variableDeclarator) {
                const varNode = variableDeclarator.node as VariableDeclarator;
                if (varNode.id && varNode.id.type === 'Identifier') {
                  const componentName = varNode.id.name;
                  slots[componentName] = slotValue;

                  const parentMatch = slotValue.match(/^([a-z]+)-/);
                  if (parentMatch && parentMatch[1]) {
                    const possibleParent = parentMatch[1].charAt(0).toUpperCase() + parentMatch[1].slice(1);
                    if (!parents[componentName]) {
                      parents[componentName] = [];
                    }
                    parents[componentName].push(possibleParent);
                  }
                }
              }
            }
          }
        }
      }
    });

    return { exports, slots, parents };
  } catch (error) {
    console.error(`Error parsing ${filePath}:`, error);
    return { exports: [], slots: {}, parents: {} };
  }
}

buildComponentTree(UI_KIT_PATH); 