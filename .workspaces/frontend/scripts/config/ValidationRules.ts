import * as t from '@babel/types';

export interface ValidationRule {
  name: string;
  description: string;
  validate: (path: any, context?: any) => boolean;
  errorMessage: (path: any, context?: any) => string;
}

export const reactValidationRules: ValidationRule[] = [
  {
    name: 'map-param-type',
    description: 'Map callback parameter should have type annotation :any',
    validate: (path) => {
      if (!t.isCallExpression(path.node)) return true;

      if (
        t.isMemberExpression(path.node.callee) &&
        t.isIdentifier(path.node.callee.property) &&
        path.node.callee.property.name === 'map'
      ) {
        const arg = path.node.arguments[0];
        if (t.isArrowFunctionExpression(arg)) {
          const param = arg.params[0];
          if (t.isIdentifier(param)) {
            if (!param.typeAnnotation) {
              return false;
            } else if (
              t.isTSTypeAnnotation(param.typeAnnotation) &&
              !t.isTSAnyKeyword(param.typeAnnotation.typeAnnotation)
            ) {
              return false;
            }
          }
        }
      }
      return true;
    },
    errorMessage: (path) => {
      const param = path.node.arguments[0].params[0];
      return `The map parameter must have the type ": any" for parameter: ${param.name}`;
    }
  },
  {
    name: 'map-key-id',
    description: 'JSX elements in map must use key={item.id}',
    validate: (path) => {
      if (!t.isCallExpression(path.node)) return true;

      if (
        t.isMemberExpression(path.node.callee) &&
        t.isIdentifier(path.node.callee.property) &&
        path.node.callee.property.name === 'map'
      ) {
        const arg = path.node.arguments[0];
        if (t.isArrowFunctionExpression(arg)) {
          const param = arg.params[0];

          // Check JSX element in function body
          let jsxElement;
          if (t.isJSXElement(arg.body)) {
            jsxElement = arg.body;
          } else if (t.isBlockStatement(arg.body) &&
            arg.body.body.length === 1 &&
            t.isReturnStatement(arg.body.body[0]) &&
            t.isJSXElement(arg.body.body[0].argument)) {
            jsxElement = arg.body.body[0].argument;
          }

          if (jsxElement) {
            // Check key attribute
            const keyAttr = jsxElement.openingElement.attributes.find(attr =>
              t.isJSXAttribute(attr) && attr.name.name === 'key'
            );

            if (!keyAttr) return false;

            // Проверяем, что это JSXAttribute а не JSXSpreadAttribute
            if (!t.isJSXAttribute(keyAttr)) return false;

            // Check that key has format {item.id}
            if (keyAttr.value && t.isJSXExpressionContainer(keyAttr.value)) {
              const expr = keyAttr.value.expression;

              // key={index} is prohibited
              if (t.isIdentifier(expr) && expr.name === 'index') {
                return false;
              }

              // Only key={param.id} is allowed
              if (t.isMemberExpression(expr)) {
                const obj = expr.object;
                const prop = expr.property;

                if (t.isIdentifier(obj) && t.isIdentifier(prop)) {
                  const isParamObj = obj.name === (param as any).name;
                  const isIdProp = prop.name === 'id';

                  if (!isParamObj || !isIdProp) {
                    return false;
                  }
                } else {
                  return false;
                }
              } else {
                return false;
              }
            } else {
              return false;
            }
          }
        }
      }
      return true;
    },
    errorMessage: (path) => {
      if (!path?.node?.arguments?.[0]?.params?.[0]) {
        return 'Map requires key={item.id} for JSX elements';
      }
      const param = path.node.arguments[0].params[0];
      return `Map requires key={${param.name}.id} for JSX elements`;
    }
  },
  {
    name: 'index-in-map',
    description: 'Avoid using index parameter in map',
    validate: (path) => {
      if (!t.isCallExpression(path.node)) return true;

      if (
        t.isMemberExpression(path.node.callee) &&
        t.isIdentifier(path.node.callee.property) &&
        path.node.callee.property.name === 'map'
      ) {
        const arg = path.node.arguments[0];
        if (t.isArrowFunctionExpression(arg) && arg.params.length > 1) {
          return false;
        }
      }
      return true;
    },
    errorMessage: () => `It is not recommended to use an index in map, use key={item.id} instead`
  },
  {
    name: 'props-destructuring',
    description: 'Component must use props destructuring',
    validate: (path) => {
      // Check only root components
      if (path.parent && t.isVariableDeclarator(path.parent)) {
        const params = path.node.params;

        if (params.length !== 1 || !t.isObjectPattern(params[0])) {
          return false;
        }
      }
      return true;
    },
    errorMessage: () => 'The component must use destructuring of props: const Component = ({ prop1, prop2 }) => {...}'
  }
];
