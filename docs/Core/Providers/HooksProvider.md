# HooksProvider

`HooksProvider` - это провайдер, отвечающий за регистрацию и управление хуками WordPress в Fasty. Он обеспечивает централизованный способ работы с действиями и фильтрами WordPress.

## Назначение

- Централизованная регистрация хуков
- Управление приоритетами выполнения
- Группировка связанных хуков
- Условная регистрация
- Отложенная загрузка

## Методы

### register()

```php
public function register(): void
```

Регистрирует хуки в контейнере.

#### Пример использования
```php
class HooksProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Регистрация хуков
        $this->container->singleton(ThemeHooks::class);
        $this->container->singleton(AdminHooks::class);
        $this->container->singleton(CustomizerHooks::class);
        
        // Условная регистрация
        if (class_exists('WooCommerce')) {
            $this->container->singleton(WooCommerceHooks::class);
        }
    }
}
```

### boot()

```php
public function boot(): void
```

Инициализирует и загружает зарегистрированные хуки.

#### Пример использования
```php
class HooksProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        // Загрузка основных хуков
        $this->container->get(ThemeHooks::class)->register();
        
        // Загрузка административных хуков
        if (is_admin()) {
            $this->container->get(AdminHooks::class)->register();
            $this->container->get(CustomizerHooks::class)->register();
        }
        
        // Загрузка хуков WooCommerce
        if (class_exists('WooCommerce')) {
            $this->container->get(WooCommerceHooks::class)->register();
        }
    }
}
```

## Примеры использования

### 1. Базовая регистрация хуков

```php
class BasicHooksProvider extends HooksProvider
{
    public function register(): void
    {
        // Регистрация базовых хуков
        $this->container->singleton(SetupHooks::class);
        $this->container->singleton(ContentHooks::class);
        $this->container->singleton(SidebarHooks::class);
        $this->container->singleton(MenuHooks::class);
    }
    
    public function boot(): void
    {
        // Загрузка хуков в правильном порядке
        $this->container->get(SetupHooks::class)->register();
        $this->container->get(ContentHooks::class)->register();
        $this->container->get(SidebarHooks::class)->register();
        $this->container->get(MenuHooks::class)->register();
    }
}
```

### 2. Условная регистрация хуков

```php
class ConditionalHooksProvider extends HooksProvider
{
    public function register(): void
    {
        // Базовые хуки
        $this->container->singleton(CoreHooks::class);
        
        // Административные хуки
        if (is_admin()) {
            $this->container->singleton(AdminHooks::class);
            $this->container->singleton(CustomizerHooks::class);
        }
        
        // Хуки для плагинов
        if (class_exists('WooCommerce')) {
            $this->container->singleton(WooCommerceHooks::class);
        }
        
        if (class_exists('ACF')) {
            $this->container->singleton(ACFHooks::class);
        }
    }
    
    public function boot(): void
    {
        // Загрузка базовых хуков
        $this->container->get(CoreHooks::class)->register();
        
        // Загрузка административных хуков
        if (is_admin()) {
            $this->container->get(AdminHooks::class)->register();
            $this->container->get(CustomizerHooks::class)->register();
        }
        
        // Загрузка хуков плагинов
        if (class_exists('WooCommerce')) {
            $this->container->get(WooCommerceHooks::class)->register();
        }
        
        if (class_exists('ACF')) {
            $this->container->get(ACFHooks::class)->register();
        }
    }
}
```

### 3. Отложенная загрузка хуков

```php
class DeferredHooksProvider extends HooksProvider
{
    protected array $deferred = [
        SearchHooks::class,
        CommentHooks::class,
        ArchiveHooks::class
    ];
    
    public function register(): void
    {
        // Регистрация отложенных хуков
        foreach ($this->deferred as $hookClass) {
            $this->container->singleton($hookClass);
        }
    }
    
    public function boot(): void
    {
        // Загрузка хуков только при необходимости
        if (is_search()) {
            $this->container->get(SearchHooks::class)->register();
        }
        
        if (is_singular() && comments_open()) {
            $this->container->get(CommentHooks::class)->register();
        }
        
        if (is_archive()) {
            $this->container->get(ArchiveHooks::class)->register();
        }
    }
}
```

