// Function to convert dot notation to square brackets
function convertDotToArrayNotation(content: string): string {
  // New regular expression that correctly handles cases with variables in Latte
  const regex = /(\$[a-zA-Z0-9_]+)(\.[a-zA-Z0-9_]+)+/g;

  // Recursive function to process all occurrences
  function processMatches(text: string): string {
    const matches = [...text.matchAll(regex)];

    if (matches.length === 0) {
      return text;
    }

    let result = text;

    // Process each occurrence, starting from the last, to avoid breaking indices
    for (let i = matches.length - 1; i >= 0; i--) {
      const match = matches[i];
      const fullMatch = match[0];
      const startIndex = match.index!;
      const endIndex = startIndex + fullMatch.length;

      // Split into variable and paths
      const parts = fullMatch.split('.');
      const varName = parts[0];
      const props = parts.slice(1);

      // Build new expression with square brackets
      let newExpression = varName;
      for (const prop of props) {
        newExpression += `['${prop}']`;
      }

      // Replace in original text
      result = result.substring(0, startIndex) + newExpression + result.substring(endIndex);
    }

    // Recursively process the result, as new occurrences may appear
    return processMatches(result);
  }

  return processMatches(content);
}

export { convertDotToArrayNotation };
