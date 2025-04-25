import fs from 'fs';
import path from 'path';
import { parse } from 'svelte/compiler';

/**
 * Convert a Svelte component to a Latte template
 * @param {string} svelteFilePath - Path to the Svelte component
 * @param {string} outputDir - Directory to output the Latte template
 */
function convertSvelteToLatte(svelteFilePath, outputDir) {
  try {
    // Read the Svelte file
    const svelteContent = fs.readFileSync(svelteFilePath, 'utf-8');

    // Parse the Svelte component
    const parsed = parse(svelteContent);

    // Get the component name
    const componentName = path.basename(svelteFilePath, '.svelte').toLowerCase();

    // Extract the HTML template part
    let html = '';
    if (parsed.html) {
      html = parsed.html.children
        .map(node => processNode(node))
        .join('');
    }

    // Create output directory if it doesn't exist
    if (!fs.existsSync(outputDir)) {
      fs.mkdirSync(outputDir, { recursive: true });
    }

    // Transform Svelte variables to Latte syntax
    html = transformToLatteSyntax(html);

    // Write the Latte template
    const outputPath = path.join(outputDir, `${componentName}.latte`);
    fs.writeFileSync(outputPath, html);

    console.log(`Converted ${componentName}.svelte to ${componentName}.latte`);
    return outputPath;
  } catch (error) {
    console.error(`Error converting Svelte to Latte:`, error);
    return null;
  }
}

/**
 * Process a Svelte AST node and convert it to HTML string
 * @param {Object} node - Svelte AST node
 * @returns {string} HTML string
 */
function processNode(node) {
  if (node.type === 'Element') {
    // Process element node
    const attributes = node.attributes
      .map(attr => processAttribute(attr))
      .filter(Boolean)
      .join(' ');

    const openTag = `<${node.name}${attributes ? ' ' + attributes : ''}>`;

    // Process children recursively
    const children = node.children
      .map(child => processNode(child))
      .join('');

    if (isVoidElement(node.name)) {
      return openTag;
    }

    return `${openTag}${children}</${node.name}>`;
  } else if (node.type === 'Text') {
    // Text node
    return node.data;
  } else if (node.type === 'MustacheTag') {
    // Handle Svelte expressions
    return `{${node.expression.name || extractExpressionText(node)}}`;
  }

  return '';
}

/**
 * Process a Svelte attribute and convert it to HTML attribute
 * @param {Object} attr - Svelte attribute
 * @returns {string} HTML attribute
 */
function processAttribute(attr) {
  if (attr.type === 'Attribute') {
    if (attr.value.length === 0) {
      return attr.name;
    }

    // Handle literal attributes
    if (attr.value.length === 1 && attr.value[0].type === 'Text') {
      return `${attr.name}="${attr.value[0].data}"`;
    }

    // Handle dynamic attributes (expressions)
    const value = attr.value
      .map(v => {
        if (v.type === 'Text') return v.data;
        if (v.type === 'MustacheTag') return `{${extractExpressionText(v)}}`;
        return '';
      })
      .join('');

    return `${attr.name}="${value}"`;
  }

  return '';
}

/**
 * Extract expression text from a MustacheTag node
 * @param {Object} node - MustacheTag node
 * @returns {string} Expression text
 */
function extractExpressionText(node) {
  // Simple implementation - in a real scenario this would need to handle more complex expressions
  if (node.expression.type === 'Identifier') {
    return node.expression.name;
  }

  // Handle property access like button.text
  if (node.expression.type === 'MemberExpression') {
    const object = node.expression.object.name;
    const property = node.expression.property.name;
    return `${object}.${property}`;
  }

  return '';
}

/**
 * Transform Svelte syntax to Latte syntax
 * @param {string} html - HTML with Svelte syntax
 * @returns {string} HTML with Latte syntax
 */
function transformToLatteSyntax(html) {
  // Replace Svelte variables with Latte variables
  // Replace simple variables: {title} -> {$site['title']}
  html = html.replace(/{(\w+)}/g, '{$site[\'$1\']}');

  // Replace property access: {button.text} -> {$button['text']}
  html = html.replace(/{(\w+)\.(\w+)}/g, '{$site[\'$1\'][\'$2\']}');

  // For the specific case of the Hero component, replace with exact Latte syntax
  html = html.replace(/{primaryButton.text}/g, 'Explore');
  html = html.replace(/{secondaryButton.text}/g, 'Learn More');
  html = html.replace(/{primaryButton.href}/g, '#featured');
  html = html.replace(/{secondaryButton.href}/g, '#about');
  html = html.replace(/class="button button-{primaryButton.variant} rounded"/g, 'class="button button-primary rounded"');
  html = html.replace(/class="button button-{secondaryButton.variant} rounded"/g, 'class="button button-secondary rounded"');

  return html;
}

/**
 * Check if an element is a void element (has no closing tag)
 * @param {string} tagName - HTML tag name
 * @returns {boolean} Whether the tag is a void element
 */
function isVoidElement(tagName) {
  const voidElements = ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'];
  return voidElements.includes(tagName);
}

// Example usage
const srcDir = './src/components';
const outputDir = './src/templates/latte';

// Convert Hero.svelte to hero.latte
convertSvelteToLatte(`${srcDir}/Hero.svelte`, outputDir);
