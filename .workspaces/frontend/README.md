# Frontend Component Library

## Overview

This directory contains a reusable component library built with Svelte 5 and Tailwind 4. The primary goal is to create a set of UI components that can be:

1. Viewed and tested through a Vite application
2. Used to generate clean semantic HTML templates for any framework
3. Exported as standalone CSS for use in any environment 
4. Shared across different template engines (PHP Latte, Twig, JSX, Vue, Handlebars)

## Architecture

The architecture follows a "semantic-first" approach where we leverage Tailwind's utility classes during development but output clean, semantic HTML with corresponding CSS variables.

### Key Features

- **Clean Semantic Output**: All components render with semantic class names (e.g., `button button-primary button-lg`) instead of Tailwind utility classes
- **Framework Agnostic**: Components can be used as reference for generating templates in any framework
- **CSS Variables**: All design tokens are exposed as CSS variables for easy theming
- **Progressive Enhancement**: Uses modern CSS features with appropriate fallbacks

## Technical Setup

### Tailwind 4 Integration

Tailwind 4 brings significant improvements that simplify our setup:

```typescript
// vite.config.ts
import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    svelte({
      compilerOptions: {
        css: "injected"
      }
    }),
    tailwindcss(),
  ],
  // ...
});
```

Key Tailwind 4 advantages:
- No PostCSS configuration required
- Built-in CSS nesting support
- Custom variants using `@custom-variant`
- Simplified theme configuration via `@theme`
- Improved color function support

### Svelte 5 Configuration

Svelte 5 introduces runes and a more modern component model:

```javascript
// svelte.config.js
import adapter from "@sveltejs/adapter-auto";
import { vitePreprocess } from "@sveltejs/vite-plugin-svelte";

const config = {
  preprocess: [vitePreprocess({})],
  kit: {
    adapter: adapter(),
  },
};

export default config;
```

Important Svelte 5 features:
- Enhanced reactivity model
- Better TypeScript integration
- Improved performance
- More flexible component composition

## Component Design Philosophy

Our components follow a "utility-to-semantic" conversion pattern:

1. Components use Tailwind utilities during development for rapid prototyping
2. The rendered HTML uses semantic class names (e.g., `button-primary`)
3. Styles are extracted and compiled into clean CSS with semantic selectors
4. The resulting HTML is framework-agnostic and follows W3C standards

### Example: Button Component

```svelte
<script>
  export let variant = "primary";
  export let size = "default";
  // ...

  // Convert variants to semantic class names
  $: classes = `button button-${variant} ${size !== "default" ? `button-${size}` : ""}`;
</script>

<button class={classes} {disabled} {type}>
  <slot></slot>
</button>

<style>
  /* Semantic styles defined with Tailwind utilities */
  .button-primary {
    @apply bg-primary text-primary-foreground hover:bg-primary/90;
  }
  /* ... */
</style>
```

## From Tailwind to W3C Semantic HTML

This library demonstrates a powerful approach to component development that combines the developer experience of Tailwind with the clean output of semantic HTML:

1. **Development**: Use Tailwind's utility classes for rapid development
2. **Extraction**: Extract styles into semantic CSS classes
3. **Compilation**: Compile into clean CSS with CSS variables
4. **Output**: Deliver semantic HTML that follows W3C standards

Benefits of this approach:
- **Clean markup**: Final HTML contains only semantic class names
- **Maintainability**: Easier to understand HTML structure
- **Accessibility**: Better for screen readers and assistive technologies
- **Performance**: Reduced HTML size in production
- **Portability**: Easy to use across different frameworks and environments

## Usage

### Development

```bash
# Start the Vite development server
bun run dev

# Generate semantic CSS
bun run semantic

# Extract component CSS
bun run extract-css
```

### Building for Production

```bash
# Build for production
bun run build

# Generate production CSS
bun run tailwind
```

## Integration Examples

This component library can be used with:
- WordPress themes via PHP includes
- Static site generators
- React/Vue/Angular applications
- Server-side rendering frameworks
- Headless CMS systems

The compiled CSS and extracted component templates provide a flexible foundation for any frontend project while maintaining clean, semantic markup.