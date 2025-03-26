# Утилиты (Utils)

## Введение

Класс `Utils` предоставляет набор статических методов для решения общих задач при разработке WordPress тем. Это вспомогательные функции для работы с путями, файлами, безопасностью и отладкой.

## Основные возможности

- Конвертация путей в URL
- Управление версиями файлов
- Санитизация HTML
- Проверка окружения
- Управление версиями фреймворка

## Работа с путями и URL

### Конвертация пути в URL

```php
use FastyChild\Core\Utils;

// Абсолютный путь в URL
$path = FASTY_CHILD_PATH . '/assets/css/style.css';
$url = Utils::pathToUrl($path);
// Результат: https://site.com/wp-content/themes/my-theme/assets/css/style.css

// Относительный путь
$url = Utils::pathToUrl('assets/js/script.js');
// Результат: https://site.com/wp-content/themes/my-theme/assets/js/script.js
```

### Проверка существования файла

```php
$uri = get_stylesheet_directory_uri() . '/css/custom.css';
$defaultUri = get_template_directory_uri() . '/css/style.css';

// Вернет custom.css если файл существует, иначе style.css
$cssUri = Utils::fileExistsUri($uri, $defaultUri);
```

## Версионирование файлов

### Получение версии файла

```php
// Версия на основе времени изменения файла
$version = Utils::getFileVersion('path/to/style.css');

// Использование в wp_enqueue_style
wp_enqueue_style(
    'theme-style',
    get_stylesheet_uri(),
    [],
    Utils::getFileVersion(get_stylesheet_directory() . '/style.css')
);
```

## Безопасность

### Санитизация HTML

```php
// Базовая санитизация с разрешенными тегами по умолчанию
$safeHtml = Utils::sanitizeHtml($unsafeContent);

// Кастомные разрешенные теги
$allowedTags = [
    'a' => [
        'href' => [],
        'title' => [],
        'class' => []
    ],
    'p' => ['class' => []],
    'strong' => []
];

$safeHtml = Utils::sanitizeHtml($content, $allowedTags);
```

### Разрешенные HTML теги по умолчанию

```php
const ALLOWED_HTML_TAGS = [
    'a' => [
        'href' => [],
        'title' => [],
        'target' => [],
        'rel' => [],
        'class' => []
    ],
    'br' => [],
    'em' => [],
    'strong' => [],
    'p' => ['class' => []],
    'span' => ['class' => []],
    'div' => ['class' => []],
    // ... и другие безопасные теги
];
```

## Определение окружения

### Проверка режима отладки

```php
if (Utils::isDebugEnabled()) {
    // Дополнительное логирование
    error_log('Debug info: ' . print_r($data, true));
}
```

### Проверка окружения разработки

```php
if (Utils::isDevelopmentEnvironment()) {
    // Включаем инструменты разработчика
    add_action('wp_footer', function() {
        include 'debug-bar.php';
    });
}
```

## Управление версиями

### Получение версии фреймворка

```php
$currentVersion = Utils::getFrameworkVersion();
```

### Сравнение версий

```php
// Проверка минимальной версии
if (Utils::isVersionAtLeast('1.2.0')) {
    // Используем новые возможности
}

// Сравнение версий
$result = Utils::compareVersions('1.1.0', '1.2.0'); // Вернет -1
```

## Лучшие практики

### 1. Безопасная работа с путями

```php
// Плохо
$url = str_replace(ABSPATH, site_url('/'), $path);

// Хорошо
$url = Utils::pathToUrl($path);
```

### 2. Версионирование ресурсов

```php
// Плохо
wp_enqueue_style('theme-style', $styleUrl, [], '1.0');

// Хорошо
wp_enqueue_style('theme-style', $styleUrl, [], Utils::getFileVersion($stylePath));
```

### 3. Санитизация данных

```php
// Плохо
echo $userContent;

// Хорошо
echo Utils::sanitizeHtml($userContent);
```

## Отладка

### Логирование в режиме разработки

```php
if (Utils::isDevelopmentEnvironment()) {
    Utils::debug('Loading theme components...');
    Utils::debug('Config:', $config);
}
```

### Проверка версий

```php
try {
    if (!Utils::isVersionAtLeast('2.0.0')) {
        throw new \Exception('Требуется Fasty Framework 2.0.0 или выше');
    }
} catch (\Exception $e) {
    // Обработка ошибки
}
```

## Расширение функционала

### Добавление собственных утилит

```php
class ThemeUtils extends Utils
{
    public static function customFunction()
    {
        // Ваша логика
    }
}
```

## Интеграция с WordPress

### Хуки и фильтры

```php
add_filter('the_content', function($content) {
    return Utils::sanitizeHtml($content);
});
```

### Условная загрузка ресурсов

```php
if (Utils::fileExistsUri($customStyleUri)) {
    wp_enqueue_style('custom-style', $customStyleUri);
}
```

## Заключение

Класс `Utils` предоставляет:
- Безопасные методы работы с данными
- Удобные инструменты для разработки
- Оптимизацию производительности
- Управление версиями и окружением


## Methods

### pathToUrl
<!-- @doc-source: Utils.pathToUrl -->
Utility class with helper methods
/
final class Utils
{
/**
Default allowed HTML tags for sanitization

#### Parameters

- ``: path string

#### Returns



### getFileVersion
<!-- @doc-source: Utils.getFileVersion -->
Get file version based on modification time

#### Parameters

- ``: string $file File path
- ``: file string

#### Returns



### fileExistsUri
<!-- @doc-source: Utils.fileExistsUri -->
Check if a file exists and return appropriate URI

#### Parameters

- ``: string $uri URI to check
- ``: string $default Default URI if file not found
- ``: uri string
- ``: default string

#### Returns



### sanitizeHtml
<!-- @doc-source: Utils.sanitizeHtml -->
Sanitize HTML content with allowed tags

#### Parameters

- ``: string $content Content to sanitize
- ``: array $allowedTags Allowed HTML tags
- ``: content string
- ``: allowedTags array

#### Returns



### isDebugEnabled
<!-- @doc-source: Utils.isDebugEnabled -->
Check if debug mode is enabled

#### Returns



### isDevelopmentEnvironment
<!-- @doc-source: Utils.isDevelopmentEnvironment -->
Check if the current environment is development

#### Returns



### getFrameworkVersion
<!-- @doc-source: Utils.getFrameworkVersion -->
Get the current framework version

#### Returns



### compareVersions
<!-- @doc-source: Utils.compareVersions -->
Compare framework versions

#### Parameters

- ``: string $version1 First version
- ``: string $version2 Second version
- ``: version1 string
- ``: version2 string

#### Returns



### isVersionAtLeast
<!-- @doc-source: Utils.isVersionAtLeast -->
Check if current framework version is at least the given version

#### Parameters

- ``: string $version Version to check against
- ``: version string

#### Returns



