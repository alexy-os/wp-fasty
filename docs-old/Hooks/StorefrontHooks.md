# Хуки Storefront (StorefrontHooks)

## Обзор

`StorefrontHooks` управляет специфическими хуками и модификациями темы Storefront в мини-фреймворке Fasty.

## Основные возможности

- 🎨 Кастомизация макета страниц
- 🔧 Гибкая настройка футера
- 🚀 Динамическая обработка хуков из конфигурации
- 🌐 Полный контроль над шаблоном Storefront

## Ключевые методы

### `register()`

Регистрация основных хуков Storefront:

```php
public function register(): void {
    add_action('init', [$this, 'processAllHooksFromConfig'], 5);
    add_filter('storefront_page_layout', [$this, 'modifyPageLayout']);
    add_filter('storefront_footer_widget_columns', [$this, 'modifyFooterWidgetColumns']);
    add_filter('storefront_credit_text', [$this, 'modifyCreditText']);
}
```

### `processAllHooksFromConfig()`

Динамическая обработка хуков из конфигурации:

```php
public function processAllHooksFromConfig(): void {
    $hooks_config = $app->config('storefront.hooks', []);
    
    // Удаление существующих хуков
    if (isset($hooks_config['remove'])) {
        foreach ($hooks_config['remove'] as $callback => $priority) {
            remove_action($hook_name, $callback, $priority);
        }
    }
    
    // Добавление новых хуков
    if (isset($hooks_config['add'])) {
        foreach ($hooks_config['add'] as $method => $priority) {
            add_action($hook_name, [$this, $method], $priority);
        }
    }
}
```

## Примеры кастомизации

### Изменение макета страницы

```php
// config/storefront.php
return [
    'page_layout' => 'full-width', // Возможные значения: full-width, left-sidebar, right-sidebar
];
```

### Настройка футера

```php
// config/storefront.php
return [
    'footer_widget_columns' => 4, // Количество колонок в футере
    'credit_text' => 'Создано с помощью Fasty', // Текст копирайта
];
```

### Полная замена шапки

```php
// config/storefront.php
return [
    'override_header' => true, // Включает кастомный header
];
```

## Пример кастомного заголовка

```php
public function customHeader(): void {
    ?>
    <header class="fasty-navbar">
        <div class="fasty-navbar-container">
            <!-- Логотип -->
            <div class="fasty-navbar-brand">
                <?php storefront_site_title_or_logo(); ?>
            </div>
            
            <!-- Навигация -->
            <nav class="fasty-navbar-menu">
                <?php storefront_primary_navigation(); ?>
            </nav>
        </div>
    </header>
    <?php
}
```

## Преимущества

- 🔌 Полная совместимость с Storefront
- 🧩 Гибкая настройка через конфигурационные файлы
- 🚀 Быстрая кастомизация без правки исходного кода темы

## Советы по использованию

1. Используйте конфигурационные файлы для настройки
2. Минимизируйте прямые модификации в коде
3. Тестируйте изменения на разных устройствах
4. Следите за совместимостью с плагинами

## Возможные проблемы

- Проверяйте совместимость с обновлениями Storefront
- Избегайте слишком сложных кастомизаций
- Следите за производительностью при большом количестве хуков
- Обрабатывайте возможные конфликты с другими плагинами

# StorefrontHooks

<!-- @doc-source: StorefrontHooks -->


## Methods

### __construct
<!-- @doc-source: StorefrontHooks.__construct -->
Storefront Hooks
Handles Storefront theme-specific hooks and overrides
/

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class StorefrontHooks {
/**
Container instance

#### Parameters

- ``: container Container

### register
<!-- @doc-source: StorefrontHooks.register -->
Register hook handlers

#### Returns



### processAllHooksFromConfig
<!-- @doc-source: StorefrontHooks.processAllHooksFromConfig -->
Обрабатывает все хуки из конфигурации

#### Returns



### modifyPageLayout
<!-- @doc-source: StorefrontHooks.modifyPageLayout -->
Modify page layout

#### Parameters

- ``: string $layout Default layout
- ``: layout string

#### Returns



### modifyFooterWidgetColumns
<!-- @doc-source: StorefrontHooks.modifyFooterWidgetColumns -->
Modify footer widget columns

#### Parameters

- ``: int $columns Default number of columns
- ``: columns int

#### Returns



### modifyCreditText
<!-- @doc-source: StorefrontHooks.modifyCreditText -->
Modify credit text

#### Parameters

- ``: string $text Default credit text
- ``: text string

#### Returns



### customHeader
<!-- @doc-source: StorefrontHooks.customHeader -->
Custom header implementation

#### Returns



