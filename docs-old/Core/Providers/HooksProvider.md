# Провайдер Hooks (HooksProvider)

## Обзор

`HooksProvider` управляет регистрацией и инициализацией хуков WordPress для темы в мини-фреймворке Fasty.

## Основные функции

### Регистрация сервисов хуков

```php
public function register(): void {
    // Регистрация базовых хуков темы
    $this->container->singleton('hooks.theme', function() {
        return new ThemeHooks($this->container);
    });
    
    // Регистрация хуков Storefront
    $this->container->singleton('hooks.storefront', function() {
        return new StorefrontHooks($this->container);
    });
    
    // Регистрация хуков WooCommerce (если активен)
    if (class_exists('WooCommerce')) {
        $this->container->singleton('hooks.woocommerce', function() {
            return new WooCommerceHooks($this->container);
        });
    }
}
```

### Инициализация хуков

```php
public function boot(): void {
    // Инициализация базовых хуков темы
    $this->container->get('hooks.theme')->register();
    
    // Инициализация хуков Storefront
    $this->container->get('hooks.storefront')->register();
    
    // Инициализация хуков WooCommerce (если активен)
    if (class_exists('WooCommerce') && $this->container->has('hooks.woocommerce')) {
        $this->container->get('hooks.woocommerce')->register();
    }
}
```

## Ключевые возможности

- 🔌 Централизованное управление WordPress хуками
- 🧩 Модульная система регистрации хуков
- 🚀 Условная загрузка хуков на основе активных плагинов

## Типы обрабатываемых хуков

1. **Базовые хуки темы** (`ThemeHooks`)
   - Общие настройки и функции темы
   - Базовые модификации WordPress

2. **Хуки Storefront** (`StorefrontHooks`)
   - Специфические настройки для шаблонов Storefront
   - Кастомизация витрины магазина

3. **Хуки WooCommerce** (`WooCommerceHooks`)
   - Настройки и модификации WooCommerce
   - Расширенная функциональность магазина

## Пример создания собственных хуков

```php
namespace FastyChild\Hooks;

class CustomHooks {
    private $container;

    public function __construct($container) {
        $this->container = $container;
    }

    public function register() {
        // Добавление собственных хуков
        add_filter('body_class', [$this, 'addCustomBodyClasses']);
        add_action('wp_head', [$this, 'addCustomMetaTags']);
    }

    public function addCustomBodyClasses($classes) {
        $classes[] = 'custom-theme-class';
        return $classes;
    }

    public function addCustomMetaTags() {
        echo '<meta name="custom-theme" content="fasty">';
    }
}
```

## Советы по использованию

1. Создавайте отдельные классы для групп хуков
2. Используйте dependency injection для гибкости
3. Группируйте связанные хуки в один класс

## Возможные проблемы

- Следите за производительностью при большом количестве хуков
- Избегайте конфликтов с хуками других плагинов
- Используйте приоритеты хуков для корректной работы

namespace FastyChild\Core\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Hooks\ThemeHooks;
use FastyChild\Hooks\StorefrontHooks;
use FastyChild\Hooks\WooCommerceHooks;

class HooksProvider implements ServiceProvider {
/**
Container instance

#### Parameters

- ``: container Container

### register
<!-- @doc-source: HooksProvider.register -->
Register hook services in the container

#### Returns



### boot
<!-- @doc-source: HooksProvider.boot -->
Boot hook services - register WordPress hooks

#### Returns



