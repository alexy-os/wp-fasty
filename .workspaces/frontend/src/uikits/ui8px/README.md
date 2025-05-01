# UI8px Design System

A modern, accessible UI kit built on the 8px grid system with shadcn oklch color system.

See prompt for how this UI8 Kit was created [PROMPT](./prompt/cursor-claude-sonnet-3-7.md)

## Features

- **8px Grid System**: All spacing, sizing, and layout based on 8px units for consistency
- **Modern Color System**: Using oklch color space from shadcn for improved color perception
- **Dark Mode Support**: Built-in dark mode support with optimized contrast
- **Semantic Components**: Naming focused on meaning rather than appearance
- **Accessible**: Designed with accessibility in mind
- **Minimal Visual Effects**: Clean, distraction-free design language

## Components Structure

The UI kit follows the Atomic Design methodology:

```
ui8px/
├── core/
│   ├── ui/            # Atoms - basic building blocks
│   ├── components/    # Molecules - combinations of atoms
│   └── blocks/        # Organisms - complex UI sections
└── examples/          # Example implementations
```

### UI Components (Atoms)

- Avatar
- Badge
- Button
- Card
- Icon
- Input
- Link
- List
- Progress
- Typography

### Components (Molecules)

- ChatBubble
- ChatCard
- ChatInput
- QuickActions
- QuizOption

### Blocks (Organisms)

- ChatContainer
- FileUpload

## Usage

To use this UI kit in your React project:

```tsx
import { Button } from '@uikits/ui8px/core/ui/button';
import { Card } from '@uikits/ui8px/core/ui/card';
import { ChatContainer } from '@uikits/ui8px/core/blocks/chat-container';

// Example usage
function MyComponent() {
  return (
    <Card>
      <Button variant="primary">Click me</Button>
    </Card>
  );
}
```

## Design Principles

1. **Consistency**: All components follow the 8px grid system
2. **Simplicity**: Minimal visual effects, focused on usability
3. **Flexibility**: Customizable through variants and properties
4. **Accessibility**: Designed to meet WCAG standards
5. **Performance**: Lightweight components with minimal dependencies

## Customization

Components accept common props for customization:

- `variant`: Visual style variant
- `size`: Size variant
- `className`: For additional CSS classes

## Example

See the [Landing Page](./examples/landing-page-example.tsx) for a full implementation of a full page interface using the UI8px design system.