## Лучшие практики

### 1. Группировка хуков

```php
class ModularHooksProvider extends HooksProvider
{
    public function register(): void
    {
        $this->registerCoreHooks();
        $this->registerAdminHooks();
        $this->registerFrontendHooks();
        $this->registerPluginHooks();
    }
    
    private function registerCoreHooks(): void
    {
        $this->container->singleton(SetupHooks::class);
        $this->container->singleton(ContentHooks::class);
    }
    
    private function registerAdminHooks(): void
    {
        if (is_admin()) {
            $this->container->singleton(AdminHooks::class);
            $this->container->singleton(CustomizerHooks::class);
        }
    }
    
    private function registerFrontendHooks(): void
    {
        $this->container->singleton(MenuHooks::class);
        $this->container->singleton(SidebarHooks::class);
        $this->container->singleton(WidgetHooks::class);
    }
    
    private function registerPluginHooks(): void
    {
        if (class_exists('WooCommerce')) {
            $this->container->singleton(WooCommerceHooks::class);
        }
    }
}
```

### 2. Приоритеты хуков

```php
class PrioritizedHooksProvider extends HooksProvider
{
    private array $priorities = [
        SetupHooks::class => 5,
        ContentHooks::class => 10,
        SidebarHooks::class => 15,
        MenuHooks::class => 20
    ];
    
    public function boot(): void
    {
        // Сортировка хуков по приоритету
        $hooks = $this->getSortedHooks();
        
        // Загрузка хуков в порядке приоритета
        foreach ($hooks as $hookClass) {
            $this->container->get($hookClass)->register();
        }
    }
    
    private function getSortedHooks(): array
    {
        $hooks = array_keys($this->priorities);
        usort($hooks, function ($a, $b) {
            return $this->priorities[$a] - $this->priorities[$b];
        });
        return $hooks;
    }
}
```

### 3. Обработка ошибок

```php
class SafeHooksProvider extends HooksProvider
{
    public function boot(): void
    {
        foreach ($this->container->tagged('hooks') as $hook) {
            try {
                // Проверка метода
                if (!method_exists($hook, 'register')) {
                    throw new RuntimeException(
                        "Класс " . get_class($hook) . " не реализует метод register()"
                    );
                }
                
                // Регистрация хука
                $hook->register();
            } catch (Exception $e) {
                // Логирование ошибки
                error_log("Ошибка регистрации хука: " . $e->getMessage());
                
                // Уведомление администратора
                if (is_admin()) {
                    add_action('admin_notices', function () use ($e) {
                        echo '<div class="error"><p>Ошибка регистрации хука: ' . 
                             esc_html($e->getMessage()) . '</p></div>';
                    });
                }
            }
        }
    }
}
```

## Заключение

`HooksProvider` предоставляет:
- Централизованное управление хуками WordPress
- Гибкую систему регистрации
- Поддержку условной загрузки
- Управление приоритетами
- Группировку связанных хуков
- Безопасную обработку ошибок

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Core\Hooks\HooksManager;

/**
Core hooks provider without theme-specific dependencies

## Methods

### register
<!-- @doc-source: HooksProvider.register -->
Core Hooks Provider
Base provider for WordPress hooks management
/

namespace FastyChild\Core\Providers;

use FastyChild\Core\AbstractServiceProvider;
use FastyChild\Core\Hooks\HooksManager;

/**
Core hooks provider without theme-specific dependencies
/
class HooksProvider extends AbstractServiceProvider
{
/**
Register hooks manager service in the container

#### Returns



### boot
<!-- @doc-source: HooksProvider.boot -->
Boot hooks manager
This only sets up the hooks manager itself, not specific hooks
Specific hooks will be added by theme providers

#### Returns



