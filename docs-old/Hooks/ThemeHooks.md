# Базовые хуки темы (ThemeHooks)

## Обзор

`ThemeHooks` управляет основными хуками WordPress и базовыми настройками темы в мини-фреймворке Fasty.

## Основные возможности

- 🎨 Кастомизация классов тела документа
- 📝 Модификация excerpts
- 🚫 Отключение ненужных функций WordPress
- 🧹 Очистка заголовков HTML

## Ключевые методы

### `register()`

Регистрация основных хуков WordPress:

```php
public function register(): void {
    add_filter('body_class', [$this, 'addBodyClasses']);
    add_filter('excerpt_more', [$this, 'modifyExcerptMore']);
    add_filter('excerpt_length', [$this, 'modifyExcerptLength']);
    
    $this->disableEmoji();
    $this->cleanHead();
}
```

## Примеры кастомизации

### Добавление классов к body

```php
// config/theme.php
return [
    'body_classes' => [
        'custom-theme-class',
        'dark-mode'
    ]
];
```

### Настройка excerpts

```php
// config/theme.php
return [
    'excerpt_length' => 30, // Количество слов
    'excerpt_more' => ' [Читать далее...]' // Текст после excerpt
];
```

### Отключение emoji

```php
// config/theme.php
return [
    'disable_emoji' => true // По умолчанию true
];
```

### Очистка заголовков HTML

```php
// config/theme.php
return [
    'clean_head' => true // По умолчанию true
];
```

## Методы кастомизации

### Добавление классов к body

```php
public function addBodyClasses(array $classes): array {
    // Добавление базового класса темы
    $classes[] = 'fasty-storefront-child';
    
    // Добавление пользовательских классов
    $custom_classes = $app->config('theme.body_classes', []);
    
    return array_merge($classes, $custom_classes);
}
```

### Модификация excerpt

```php
public function modifyExcerptMore(string $more): string {
    return $app->config('theme.excerpt_more', '&hellip;');
}

public function modifyExcerptLength(int $length): int {
    return $app->config('theme.excerpt_length', 55);
}
```

### Отключение emoji

```php
private function disableEmoji(): void {
    if ($app->config('theme.disable_emoji', true)) {
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        // Другие действия по отключению emoji
    }
}
```

### Очистка заголовков HTML

```php
private function cleanHead(): void {
    if ($app->config('theme.clean_head', true)) {
        remove_action('wp_head', 'wp_generator');
        remove_action('wp_head', 'wlwmanifest_link');
        // Другие действия по очистке
    }
}
```

## Преимущества

- 🔌 Полная совместимость с WordPress
- 🧩 Гибкая настройка через конфигурационные файлы
- 🚀 Быстрая кастомизация без правки исходного кода
- 🛠 Улучшение производительности и безопасности

## Советы по использованию

1. Используйте конфигурационные файлы для настройки
2. Минимизируйте прямые модификации в коде
3. Тестируйте изменения на разных устройствах
4. Следите за совместимостью с плагинами

## Возможные проблемы

- Проверяйте совместимость с обновлениями WordPress
- Избегайте слишком агрессивной очистки заголовков
- Следите за корректностью работы плагинов
- Обрабатывайте возможные конфликты с другими темами и плагинами


## Methods

### __construct
<!-- @doc-source: ThemeHooks.__construct -->
Theme Hooks
Handles basic WordPress hook overrides
/

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class ThemeHooks {
/**
Container instance

#### Parameters

- ``: container Container

### register
<!-- @doc-source: ThemeHooks.register -->
Register hook handlers

#### Returns



### addBodyClasses
<!-- @doc-source: ThemeHooks.addBodyClasses -->
Add custom body classes

#### Parameters

- ``: array $classes Existing body classes
- ``: classes array

#### Returns



### modifyExcerptMore
<!-- @doc-source: ThemeHooks.modifyExcerptMore -->
Modify excerpt "read more" string

#### Parameters

- ``: string $more Default "read more" string
- ``: more string

#### Returns



### modifyExcerptLength
<!-- @doc-source: ThemeHooks.modifyExcerptLength -->
Modify excerpt length

#### Parameters

- ``: int $length Default excerpt length
- ``: length int

#### Returns



### disableEmoji
<!-- @doc-source: ThemeHooks.disableEmoji -->
Disable WordPress emoji functionality

#### Returns



### cleanHead
<!-- @doc-source: ThemeHooks.cleanHead -->
Clean up WordPress head

#### Returns



