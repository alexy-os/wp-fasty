# RegexExtractorAdapter

A powerful and flexible class extraction module that uses regular expressions to analyze React components and extract Tailwind CSS classes, including support for Tailwind Variants.

## Overview

The RegexExtractorAdapter is designed to parse React components and extract CSS class information using regular expressions. It supports various contexts including JSX attributes, constant definitions, configuration objects, dynamic class assignments (using `clsx/cn`), template literals, and Tailwind Variants.

## Structure

```
regex/
├── extractors/
│   ├── className-extractor.ts    # Handles standard class name extraction
│   └── tv-extractor.ts          # Processes Tailwind Variants
├── utils/
│   ├── class-entry.ts           # Class entry creation utilities
│   └── element-type.ts          # Element type detection
├── index.ts                     # Main adapter implementation
├── patterns.ts                  # Regex pattern definitions
└── types.ts                     # Type definitions
```

## Features

- **Multiple Context Support**: Extracts classes from:
  - JSX className attributes
  - Constant definitions
  - Configuration objects
  - Dynamic class assignments (clsx/cn)
  - Template literals
  - Tailwind Variants (tv) definitions

- **Intelligent Element Type Detection**: Automatically determines the HTML element type based on context

- **Tailwind Variants Support**: Full support for tv() syntax including:
  - Base classes
  - Variant groups
  - Nested variants

## Usage

```typescript
import RegexExtractorAdapter from './adapters/regex';

const adapter = new RegexExtractorAdapter();

// Check if adapter supports the file
const isSupported = adapter.supportsComponent('Component.tsx');

// Extract classes from a component
const classEntries = await adapter.extractClasses('path/to/Component.tsx');
```

## Supported Patterns

The adapter recognizes the following class patterns:

```typescript
// JSX className
className="text-blue-500"

// Constant definition
const styles = { className: "bg-red-500" }

// Configuration object
config: { className: "p-4" }

// Dynamic classes with clsx/cn
className={clsx('flex', 'items-center')}

// Template literal
className={`mt-4 ${dynamicClass}`}

// Tailwind Variants
tv({
  base: "flex items-center",
  variants: {
    size: {
      sm: "text-sm",
      lg: "text-lg"
    }
  }
})
```

## Output Format

The adapter produces `EnhancedClassEntry` objects containing:
- Quark name (unique identifier)
- Semantic name (readable identifier)
- Original classes
- Component information
- Element type
- Variant information (for tv components)

## Error Handling

The adapter includes robust error handling for:
- File reading errors
- Invalid regex patterns
- Malformed component syntax
- Missing or invalid class definitions

## Contributing

When adding new patterns or modifying existing ones:
1. Update the patterns in `patterns.ts`
2. Add corresponding test cases
3. Ensure backward compatibility
4. Update documentation as needed