# UI Headless Components

A collection of unstyled, accessible, and composable React components designed for maximum flexibility and reusability.

## Core Concepts

### Slot Component

The Slot component is the cornerstone of our headless UI system, enabling component composition and polymorphism without sacrificing type safety or accessibility.

#### Key Features

- **Component Merging**: Merges props and refs from multiple components
- **Polymorphic Behavior**: Allows changing the underlying HTML element while preserving props and functionality
- **Type Safety**: Full TypeScript support with proper type inference
- **Ref Forwarding**: Intelligent handling of multiple refs through composition

#### Structure

```typescript
/slot
├── Slot.tsx          // Main component implementation
├── Slot.css.ts       // Optional base styles
├── composeRefs.ts    // Utility for ref composition
└── index.ts         // Public API exports
```

### Usage Example

```tsx
import { Slot } from '@ui-factory/ui-headless/slot';

// Basic usage
<Slot>
  <button>Click me</button>
</Slot>

// Polymorphic transformation
<Slot asChild>
  <Link href="/home">Home</Link>
</Slot>
```

## Component Architecture

Our headless components follow these principles:

1. **Separation of Concerns**
   - Logic and behavior in headless components
   - Styling handled by consuming applications
   - Accessibility built-in by default

2. **Composability**
   - Components can be nested and combined
   - Props are merged intelligently
   - Refs are composed automatically

3. **Flexibility**
   - No opinion on styling solutions
   - Framework agnostic design
   - Easy integration with any design system

## Styling Recommendations

We recommend using `tailwind-variants` (tv) over `cva` + `cn` for several reasons:

### Why tv()?

```typescript
// Using tv()
const buttonStyles = tv({
  base: "inline-flex items-center justify-center",
  variants: {
    variant: {
      primary: "bg-primary text-white",
      secondary: "bg-secondary text-gray-900"
    },
    size: {
      sm: "text-sm px-2 py-1",
      lg: "text-lg px-4 py-2"
    }
  },
  defaultVariants: {
    variant: "primary",
    size: "sm"
  }
});

// Usage
<Button className={buttonStyles({ variant: "primary", size: "lg" })} />
```

#### Advantages over cva + cn:

1. **Simplified API**
   - Single function call instead of multiple utilities
   - More intuitive variant definition
   - Built-in class merging

2. **Better Performance**
   - Optimized class generation
   - Reduced runtime overhead
   - Smaller bundle size

3. **Enhanced Developer Experience**
   - Better TypeScript integration
   - Automatic variant type generation
   - Improved IDE support

4. **Advanced Features**
   - Compound variants
   - Base styles
   - Default variants
   - Dynamic variants

## Example Implementation

Here's how a Button component is implemented using our headless system:

```typescript
// Headless Button (packages/ui-headless/button)
export interface ButtonProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  asChild?: boolean;
  ignoreBaseStyle?: boolean;
}

const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ asChild, ignoreBaseStyle, ...props }, ref) => {
    const Comp = asChild ? Slot : "button";
    return <Comp ref={ref} {...props} />;
  }
);

// Styled Button (consuming application)
const StyledButton = React.forwardRef<HTMLButtonElement, StyledButtonProps>(
  ({ variant, size, className, ...props }, ref) => {
    const variantClasses = buttonStyles({ variant, size });
    return (
      <HeadlessButton
        ref={ref}
        className={twMerge(variantClasses, className)}
        {...props}
      />
    );
  }
);
```

## Best Practices

1. **Component Creation**
   - Start with headless components
   - Add styling layer separately
   - Maintain accessibility features

2. **Styling Integration**
   - Use `tv()` for variant management
   - Leverage `twMerge` for class conflicts
   - Keep styles modular and composable

3. **Type Safety**
   - Utilize TypeScript for all components
   - Maintain proper prop types
   - Document component interfaces

## Getting Started

```bash
# Install core package
# pending: npm install uiheadless

# Install styling dependencies
npm install tailwind-variants tailwind-merge
```

## Contributing

We welcome contributions! Please see our contributing guide for details.

## License

MIT © Factory/UI
