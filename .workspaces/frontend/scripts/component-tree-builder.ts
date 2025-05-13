// cd .workspaces/frontend && bun scripts/component-tree-builder.ts
import fs from 'fs';
import path from 'path';
import * as babel from '@babel/parser';
import traverse from '@babel/traverse';
import { FunctionDeclaration, VariableDeclarator } from '@babel/types';

const UI_KIT_PATH = './src/uikits/ui8px/core/tailwind';

interface ComponentExport {
  name: string;
  filePath: string;
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
          console.log(`${relativePath}:`);
          console.log('exports:');
          exports.forEach(exp => console.log(`  ${exp}`));
          console.log('');

          exports.forEach(exportName => {
            const component: ComponentExport = {
              name: exportName,
              filePath: relativePath
            };

            // Add slot information if available
            if (slots[exportName]) {
              component.slot = slots[exportName];
            }

            // Add valid parents if available
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

  // Save the component tree to a JSON file
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

    // Extract component names
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

    // Extract slot information and parent components
    traverse(ast, {
      JSXAttribute(path) {
        // Look for data-slot attributes
        if (path.node.name.name === 'data-slot' &&
          path.node.value &&
          path.node.value.type === 'StringLiteral') {

          const slotValue = path.node.value.value;

          // Find the component function that contains this JSX
          let functionPath = path.findParent(p =>
            p.isFunctionDeclaration() ||
            p.isArrowFunctionExpression() ||
            p.isFunctionExpression()
          );

          if (functionPath) {
            // For function declarations, the name is directly available
            if (functionPath.isFunctionDeclaration() &&
              functionPath.node.type === 'FunctionDeclaration') {
              const funcNode = functionPath.node as FunctionDeclaration;
              if (funcNode.id && funcNode.id.type === 'Identifier') {
                const componentName = funcNode.id.name;
                slots[componentName] = slotValue;

                // Infer parent components from slot name
                // Example: "article-header" suggests Article as parent
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
            // For variable declarations with function expressions
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

// Run the script
buildComponentTree(UI_KIT_PATH); 