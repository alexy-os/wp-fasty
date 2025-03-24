# Инициализация фреймворка Fasty

- [Документация](./index.md)

## Обзор

Файл `bootstrap.php` является ключевым компонентом инициализации мини-фреймворка дочерних тем Fasty. Он отвечает за загрузку и настройку основных сервисов и провайдеров темы.

## Основные функции

### 1. Проверка доступа

```php
if (!defined('ABSPATH')) {
    exit; // Предотвращение прямого доступа к файлу
}
```

### 2. Инициализация загрузчика темы

```php
$theme = ThemeLoader::init();
```

### 3. Регистрация базовых сервис-провайдеров

```php
$theme->registerProvider(Core\Providers\AssetsServiceProvider::class);
$theme->registerProvider(Core\Providers\ThemeServiceProvider::class);
$theme->registerProvider(Core\Providers\HooksProvider::class);
```

### 4. Динамическая загрузка дополнительных провайдеров

```php
if (file_exists(FASTY_CHILD_PATH . '/fasty/config/providers.php')) {
    $providers = include FASTY_CHILD_PATH . '/fasty/config/providers.php';
    if (is_array($providers)) {
        foreach ($providers as $provider) {
            $theme->registerProvider($provider);
        }
    }
}
```

### 5. Загрузка фреймворка

```php
$theme->boot(); // Инициализация всех зарегистрированных сервисов
```

### 6. Уведомление о полной загрузке

```php
do_action('fasty_child_framework_loaded', $theme);
```

## Использование в дочерней теме

В файле `functions.php` вашей дочерней темы:

```php
<?php
// Подключение bootstrap.php
require_once get_stylesheet_directory() . '/fasty/bootstrap.php';
```

## Расширение функциональности

### Добавление собственных провайдеров

Создайте файл `fasty/config/providers.php`:

```php
<?php
return [
    \YourNamespace\CustomServiceProvider::class,
    // Другие провайдеры
];
```

## Хуки и события

- `fasty_child_framework_loaded`: Вызывается после полной загрузки фреймворка
- Позволяет подключать дополнительную логику после инициализации

## Советы по использованию

1. Всегда проверяйте наличие необходимых файлов и зависимостей
2. Используйте пространства имен для предотвращения конфликтов
3. Следуйте принципам модульности при расширении фреймворка

## Возможные проблемы и их решение

- Убедитесь, что путь к `bootstrap.php` корректен
- Проверьте совместимость провайдеров
- Используйте режим отладки для диагностики проблем

