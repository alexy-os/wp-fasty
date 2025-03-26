# Сервис Theme (ThemeService)

## Обзор

`ThemeService` управляет основной настройкой и функциональностью темы WordPress в мини-фреймворке Fasty. Этот сервис отвечает за первоначальную инициализацию темы, регистрацию поддержки различных функций WordPress и настройку пользовательских параметров.

## Основные возможности

- 🌐 Загрузка текстового домена для интернационализации
- 🎨 Регистрация поддержки функций темы
- 📋 Настройка навигационных меню
- 🖼 Регистрация пользовательских размеров изображений
- 🛠 Расширенная настройка через Customizer
- 🧩 Регистрация виджетов и областей виджетов

## Конфигурация темы

### Поддержка темы

```php
// config/theme.php
return [
    'supports' => [
        'title-tag',
        'post-thumbnails',
        'responsive-embeds',
        'align-wide',
        'editor-styles'
    ],
    'image_sizes' => [
        'blog-thumbnail' => [300, 200, true],
        'hero-image' => [1920, 600, false]
    ]
];
```

### Навигационные меню

```php
// config/theme.php
return [
    'menus' => [
        'primary' => __('Основное меню', 'fasty'),
        'footer' => __('Меню подвала', 'fasty'),
        'mobile' => __('Мобильное меню', 'fasty')
    ]
];
```

## Ключевые методы

### `setupTheme()`

Первоначальная настройка темы:

```php
public function setupTheme(): void {
    // Загрузка текстового домена
    load_theme_textdomain('fasty', FASTY_CHILD_PATH . '/languages');

    // Регистрация поддержки функций
    $supports = $this->app->config('theme.supports', []);
    foreach ($supports as $feature) {
        add_theme_support($feature);
    }

    // Регистрация навигационных меню
    $menus = $this->app->config('theme.menus', []);
    register_nav_menus($menus);
}
```

### `registerImageSizes()`

Регистрация пользовательских размеров изображений:

```php
public function registerImageSizes(): void {
    $image_sizes = $this->app->config('theme.image_sizes', []);
    foreach ($image_sizes as $name => $params) {
        add_image_size($name, $params[0], $params[1], $params[2]);
    }
}
```

### `registerCustomizer()`

Расширенная настройка через Customizer:

```php
public function registerCustomizer($wp_customize): void {
    $customizer_settings = $this->app->config('theme.customizer', []);
    
    foreach ($customizer_settings as $section => $controls) {
        // Регистрация секций и элементов управления
        $wp_customize->add_section($section, $controls['section']);
        
        foreach ($controls['settings'] as $setting_id => $setting) {
            $wp_customize->add_setting($setting_id, $setting);
            $wp_customize->add_control($setting_id, $setting['control']);
        }
    }
}
```

### `registerWidgets()`

Регистрация виджетов и областей виджетов:

```php
public function registerWidgets(): void {
    $widget_areas = $this->app->config('theme.widget_areas', []);
    foreach ($widget_areas as $id => $args) {
        register_sidebar([
            'id' => $id,
            'name' => $args['name'],
            'description' => $args['description'],
            'before_widget' => $args['before_widget'] ?? '<div id="%1$s" class="widget %2$s">',
            'after_widget' => $args['after_widget'] ?? '</div>',
            'before_title' => $args['before_title'] ?? '<h3 class="widget-title">',
            'after_title' => $args['after_title'] ?? '</h3>',
        ]);
    }
}
```

## Преимущества

- 🔌 Полная интеграция с WordPress
- 🧩 Гибкая настройка через конфигурационные файлы
- 🚀 Быстрая и простая кастомизация темы
- 🌍 Поддержка многоязычности
- 🎨 Расширенные возможности настройки через Customizer

## Советы по использованию

1. Используйте конфигурационные файлы для настройки темы
2. Минимизируйте прямые модификации в коде
3. Всегда добавляйте файлы перевода
4. Тестируйте совместимость с плагинами
5. Следите за производительностью при добавлении большого количества функций

## Возможные проблемы

- Проверяйте совместимость с обновлениями WordPress
- Избегайте чрезмерной кастомизации
- Следите за производительностью при регистрации множества виджетов
- Тестируйте на разных устройствах и браузерах
- Обратите внимание на конфликты с другими плагинами и темами

namespace FastyChild\Services;

use FastyChild\Core\Application;

class ThemeService {
/**
Application instance

#### Parameters

- ``: app Application

### setupTheme
<!-- @doc-source: ThemeService.setupTheme -->
Setup theme features

#### Returns



### registerImageSizes
<!-- @doc-source: ThemeService.registerImageSizes -->
Register custom image sizes

#### Returns



### registerCustomizer
<!-- @doc-source: ThemeService.registerCustomizer -->
Register customizer settings

#### Parameters

- ``: null \WP_Customize_Manager $wp_customize
- ``: wp_customize mixed

#### Returns



### registerWidgets
<!-- @doc-source: ThemeService.registerWidgets -->
Register custom widgets

#### Returns



