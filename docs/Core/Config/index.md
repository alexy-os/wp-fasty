# Конфигурации в Fasty

## Введение

Система конфигураций в Fasty предоставляет гибкий и безопасный способ управления настройками темы. Она построена на принципах:
- Централизованного хранения настроек
- Иерархической структуры
- Кэширования для производительности
- Безопасной валидации данных

## Архитектура

### ConfigInterface

Интерфейс `ConfigInterface` определяет контракт для работы с конфигурациями:

```php
interface ConfigInterface {
    public function get(string $key, mixed $default = null): mixed;
    public function set(string $key, mixed $value): void;
    public function has(string $key): bool;
    public function load(string $file): void;
    public function saveCache(): void;
    public function loadCache(): void;
    public function clearCache(): void;
}
```

### ConfigManager

`ConfigManager` - это основная реализация `ConfigInterface`, которая:
- Загружает конфигурации из файлов
- Управляет кэшированием
- Обеспечивает безопасность данных

## Использование

### 1. Базовые операции

```php
// Получение значения
$debug = $config->get('app.debug', false);

// Установка значения
$config->set('theme.colors.primary', '#007bff');

// Проверка существования
if ($config->has('social.twitter')) {
    // ...
}
```

### 2. Работа с файлами конфигурации

```php
// config/app.php
return [
    'name' => 'My Theme',
    'debug' => true,
    'providers' => [
        ThemeServiceProvider::class,
        MenuServiceProvider::class
    ]
];

// Загрузка конфигурации
$config->load('config/app.php');
```

### 3. Иерархический доступ

```php
// Получение вложенных значений через точечную нотацию
$primaryColor = $config->get('theme.colors.primary');
$menuItems = $config->get('navigation.main.items', []);
```

### 4. Кэширование

```php
// Сохранение конфигураций в кэш
$config->saveCache();

// Загрузка из кэша
$config->loadCache();

// Очистка кэша
$config->clearCache();
```

## Лучшие практики

### 1. Структурирование конфигураций

```php
// config/theme.php
return [
    'colors' => [
        'primary' => '#007bff',
        'secondary' => '#6c757d'
    ],
    'typography' => [
        'font-family' => 'Arial, sans-serif',
        'base-size' => '16px'
    ]
];
```

### 2. Безопасное использование

```php
// Всегда указывайте значение по умолчанию
$fontSize = $config->get('typography.base-size', '16px');

// Проверяйте существование перед использованием
if ($config->has('social.networks')) {
    foreach ($config->get('social.networks') as $network) {
        // ...
    }
}
```

### 3. Кэширование в продакшене

```php
class ConfigServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $config = new ConfigManager();
        
        if (WP_ENV === 'production' && $config->loadCache()) {
            return;
        }
        
        $config->load('config/app.php');
        $config->load('config/theme.php');
        $config->saveCache();
    }
}
```

## Интеграция с WordPress

### 1. Настройки темы

```php
// Получение настроек темы
$logoUrl = $config->get('theme.logo');
add_theme_support('custom-logo', [
    'height' => $config->get('theme.logo.height', 100),
    'width' => $config->get('theme.logo.width', 400)
]);
```

### 2. Меню и виджеты

```php
// Регистрация меню из конфигурации
$menus = $config->get('theme.menus', []);
foreach ($menus as $location => $description) {
    register_nav_menu($location, $description);
}
```

## Обработка ошибок

```php
try {
    $config->load('config/non-existent.php');
} catch (ConfigurationException $e) {
    // Обработка ошибки загрузки конфигурации
    error_log($e->getMessage());
}
``` 