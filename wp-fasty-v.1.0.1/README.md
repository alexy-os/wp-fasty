# WP Fasty Theme Framework

A modern WordPress theme framework built with OOP principles and dependency injection.

## Features

- Object-oriented architecture with dependency injection container
- Service-based modular architecture
- WordPress hooks management system
- Template system
- Asset management
- Performance optimizations

## Directory Structure

```
wp-fasty/
├── src/
│   ├── Core/          # Core framework classes
│   │   ├── Application.php
│   │   └── Container.php
│   ├── Hooks/         # WordPress hook handlers
│   │   ├── AssetsHooks.php
│   │   ├── HeaderHooks.php
│   │   ├── ThemeHooks.php
│   │   └── PageTemplateHooks.php
│   └── Templates/     # Template handlers
│       └── FullPageTemplate.php
├── assets/           # Theme assets
├── languages/        # Translation files
├── functions.php     # Theme bootstrap
└── style.css        # Theme stylesheet
```

## How to Use

### 1. Service Container

The framework uses a dependency injection container for managing services:

```php
namespace WPFasty\Core;

class Container {
    public function bind(string $abstract, $concrete): void {
        // Bind a service
    }
    
    public function singleton(string $abstract, $concrete): void {
        // Bind a singleton service
    }
    
    public function get(string $abstract) {
        // Resolve a service
    }
}
```

### 2. Hooks

Create hook handlers by extending the base hooks classes:

```php
namespace WPFasty\Hooks;

class CustomHooks {
    private $container;
    
    public function __construct($container) {
        $this->container = $container;
    }

    public function register(): void {
        // Register WordPress hooks
    }
}
```

### 3. Service Registration

Register services in the Application class:

```php
namespace WPFasty\Core;

class Application {
    private function registerServices(): void {
        $this->container->bind('service.name', function($container) {
            return new ServiceClass($container);
        });
        
        $this->container->singleton('singleton.service', function($container) {
            return new SingletonService($container);
        });
    }
}
```

### 4. Templates

Create custom template handlers:

```php
namespace WPFasty\Templates;

class CustomTemplate {
    public function render(): void {
        // Template rendering logic
    }
}
```

## Getting Started

1. Include the framework in your theme's `functions.php`:
```php
// Bootstrap the application
WPFasty\Core\Application::getInstance();
```

2. Create your service classes in the appropriate namespace
3. Register your services in the Application class
4. Use the container to manage dependencies

## Credits

Framework developed by WP Fasty Team