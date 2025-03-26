# Абстрактный Сервис-провайдер (AbstractServiceProvider)

## Введение в концепцию сервис-провайдеров

Сервис-провайдеры - это ключевые строительные блоки Fasty. Они позволяют организовать код вашей темы в логические модули и избежать "спагетти-кода". Если вы когда-либо писали весь код в `functions.php`, вы оцените преимущества этого подхода.

## Что такое сервис-провайдер?

Сервис-провайдер - это класс, который отвечает за:
- Регистрацию сервисов в контейнере зависимостей
- Инициализацию компонентов темы
- Настройку хуков WordPress
- Подключение ресурсов (CSS, JS)

## Базовый пример

```php
use FastyChild\Core\AbstractServiceProvider;

class MyThemeProvider extends AbstractServiceProvider 
{
    public function register(): void 
    {
        // Регистрируем сервис как синглтон
        $this->singleton('theme.menu', function() {
            return new MenuService();
        });
        
        // Регистрируем обычный сервис
        $this->bind('theme.posts', function() {
            return new PostsService();
        });
    }
    
    public function boot(): void 
    {
        // Инициализация после регистрации всех сервисов
        $menuService = $this->get('theme.menu');
        $menuService->setupMenus();
    }
}
```

## Основные методы

### register()
Метод для регистрации сервисов в контейнере. Здесь вы определяете, как создавать различные сервисы вашей темы.

### boot()
Вызывается после регистрации всех сервис-провайдеров. Используйте его для инициализации, которая требует доступа к другим сервисам.

### provides()
Возвращает список сервисов, предоставляемых провайдером. Используется для отложенной загрузки.

## Полезные методы для регистрации

### singleton(string $abstract, $concrete)
Регистрирует сервис как синглтон - будет создан только один экземпляр.

```php
$this->singleton('theme.config', function() {
    return new ConfigService();
});
```

### bind(string $abstract, $concrete)
Регистрирует сервис, который создается заново при каждом запросе.

```php
$this->bind('theme.post', function() {
    return new PostService();
});
```

### lazy(string $abstract, \Closure $factory, bool $singleton = true)
Регистрирует сервис, который создается только при первом обращении.

```php
$this->lazy('theme.heavy_service', function() {
    return new HeavyService();
});
```

## Доступ к другим сервисам

Внутри провайдера вы можете получить доступ к другим сервисам:

```php
public function boot(): void
{
    $config = $this->get('theme.config');
    $menu = $this->make(MenuService::class);
}
```

## Лучшие практики

1. **Единая ответственность**: Каждый провайдер должен отвечать за один компонент темы
2. **Именование сервисов**: Используйте префиксы для группировки (theme.*, admin.*)
3. **Документация**: Описывайте предоставляемые сервисы в PHPDoc
4. **Типизация**: Используйте строгую типизацию для методов

## Продвинутое использование

### Отложенная загрузка

```php
protected array $provides = [
    'theme.heavy_service',
    'theme.another_service'
];
```

### Условная регистрация

```php
public function register(): void
{
    if (is_admin()) {
        $this->registerAdminServices();
    } else {
        $this->registerFrontendServices();
    }
}
```

## Интеграция с WordPress

```php
public function boot(): void
{
    add_action('init', function() {
        $this->get('theme.initializer')->initialize();
    });
    
    add_filter('the_content', function($content) {
        return $this->get('theme.content')->process($content);
    });
}
```

## Отладка

Провайдер включает `LoggerTrait`, который позволяет логировать действия:

```php
public function register(): void
{
    $this->debug('Регистрация сервисов темы...');
    // ...
}
```

## Заключение

Сервис-провайдеры - это мощный инструмент организации кода WordPress темы. Они помогают:
- Структурировать код
- Управлять зависимостями
- Облегчать тестирование
- Следовать принципам SOLID

# AbstractServiceProvider

<!-- @doc-source: AbstractServiceProvider -->


## Methods

### __construct
<!-- @doc-source: AbstractServiceProvider.__construct -->
Abstract base class for service providers
Provides common functionality and implements ServiceProvider interface
/

namespace FastyChild\Core;

use FastyChild\Core\Traits\LoggerTrait;
use FastyChild\Core\Traits\ContainerAwareTrait;

abstract class AbstractServiceProvider implements ServiceProvider
{
use LoggerTrait;
use ContainerAwareTrait;

/**
Services provided by this provider

#### Parameters

- ``: container Container

### boot
<!-- @doc-source: AbstractServiceProvider.boot -->
Register services in the container

#### Returns



### provides
<!-- @doc-source: AbstractServiceProvider.provides -->
Get the services provided by the provider
Used for deferred loading

#### Returns



### singleton
<!-- @doc-source: AbstractServiceProvider.singleton -->
Register a singleton binding in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $concrete Concrete implementation or factory
- ``: abstract string
- ``: concrete mixed

#### Returns



### bind
<!-- @doc-source: AbstractServiceProvider.bind -->
Register a binding in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $concrete Concrete implementation or factory
- ``: abstract string
- ``: concrete mixed

#### Returns



### lazy
<!-- @doc-source: AbstractServiceProvider.lazy -->
Register a lazy service in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: null \Closure $factory Factory function to create the service
- ``: bool $singleton Whether to treat as singleton
- ``: abstract string
- ``: factory \Closure
- ``: singleton bool

#### Returns



### get
<!-- @doc-source: AbstractServiceProvider.get -->
Get a service from the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### has
<!-- @doc-source: AbstractServiceProvider.has -->
Check if a service exists in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### make
<!-- @doc-source: AbstractServiceProvider.make -->
Make an instance of a class with automatic dependency injection

#### Parameters

- ``: string $concrete Class name
- ``: array $parameters Additional constructor parameters
- ``: concrete string
- ``: parameters array

#### Returns



### call
<!-- @doc-source: AbstractServiceProvider.call -->
Call a method with automatic dependency injection

#### Parameters

- ``: object |string $instance Object instance or class name for static methods
- ``: string $method Method name
- ``: array $parameters Additional method parameters
- ``: instance mixed
- ``: method string
- ``: parameters array

#### Returns



