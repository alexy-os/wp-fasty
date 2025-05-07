/**
 * Checks the syntax of a React component for compliance with standards
 */
export function validateReactSyntax(code: string): string[] {
  const errors: string[] = [];

  // Check the correctness of .map()
  const mapRegex = /\.map\(\(([^,\)]+)(?::\s*([^,\)]+))?(?:,\s*([^)]+))?\)\s*=>/g;
  let match;

  while ((match = mapRegex.exec(code)) !== null) {
    const [fullMatch, paramType, paramName, indexParam] = match;

    // Check the format of the parameter: must be one of the allowed with the type "any"

    if (!paramType.includes(': any')) {
      //if (!paramType || paramType !== 'any') {
      errors.push(`The map parameter must have the type ": any" in: ${fullMatch.slice(0, 30)}...`);
    }

    // Allowed parameter names (post, category, item, etc.)
    /*const validParamNames = ['post', 'category', 'item', 'menu', 'product', 'page'];
    if (!validParamNames.includes(paramName)) {
      errors.push(`The map parameter name must be one of (${validParamNames.join(', ')}) in: ${fullMatch.slice(0, 30)}...`);
    }*/

    // Warning about the presence of an index
    if (indexParam) {
      errors.push(`It is not recommended to use an index in map, use key={${paramName}.id}: ${fullMatch.slice(0, 30)}...`);
    }
  }

  // Check the presence of key={*.id} in map
  /*const mapWithoutKeyRegex = /\.map\(\(([^,\)]+)[^\)]*\)\s*=>\s*<[^>]*(?!key=\{\1\.id\})[^>]*>/g;
  while ((match = mapWithoutKeyRegex.exec(code)) !== null) {
    const [fullMatch, paramName] = match;
    errors.push(`Missing key={${paramName}.id} in map: ${fullMatch.slice(0, 30)}...`);
  }*/

  // Check for redundant checks
  const redundantChecksRegex = /(\w+)\s+&&\s+\1\./g;
  while ((match = redundantChecksRegex.exec(code)) !== null) {
    errors.push(`Redundant check: use a simple variable check: ${match[0]}`);
  }

  // Check for a simple props structure
  if (!code.match(/const \w+\s*=\s*\(\{\s*[^{}]*\}\)\s*=>/)) {
    errors.push('The component must use destructuring of props: const Component = ({ prop1, prop2 }) => {...}');
  }

  return errors;
}
