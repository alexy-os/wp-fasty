import * as t from '@babel/types';
import { processConditional } from './processor/conditional';
import { processLoop } from './processor/loop';
import traverse from '@babel/traverse';

/**
 * Converts JSX to Latte syntax
 */
export function transformJSXToLatte(jsxNode: any, imports: Record<string, any>, indent = 0): string {
  const indentation = ' '.repeat(indent);

  // If this is a fragment
  if (t.isJSXFragment(jsxNode)) {
    return jsxNode.children
      .filter((child: any) => !t.isJSXText(child) || child.value.trim() !== '')
      .map((child: any) => transformJSXToLatte(child, imports, indent))
      .join('\n');
  }

  // If this is a conditional operator
  if (t.isJSXExpressionContainer(jsxNode) &&
    t.isLogicalExpression(jsxNode.expression) &&
    jsxNode.expression.operator === '&&') {
    return processConditional(jsxNode, imports, indent, transformJSXToLatte);
  }

  // If this is a map (loop)
  if (t.isJSXExpressionContainer(jsxNode) &&
    t.isCallExpression(jsxNode.expression) &&
    t.isMemberExpression(jsxNode.expression.callee) &&
    t.isIdentifier(jsxNode.expression.callee.property) &&
    (jsxNode.expression.callee.property as any).name === 'map') {
    return processLoop(jsxNode, imports, indent, transformJSXToLatte);
  }

  // If this is an external component
  if (t.isJSXElement(jsxNode) && t.isJSXIdentifier(jsxNode.openingElement.name) &&
    !(jsxNode.openingElement.name as any).name.match(/^[a-z]/)) {
    const componentName = (jsxNode.openingElement.name as any).name;
    if (imports[componentName]) {
      // Recursively process the imported component
      return processImportedComponent(componentName, jsxNode, indent);
    }
  }

  // Regular HTML element
  if (t.isJSXElement(jsxNode)) {
    const tag = (jsxNode.openingElement.name as any).name;
    const attributes = processAttributes(jsxNode.openingElement.attributes);
    let result = '';

    // Opening tag
    result += `${indentation}<${tag}${attributes.length ? ' ' + attributes.join(' ') : ''}>`;

    // If there are child elements
    if (jsxNode.children.length > 0) {
      result += '\n';
      jsxNode.children.forEach((child: any) => {
        if (t.isJSXText(child)) {
          if (child.value.trim() !== '') {
            result += `${indentation}  ${child.value.trim()}\n`;
          }
        } else {
          result += transformJSXToLatte(child, imports, indent + 2) + '\n';
        }
      });
      result += `${indentation}</${tag}>`;
    } else {
      result += `</${tag}>`;
    }

    return result;
  }

  // JSX-expression (variable)
  if (t.isJSXExpressionContainer(jsxNode)) {
    if (t.isIdentifier(jsxNode.expression)) {
      return `${indentation}{$${(jsxNode.expression as any).name}}`;
    } else if (t.isMemberExpression(jsxNode.expression)) {
      return `${indentation}{$${memberExpressionToLatteVar(jsxNode.expression)}}`;
    }
  }

  // JSX-text
  if (t.isJSXText(jsxNode)) {
    const text = jsxNode.value.trim();
    return text ? `${indentation}${text}` : '';
  }

  return '';
}

/**
 * Processing JSX element attributes
 */
function processAttributes(attributes: any[]): string[] {
  return attributes.map(attr => {
    if (t.isJSXAttribute(attr)) {
      // Replace className with class
      let name = (attr.name as any).name;
      if (name === 'className') name = 'class';

      // If the attribute value is a string
      if (t.isStringLiteral(attr.value)) {
        return `${name}="${attr.value.value}"`;
      }

      // If the attribute value is an expression
      if (t.isJSXExpressionContainer(attr.value)) {
        if (t.isIdentifier(attr.value.expression)) {
          return `${name}="{$${(attr.value.expression as any).name}}"`;
        } else if (t.isMemberExpression(attr.value.expression)) {
          return `${name}="{$${memberExpressionToLatteVar(attr.value.expression)}}"`;
        }
      }

      // If the attribute has no value
      if (!attr.value) {
        return name;
      }
    }

    return '';
  }).filter(Boolean);
}

/**
 * Converts MemberExpression to a Latte variable string
 */
function memberExpressionToLatteVar(node: any): string {
  if (t.isIdentifier(node.object)) {
    return `${(node.object as any).name}.${(node.property as any).name}`;
  } else if (t.isMemberExpression(node.object)) {
    return `${memberExpressionToLatteVar(node.object)}.${(node.property as any).name}`;
  }
  return '';
}

/**
 * Processes the imported component
 */
function processImportedComponent(componentName: string, jsxNode: any, indent: number): string {
  // Get the parameters passed to the component
  const props: Record<string, string> = {};
  jsxNode.openingElement.attributes.forEach((attr: any) => {
    if (t.isJSXAttribute(attr)) {
      const name = (attr.name as any).name;
      if (t.isJSXExpressionContainer(attr.value)) {
        if (t.isIdentifier(attr.value.expression)) {
          props[name] = (attr.value.expression as any).name;
        } else if (t.isMemberExpression(attr.value.expression)) {
          props[name] = memberExpressionToLatteVar(attr.value.expression);
        }
      }
    }
  });

  // Here we need to recursively convert the imported component
  // This approach requires loading and analyzing all imported components

  // For example, we will include the component content as a separate block
  const indentation = ' '.repeat(indent);
  return `${indentation}{* Start of component ${componentName} *}\n${indentation}{include '${componentName.toLowerCase()}.latte', ${Object.entries(props).map(([k, v]) => `${k}: $${v}`).join(', ')}}\n${indentation}{* End of component ${componentName} *}`;
}

/**
 * Generates Latte template header with documentation
 */
function generateLatteHeader(params: string[]): string {
  let header = '{**\n';
  header += ' * Component Template\n';
  header += ' *\n';
  header += ' * Required context:\n';

  if (params.length > 0) {
    params.forEach(param => {
      header += ` * - ${param}: Component parameter\n`;
    });
  } else {
    header += ' * - No parameters required\n';
  }

  header += ' *}\n\n';
  return header;
}

/**
 * Converts the AST of a React component to a Latte template
 */
export async function transformReactToLatte(ast: any, imports: Record<string, any>) {
  let latteTemplate = '';
  let componentParams: string[] = [];

  // Extract the component parameters
  traverse(ast, {
    ArrowFunctionExpression(path) {
      // Find the main component (the first top-level arrow function)
      if (t.isVariableDeclarator(path.parent) &&
        t.isObjectPattern(path.node.params[0])) {

        const props = path.node.params[0].properties;
        componentParams = props.map((prop: any) =>
          t.isObjectProperty(prop) ? (prop.key as any).name : (prop.argument as any).name
        );
      }
    }
  });

  // Generate the template header with documentation
  latteTemplate += generateLatteHeader(componentParams);

  // Find the component body (its JSX)
  let rootJSX: any = null;
  traverse(ast, {
    ReturnStatement(path) {
      if (path.node.argument && t.isJSXElement(path.node.argument)) {
        rootJSX = path.node.argument;
      } else if (path.node.argument && t.isJSXFragment(path.node.argument)) {
        rootJSX = path.node.argument;
      }
    }
  });

  if (!rootJSX) {
    throw new Error('Root JSX element not found');
  }

  // Convert JSX to Latte
  const jsxToLatteResult = transformJSXToLatte(rootJSX, imports);
  latteTemplate += jsxToLatteResult;

  return latteTemplate;
}
