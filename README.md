A high-performance WordPress theme built with modern architectural patterns and optimised delivery methods.

# WP FastY Theme

<div align="center">

> *Copy nothing - create your own.*

The world's fastest WordPress theme where every line of code is just your handwriting. Don't copy other people's themes anymore - create unique things yourself.

https://wpfasty.app-server.ru

> *FastY is a WordPress framework for those who think differently.*
</div>

## PageSpeed Insights 100%

<div align="center">
    <img src="https://raw.githubusercontent.com/alexy-os/wp-fasty/refs/heads/main/screenshot.png" alt="FastY Theme" width="600">
</div>

Take a walk to test the page speed: [PageSpeed Insights](https://pagespeed.web.dev/analysis/https-wpfasty-app-server-ru/z58kehvfbr?form_factor=mobile)

## Architectural Advantages

This theme implements a robust Dependency Injection (DI) and Closure-based architecture, offering significant advantages over traditional Full Site Editing (FSE) approaches:

### Clean Architecture vs FSE Blocks

Whilst FSE promotes a block-based paradigm that inherently couples view and model layers, our DI/Closure architecture maintains strict separation of concerns:

- **Dependency Management**: Proper inversion of control through container-based service resolution
- **Testability**: Isolated components with clear boundaries and dependencies
- **Maintainability**: Modular structure allowing for precise modifications without side effects

### Performance Considerations

Unlike FSE's React-based implementation, which:
- Creates additional JavaScript runtime overhead
- Increases DOM tree complexity through nested block structures
- Requires substantial client-side processing for block rendering

Our approach:
- Minimises DOM tree depth through optimised template structures
- Reduces JavaScript payload by leveraging native browser capabilities
- Implements efficient server-side rendering patterns

## Development Features

### Dual-Mode Operation

The theme supports two distinct operational modes:

**Production Mode (Default)**
- Optimised Tailwind CSS delivery
- Minimal runtime overhead
- Maximum performance optimisation

**Development Mode**
- Live Tailwind configuration
- Enhanced debugging capabilities
- Direct style modifications

### UI Framework Integration

Built with shadcn/ui compatibility in mind:
- Consistent component architecture
- Theme-wide design token system
- Dark mode support out of the box

## Performance Metrics

Achieves perfect Google PageSpeed scores across all metrics:
- Performance: 100/100
- Accessibility: 100/100
- Best Practices: 100/100

These scores are maintained across both Production and Development modes, ensuring optimal performance regardless of operational context.

## Technical Implementation

- Implements PSR-4 compliant autoloading
- Utilises singleton pattern for service management
- Features comprehensive dependency injection container
- Maintains WordPress coding standards whilst introducing modern PHP patterns

This architecture demonstrates that WordPress themes can maintain high performance and clean architecture without compromising on modern development practices or user experience.
