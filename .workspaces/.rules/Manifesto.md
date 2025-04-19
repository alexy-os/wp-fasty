# Web Application Architecture Development Manifesto

## Core Principles

### 1. Separation of Presentation from Business Logic
- Templates must be free from direct dependencies on specific frameworks or CMSs
- Universal extension syntax in templates for seamless reusability
- Only constructs that are either universal or easily transformable are permitted

### 2. Semantic Markup and Accessibility
- HTML5 semantic tags always take priority over generic containers
- Minimized element nesting to improve readability and performance
- Adherence to W3C standards and WCAG accessibility guidelines

### 3. Unified CSS Naming and Organization
- Use of intuitively understandable class naming systems
- Compilation of styles into static files with separation between common and specific
- Utility-first approach with composition capabilities through compiler directives

### 4. Front Controller Pattern for Entry Points
- Each page type represented by a single entry point
- Controllers providing platform agnosticism
- Minimized code duplication in controllers through inheritance and composition

### 5. Unified Data Context
- All data passed to templates through a single consistent context object
- Control and filtering of data to minimize the volume of transmitted information
- Declarative definition of template dependencies on data

## Architectural Organization

### Directory Structure
```
views/
  ├── [template-name]/
  │   ├── context/    # Data sources and filters
  │   ├── cache/      # Compiled templates
  │   ├── parts/      # Template components
  │   ├── css/        # Styles (source and compiled)
  │   ├── config/     # Template configuration and metadata
  │   └── ...
  └── ...
```

### Controller-View-Model
- Controllers initialize the application and prepare the context
- Models are responsible for obtaining and transforming data
- Views are solely responsible for displaying the provided data

## Quality Principles

### Code Economy
- No redundant dependencies or lines of code
- Use only those resources that are genuinely needed
- Elimination of dead code at all levels (HTML, CSS, JS, server code)

### Performance and Security
- Optimization for PageSpeed and Core Web Vitals
- Preference for compilation over interpretation
- Use of modern approaches to isolation and optimization (WebComponents, WASM)

### SOLID and DRY Support
- Single Responsibility Principle for all components
- Dependency Inversion through the use of abstractions and interfaces
- Code reuse through composition and inheritance

### Extensibility and Adaptability
- Ability to add new functions without modifying existing code
- Flexibility in configuring all system components
- Documented extension points for all modules

## Technological Independence

This manifesto is not tied to specific technologies but defines an architectural approach applicable to any stack:
- WordPress, Laravel, Symphony, Express, Flask, Django
- React, Vue, Angular, Svelte
- Golang, Node.js, PHP, Python, Ruby

Following this manifesto ensures the creation of scalable, maintainable, and high-performance applications regardless of the technologies used.