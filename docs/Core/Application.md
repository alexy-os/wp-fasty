# Класс Application

## Обзор

`Application` - это ядро мини-фреймворка Fasty, реализующее паттерн singleton и обеспечивающее центральную точку управления приложением.

## Основные возможности

- 🧩 Единый экземпляр приложения
- 🔧 Управление конфигурациями
- 🚀 Работа с контейнером зависимостей
- 🌐 Информация о родительской теме

## Ключевые методы

### `getInstance()`

Получение единственного экземпляра приложения:

```php
$app = Application::getInstance();
```

### `config(string $key, $default = null)`

Получение значений конфигурации:

```php
// Получение значения из config/app.php
$debugMode = $app->config('app.debug', false);

// Получение значения с значением по умолчанию
$apiKey = $app->config('services.api_key', 'default_key');
```

### `getContainer()`

Получение контейнера зависимостей:

```php
$container = $app->getContainer();
```

### `getParentTheme()`

Получение информации о родительской теме:

```php
$parentTheme = $app->getParentTheme();
$parentName = $parentTheme->get('Name');
```

### `isAdminPage(string $page)`

Проверка текущей административной страницы:

```php
if ($app->isAdminPage('themes.php')) {
    // Действия на странице тем
}
```

## Принципы работы

### Загрузка конфигураций

```php
private function loadConfigs(): void {
    $configPath = FASTY_CHILD_PATH . '/fasty/config';
    
    if (is_dir($configPath)) {
        $files = glob($configPath . '/*.php');
        
        foreach ($files as $file) {
            $key = basename($file, '.php');
            $this->configs[$key] = include $file;
        }
    }
}
```

### Получение вложенных конфигураций

```php
public function config(string $key, $default = null) {
    $parts = explode('.', $key);
    $file = $parts[0];
    
    if (!isset($this->configs[$file])) {
        return $default;
    }
    
    $config = $this->configs[$file];
    
    // Поддержка вложенных ключей
    if (count($parts) > 1) {
        for ($i = 1; $i < count($parts); $i++) {
            if (!isset($config[$parts[$i]])) {
                return $default;
            }
            $config = $config[$parts[$i]];
        }
    }
    
    return $config;
}
```

## Пример использования конфигурации

```php
// config/app.php
return [
    'debug' => true,
    'name' => 'Fasty Child Theme',
    'services' => [
        'analytics' => [
            'enabled' => true,
            'tracking_id' => 'UA-XXXXX-Y'
        ]
    ]
];

// Использование в коде
$app = Application::getInstance();

$isDebug = $app->config('app.debug'); // true
$themeName = $app->config('app.name'); // 'Fasty Child Theme'
$analyticsEnabled = $app->config('app.services.analytics.enabled'); // true
```

## Преимущества

- 🔒 Единая точка доступа к настройкам
- 🧠 Гибкая система конфигурации
- 🔗 Интеграция с контейнером зависимостей

## Советы по использованию

1. Храните конфигурации в отдельных файлах
2. Используйте значения по умолчанию
3. Не храните чувствительные данные в конфигурациях
4. Используйте окружения (dev, prod)

## Возможные проблемы

- Следите за производительностью при частом доступе к конфигурациям
- Избегайте сложных вложенных структур
- Используйте осторожно с глобальным состоянием
- Валидируйте входные данные конфигурации

# Application

<!-- @doc-source: Application -->


<!-- @doc-extend: Application -->
Application - это основной класс фреймворка FastyChild, который отвечает за:
- Инициализацию приложения
- Управление конфигурацией
- Контейнер зависимостей
- Взаимодействие с родительской темой WordPress
<!-- @doc-extend-end -->

## Methods

### __construct
<!-- @doc-source: Application.__construct -->
Main Application class for FastyChild framework
Serves as the core of the framework
/

namespace FastyChild\Core;

class Application {
/**
Singleton instance

### getInstance
<!-- @doc-source: Application.getInstance -->
Get application instance

#### Returns



<!-- @doc-extend: Application.getInstance -->
Метод реализует паттерн Singleton, гарантируя единственный экземпляр приложения.

#### Example
```php
$app = Application::getInstance();
```

#### Best Practices
- Используйте этот метод вместо прямого создания экземпляра
- Не злоупотребляйте глобальным доступом к приложению
<!-- @doc-extend-end -->

### loadConfigs
<!-- @doc-source: Application.loadConfigs -->
Load configuration files

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



