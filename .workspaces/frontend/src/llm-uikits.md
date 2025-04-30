# LLM UI Kit Generation Guidelines

## Overview

This guide outlines the process of converting raw HTML with Tailwind utility classes into a structured UI kit with semantic components following the Atomic Design methodology.

## Directory Structure

```
src/
└── uikits/
    └── [kit-name]/
        └── core/
            ├── ui/          # Atoms - basic building blocks (buttons, inputs, badges)
            ├── components/  # Molecules - combinations of atoms (cards, navbars, tabs)
            └── blocks/      # Organisms - complex UI sections (heroes, feature sections, testimonials)
```

## Component Creation Process

1. **Analysis Phase**
   - Identify repeating patterns in the provided HTML
   - Categorize elements into atoms, molecules, and organisms
   - Extract common styling patterns into component variants

2. **Interface Definition**
   - Create `interface.ts` files with TypeScript types
   - Define variants, sizes, and other component properties
   - Map Tailwind utility classes to semantic variants

3. **CSS Generation**
   - Create component CSS files with semantic class names
   - Use `@apply` to map semantic classes to utility classes
   - Maintain CSS files in `src/assets/css/[kit-name]/`

4. **Component Implementation**
   - Create React components with props based on interfaces
   - Use semantic class naming in component templates
   - Follow the pattern: `className={componentName} ${componentName}-${variant}`

## Analysis Guidelines

### Atoms (UI)
- **Characteristics**: Single-purpose, non-divisible UI elements
- **Examples**: Buttons, inputs, texts, badges, icons
- **Variants**: Usually have multiple visual variants (primary, secondary, etc.)

### Molecules (Components)
- **Characteristics**: Combinations of atoms forming a functional unit
- **Examples**: Cards, form groups, navigation items, tab panels
- **Properties**: Often have multiple states and accept complex props

### Organisms (Blocks)
- **Characteristics**: Complex, complete UI sections that fulfill a specific purpose
- **Examples**: Hero sections, feature grids, pricing tables, testimonial carousels
- **Structure**: Typically composed of multiple components with their own layout

## Implementation Process

1. For each identified component:
   - Create interface file with all variants
   - Create component implementation file
   - Create CSS file with semantic class names

2. For all components:
   - Ensure consistent naming conventions
   - Use the same pattern for class generation
   - Follow semantic naming principles

## Working with User Examples

When a user submits HTML with Tailwind classes:

1. Ask for the UI kit name if not provided
2. Analyze the HTML to identify component hierarchy
3. Extract UI patterns into atomic components
4. Create interfaces for each component
5. Generate semantic CSS equivalents
6. Implement React components using the interfaces

## Example Workflow

For a submitted HTML snippet:

```html
<div class="rounded-lg bg-white p-6 shadow-md">
  <h3 class="text-xl font-bold text-gray-800">Card Title</h3>
  <p class="mt-2 text-gray-600">Card description goes here</p>
  <button class="mt-4 rounded bg-blue-500 px-4 py-2 text-white hover:bg-blue-600">
    Click me
  </button>
</div>
```

The analysis would identify:
- A Button atom (with primary variant)
- A Card component (with default variant)

The implementation would create:
- `ui/button/interface.ts` with button variants
- `ui/button/index.tsx` with the Button component
- `components/card/interface.ts` with card variants
- `components/card/index.tsx` with the Card component
- Corresponding CSS files with semantic classes

## Best Practices

1. **Keep components focused**: Each component should do one thing well
2. **Maintain variant consistency**: Use the same variant names across components
3. **Follow semantic naming**: Component names should reflect their purpose
4. **Use TypeScript interfaces**: Provide good type definitions for components
5. **Document components**: Include prop documentation in interface files
6. **Consider accessibility**: Ensure components follow accessibility best practices

## Integration with Existing System

The system is designed to work with:
- Tailwind CSS for utility classes
- React for component implementation
- TypeScript for type definitions
- Semantic CSS class generation for maintainability

## Error Handling

If a user provides incomplete information:
1. Ask for clarification on component purpose
2. Request the UI kit name if not provided
3. Seek input on desired variants if unclear 