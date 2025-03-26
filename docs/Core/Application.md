# Класс приложения (Application)

## Введение

`Application` - это центральный класс фреймворка Fasty, который управляет всем жизненным циклом темы WordPress. Он реализует паттерн Singleton для обеспечения единой точки доступа к основным компонентам приложения.

## Основные возможности

- Управление конфигурацией
- Доступ к контейнеру зависимостей
- Управление сервисами
- Кеширование конфигурации
- Интеграция с WordPress

## Начало работы

### Получение экземпляра приложения

```php
use FastyChild\Core\Application;

$app = Application::getInstance();
```

### Базовое использование

```php
// Получение конфигурации
$debug = $app->config('app.debug', false);

// Доступ к сервису
$theme = $app->service('theme');

// Проверка страницы админки
if ($app->isAdminPage('edit.php')) {
    // ...
}
```

## Работа с конфигурацией

### Структура конфигурации

Конфигурации хранятся в директории `config/` и автоматически загружаются:

```
config/
  ├── app.php
  ├── theme.php
  └── services.php
```

### Файл конфигурации

```php
// config/theme.php
return [
    'name' => 'My Theme',
    'supports' => [
        'post-thumbnails',
        'custom-header'
    ],
    'menus' => [
        'primary' => 'Главное меню',
        'footer' => 'Меню подвала'
    ]
];
```

### Получение настроек

```php
// Простое значение
$themeName = $app->config('theme.name');

// Со значением по умолчанию
$debug = $app->config('app.debug', false);

// Вложенные настройки
$menus = $app->config('theme.menus', []);
```

## Управление сервисами

### Регистрация сервиса

```php
$app->getContainer()->singleton('theme.menu', function() {
    return new MenuService();
});
```

### Получение сервиса

```php
// Получение обязательного сервиса
$menu = $app->service('theme.menu');

// Получение опционального сервиса
$cache = $app->service('theme.cache', false);

// Проверка наличия сервиса
if ($app->hasService('theme.cache')) {
    // ...
}
```

## Кеширование

### Конфигурация кеша

```php
// Время жизни кеша (в секундах)
define('FASTY_CONFIG_CACHE_TIME', 86400); // 24 часа
```

### Управление кешем

```php
// Сброс кеша конфигурации
$app->invalidateConfigCache();
```

## Интеграция с WordPress

### Работа с родительской темой

```php
// Получение информации о родительской теме
$parentTheme = $app->getParentTheme();
$version = $parentTheme->get('Version');
```

### Определение контекста

```php
// Проверка страницы админки
if ($app->isAdminPage('edit.php')) {
    // Мы на странице редактирования записей
}
```

## Продвинутое использование

### Отложенная загрузка сервисов

```php
$app->getContainer()->lazy('theme.heavy_service', function() {
    return new HeavyService();
});
```

### Условная конфигурация

```php
$config = $app->config('theme.features');

if (isset($config['woocommerce'])) {
    add_theme_support('woocommerce');
}
```

## Безопасность

### Санитизация данных

```php
// Конфигурации автоматически санитизируются
$html = $app->config('theme.custom_html');
```

### Обработка ошибок

```php
try {
    $service = $app->service('required.service');
} catch (NotFoundException $e) {
    // Логирование ошибки
    error_log($e->getMessage());
}
```

## Отладка

### Режим разработки

```php
if (Utils::isDevelopmentEnvironment()) {
    // Включаем дополнительное логирование
}
```

### Логирование

```php
// В режиме отладки
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log("[FASTY] Loading configuration...");
}
```

## Лучшие практики

1. **Используйте типизацию**
```php
public function getService(string $name): ?ServiceInterface
```

2. **Группируйте конфигурации**
```php
// config/theme.php
return [
    'features' => [...],
    'appearance' => [...],
    'performance' => [...]
];
```

3. **Кешируйте тяжелые операции**
```php
$value = get_transient('cache_key');
if (false === $value) {
    $value = heavy_operation();
    set_transient('cache_key', $value, HOUR_IN_SECONDS);
}
```

## Заключение

Класс `Application` предоставляет:
- Централизованное управление темой
- Гибкую систему конфигурации
- Интеграцию с WordPress
- Управление зависимостями
- Оптимизацию производительности


## Methods

### __construct
<!-- @doc-source: Application.__construct -->
Main Application class for FastyChild framework
Serves as the core of the framework
/

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\ConfigurationException;
use FastyChild\Core\Exceptions\NotFoundException;

class Application {
/**
Singleton instance

### getInstance
<!-- @doc-source: Application.getInstance -->
Get application instance

#### Returns



### loadConfigs
<!-- @doc-source: Application.loadConfigs -->
Load configuration files
Uses caching in production environment

#### Returns



### loadConfigsFromCache
<!-- @doc-source: Application.loadConfigsFromCache -->
Load configurations from cache

#### Returns



### cacheConfigs
<!-- @doc-source: Application.cacheConfigs -->
Cache configurations

#### Returns



### sanitizeConfigArray
<!-- @doc-source: Application.sanitizeConfigArray -->
Sanitize configuration array values recursively

#### Parameters

- ``: mixed $config Configuration to sanitize
- ``: config mixed

#### Returns



### sanitizeConfigValue
<!-- @doc-source: Application.sanitizeConfigValue -->
Sanitize a configuration value

#### Parameters

- ``: string $value Value to sanitize
- ``: value string

#### Returns



### config
<!-- @doc-source: Application.config -->
Get config value by key

#### Parameters

- ``: string $key Config key in format file.option
- ``: mixed $default Default value if key not found
- ``: key string
- ``: default mixed

#### Returns



### invalidateConfigCache
<!-- @doc-source: Application.invalidateConfigCache -->
Invalidate configuration cache

#### Returns



### getContainer
<!-- @doc-source: Application.getContainer -->
Get dependency container

#### Returns



### getParentTheme
<!-- @doc-source: Application.getParentTheme -->
Get parent theme information

#### Returns



### isAdminPage
<!-- @doc-source: Application.isAdminPage -->
Helper method to check if we're on a specific admin page

#### Parameters

- ``: string $page Admin page to check
- ``: page string

#### Returns



### service
<!-- @doc-source: Application.service -->
Get a service from the container

#### Parameters

- ``: string $service Service identifier
- ``: bool $required Whether the service is required
- ``: service string
- ``: required bool

#### Returns



### hasService
<!-- @doc-source: Application.hasService -->
Check if a service exists in the container

#### Parameters

- ``: string $service Service identifier
- ``: service string

#### Returns



