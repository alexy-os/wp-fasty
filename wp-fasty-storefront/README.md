# WP Fasty Storefront Child Theme

A custom child theme for the Storefront theme, built with the FastyChild OOP framework.

## Features

- Object-oriented architecture with dependency injection
- Configuration-driven theme customization
- Service providers for modular functionality
- Storefront hooks and filters override system
- WooCommerce integration and customization
- Tailwind CSS support

## Directory Structure

```
wp-fasty-storefront/
├── assets/
│   ├── css/           # Compiled CSS files
│   ├── scss/          # SCSS source files
│   ├── js/            # JavaScript files
│   └── fonts/         # Font files
├── fasty/             # FastyChild framework
│   ├── Core/          # Core framework classes
│   ├── Hooks/         # WordPress hook handlers
│   ├── Services/      # Service classes
│   └── config/        # Configuration files
├── woocommerce/       # WooCommerce template overrides
├── template-parts/    # Theme template parts
├── languages/         # Translation files
├── functions.php      # Theme functions
├── style.css          # Theme stylesheet
└── screenshot.png     # Theme screenshot
```

## How to Use

### 1. Configuration

Modify the configuration files in `fasty/config/` to customize the theme:

- `assets.php` - Configure theme stylesheets and scripts
- `theme.php` - Configure theme features, widgets, etc.
- `storefront.php` - Configure Storefront-specific overrides
- `woocommerce.php` - Configure WooCommerce customizations

### 2. Hooks

Create custom hook handlers in `fasty/Hooks/` to override theme functionality:

```php
namespace FastyChild\Hooks;

class CustomHooks {
    public function register(): void {
        add_action('wp_footer', [$this, 'customFooterContent']);
    }
    
    public function customFooterContent(): void {
        echo '<div class="custom-footer">Custom Footer Content</div>';
    }
}
```

### 3. Services

Create custom services in `fasty/Services/` to encapsulate business logic:

```php
namespace FastyChild\Services;

class CustomService {
    public function doSomething(): void {
        // Implementation
    }
}
```

### 4. Service Providers

Register your custom services in a service provider:

```php
namespace FastyChild\Providers;

use FastyChild\Core\ServiceProvider;
use FastyChild\Core\Container;
use FastyChild\Services\CustomService;

class CustomServiceProvider implements ServiceProvider {
    private $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    public function register(): void {
        $this->container->singleton('custom', function() {
            return new CustomService();
        });
    }
    
    public function boot(): void {
        // Initialize service
    }
}
```

Then register your provider in `fasty/config/providers.php`.

### 5. CSS Compilation

The theme uses SCSS for styling. Compile the SCSS files with:

```bash
npm run scss:build
```

Or watch for changes during development:

```bash
npm run scss:watch
```

## Credits

- Built on the Storefront theme by WooCommerce
- Framework developed by Fasty Team 