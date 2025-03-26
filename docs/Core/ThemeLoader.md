# Загрузчик темы (ThemeLoader)

## Введение

`ThemeLoader` - это класс, отвечающий за инициализацию и загрузку дочерней темы WordPress. Он управляет жизненным циклом темы, регистрацией сервис-провайдеров и их загрузкой.

## Основные задачи

- Инициализация фреймворка
- Управление сервис-провайдерами
- Отложенная загрузка компонентов
- Обработка ошибок

## Базовое использование

### Инициализация темы

```php
use FastyChild\Core\ThemeLoader;

// Получение экземпляра загрузчика
$loader = ThemeLoader::init();

// Регистрация провайдеров
$loader->registerProvider(ThemeServiceProvider::class);
$loader->registerProvider(AssetsServiceProvider::class);

// Загрузка темы
$loader->boot();
```

### Регистрация нескольких провайдеров

```php
$loader->registerProviders([
    ThemeServiceProvider::class,
    MenuServiceProvider::class,
    CustomizerServiceProvider::class
]);
```

## Отложенная загрузка

### Регистрация отложенного провайдера

```php
// Провайдер загрузится только когда понадобится
$loader->registerProvider(
    HeavyFeatureProvider::class, 
    true // deferred = true
);
```

### Как работает отложенная загрузка

1. Провайдер регистрируется как отложенный
2. Указывает, какие сервисы он предоставляет
3. Загружается только при первом запросе его сервиса

```php
class HeavyFeatureProvider extends ServiceProvider
{
    public function provides(): array
    {
        return [
            'heavy.feature',
            'heavy.another_feature'
        ];
    }
    
    public function register(): void
    {
        $this->bind('heavy.feature', function() {
            return new HeavyFeature();
        });
    }
}
```

## Жизненный цикл загрузки

1. **Инициализация**
```php
$loader = ThemeLoader::init();
```

2. **Регистрация провайдеров**
```php
$loader->registerProviders($providers);
```

3. **Загрузка**
```php
$loader->boot();
```

4. **Выполнение**
```php
// Все провайдеры загружены
do_action(Constants::HOOK_FASTY_FRAMEWORK_LOADED);
```

## Обработка ошибок

### Проверка провайдера

```php
try {
    $loader->registerProvider(CustomProvider::class);
} catch (ContainerException $e) {
    // Ошибка регистрации провайдера
    error_log($e->getMessage());
}
```

### Безопасная загрузка

```php
try {
    $loader->boot();
} catch (\Throwable $e) {
    // Ошибка загрузки темы
    add_action('admin_notices', function() use ($e) {
        echo '<div class="error"><p>';
        echo 'Ошибка загрузки темы: ' . esc_html($e->getMessage());
        echo '</p></div>';
    });
}
```

## Продвинутые возможности

### Условная регистрация

```php
if (is_admin()) {
    $loader->registerProvider(AdminPanelProvider::class);
} else {
    $loader->registerProvider(FrontendProvider::class);
}
```

### Доступ к приложению

```php
$app = $loader->getApplication();
$container = $loader->getContainer();
```

### Проверка состояния

```php
if ($loader->isBooted()) {
    // Фреймворк загружен
}

// Получение списка провайдеров
$providers = $loader->getProviders();
$deferred = $loader->getDeferredProviders();
```

## Интеграция с WordPress

### Хуки WordPress

```php
add_action('after_setup_theme', function() {
    $loader = ThemeLoader::init();
    
    // Регистрация провайдеров
    $loader->registerProviders([
        // ...
    ]);
    
    // Загрузка темы
    $loader->boot();
});
```

### Кастомные хуки

```php
add_action(Constants::HOOK_FASTY_FRAMEWORK_LOADED, function($loader) {
    // Фреймворк полностью загружен
    do_action('theme_custom_init');
});
```

## Лучшие практики

1. **Группировка провайдеров**
```php
// Базовые провайдеры
$loader->registerProviders($baseProviders);

// Отложенные провайдеры
$loader->registerProviders($optionalProviders, true);
```

2. **Порядок загрузки**
```php
// Сначала базовые сервисы
$loader->registerProvider(CoreServiceProvider::class);

// Затем зависимые
$loader->registerProvider(DependentServiceProvider::class);
```

3. **Обработка ошибок**
```php
try {
    $loader->boot();
} catch (ContainerException $e) {
    // Ошибка контейнера
} catch (NotFoundException $e) {
    // Сервис не найден
} catch (\Throwable $e) {
    // Другие ошибки
}
```

## Отладка

### Режим разработки

```php
if (defined('WP_DEBUG') && WP_DEBUG) {
    // Отключаем отложенную загрузку
    $loader->registerProviders($allProviders, false);
}
```

### Логирование

```php
add_action(Constants::HOOK_FASTY_FRAMEWORK_LOADED, function() {
    error_log('Fasty Framework loaded successfully');
});
```

## Заключение

`ThemeLoader` - это мощный инструмент для:
- Управления загрузкой темы
- Организации модульной архитектуры
- Оптимизации производительности
- Обеспечения надежности работы

# ThemeLoader

<!-- @doc-source: ThemeLoader -->


## Methods

### __construct
<!-- @doc-source: ThemeLoader.__construct -->
Theme Loader - main entry point for the FastyChild framework
Manages service providers and bootstraps the application
/

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\NotFoundException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Hooks\Constants;

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
- ``: bool $deferred Whether to defer loading until needed
- ``: providerClass string
- ``: deferred bool

#### Returns



### getProvidedServices
<!-- @doc-source: ThemeLoader.getProvidedServices -->
Get services provided by a deferred service provider

#### Parameters

- ``: string $providerClass Provider class name
- ``: providerClass string

#### Returns



### registerProviders
<!-- @doc-source: ThemeLoader.registerProviders -->
Register multiple service providers

#### Parameters

- ``: array $providers Array of service provider class names
- ``: bool $deferred Whether to defer loading until needed
- ``: providers array
- ``: deferred bool

#### Returns



### loadDeferredProvider
<!-- @doc-source: ThemeLoader.loadDeferredProvider -->
Load a deferred provider

#### Parameters

- ``: string $service Service name
- ``: service string

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



### getProviders
<!-- @doc-source: ThemeLoader.getProviders -->
Get all registered providers

#### Returns



### getDeferredProviders
<!-- @doc-source: ThemeLoader.getDeferredProviders -->
Get all deferred providers

#### Returns



### isBooted
<!-- @doc-source: ThemeLoader.isBooted -->
Check if framework is booted

#### Returns



