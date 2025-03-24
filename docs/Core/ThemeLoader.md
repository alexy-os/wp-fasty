# Загрузчик темы (ThemeLoader)

## Обзор

`ThemeLoader` - ключевой компонент мини-фреймворка Fasty, отвечающий за инициализацию и загрузку сервис-провайдеров темы.

## Основные возможности

- 🚀 Единая точка входа для фреймворка
- 🧩 Регистрация и загрузка сервис-провайдеров
- 🔗 Управление жизненным циклом приложения
- 🌐 Интеграция с WordPress

## Ключевые методы

### `init()`

Инициализация загрузчика темы:

```php
$themeLoader = ThemeLoader::init();
```

### `registerProvider(string $providerClass)`

Регистрация сервис-провайдера:

```php
$themeLoader->registerProvider(AssetsServiceProvider::class);
$themeLoader->registerProvider(ThemeServiceProvider::class);
```

### `boot()`

Загрузка всех зарегистрированных провайдеров:

```php
$themeLoader->boot();
```

## Принципы работы

### Регистрация провайдеров

```php
public function registerProvider(string $providerClass): self {
    if (!in_array($providerClass, $this->providers)) {
        $this->providers[] = $providerClass;
    }
    return $this;
}
```

### Загрузка провайдеров

```php
public function boot(): self {
    if ($this->booted) {
        return $this;
    }
    
    $container = $this->app->getContainer();
    
    // Регистрация провайдеров
    foreach ($this->providers as $providerClass) {
        $provider = new $providerClass($container);
        
        if (method_exists($provider, 'register')) {
            $provider->register();
        }
        
        $container->bind($providerClass, $provider);
    }
    
    // Инициализация провайдеров
    foreach ($this->providers as $providerClass) {
        $provider = $container->get($providerClass);
        
        if (method_exists($provider, 'boot')) {
            $provider->boot();
        }
    }
    
    $this->booted = true;
    
    // Уведомление о полной загрузке
    do_action('fasty_child_booted', $this);
    
    return $this;
}
```

## Пример полной инициализации

```php
// В bootstrap.php вашей темы
$theme = ThemeLoader::init()
    ->registerProvider(AssetsServiceProvider::class)
    ->registerProvider(ThemeServiceProvider::class)
    ->registerProvider(HooksProvider::class)
    ->boot();
```

## Расширенное использование

### Добавление собственных провайдеров

```php
// config/providers.php
return [
    \YourNamespace\CustomServiceProvider::class,
    \YourNamespace\AnotherServiceProvider::class
];

// В ThemeLoader
$providers = include FASTY_CHILD_PATH . '/fasty/config/providers.php';
foreach ($providers as $providerClass) {
    $themeLoader->registerProvider($providerClass);
}
```

## Хуки и события

- `fasty_child_booted`: Вызывается после полной загрузки фреймворка
- Позволяет подключать дополнительную логику после инициализации

## Преимущества

- 🔌 Модульная архитектура
- 🧩 Гибкая система расширения
- 🚀 Отложенная загрузка сервисов
- 🔒 Контроль над порядком инициализации

## Советы по использованию

1. Создавайте узкоспециализированные провайдеры
2. Используйте dependency injection
3. Минимизируйте логику в методах `register()` и `boot()`
4. Следите за порядком регистрации провайдеров

## Возможные проблемы

- Избегайте циклических зависимостей
- Обрабатывайте ошибки при создании провайдеров
- Следите за производительностью
- Используйте осторожно с глобальным состоянием

# ThemeLoader

<!-- @doc-source: ThemeLoader -->


## Methods

### __construct
<!-- @doc-source: ThemeLoader.__construct -->
Theme Loader - main entry point for the FastyChild framework
Manages service providers and bootstraps the application
/

namespace FastyChild\Core;

class ThemeLoader {
/**
Singleton instance

### init
<!-- @doc-source: ThemeLoader.init -->
Initialize theme loader

#### Returns



### registerProvider
<!-- @doc-source: ThemeLoader.registerProvider -->
Register a service provider

#### Parameters

- ``: string $providerClass Service provider class name
- ``: providerClass string

#### Returns



### boot
<!-- @doc-source: ThemeLoader.boot -->
Boot all registered service providers

#### Returns



### getApplication
<!-- @doc-source: ThemeLoader.getApplication -->
Get application instance

#### Returns



### getContainer
<!-- @doc-source: ThemeLoader.getContainer -->
Get application container

#### Returns



