# Провайдер Theme (ThemeServiceProvider)

## Обзор

`ThemeServiceProvider` отвечает за настройку, регистрацию и инициализацию основных функций темы в мини-фреймворке Fasty.

## Основные функции

### Регистрация сервиса темы

```php
public function register(): void {
    // Регистрация ThemeService в контейнере
    $this->container->singleton('theme', function() {
        return new ThemeService($this->container->get('app'));
    });
}
```

- Регистрирует `ThemeService` как синглтон в контейнере
- Создает единственный экземпляр сервиса управления темой

### Инициализация функций темы

```php
public function boot(): void {
    $theme = $this->container->get('theme');
    
    // Настройка базовых функций темы
    add_action('after_setup_theme', [$theme, 'setupTheme']);
    
    // Регистрация пользовательских размеров изображений
    add_action('after_setup_theme', [$theme, 'registerImageSizes']);
    
    // Регистрация настроек кастомайзера
    add_action('customize_register', [$theme, 'registerCustomizer']);
    
    // Регистрация виджетов
    add_action('widgets_init', [$theme, 'registerWidgets']);
}
```

## Ключевые возможности

- 🎨 Централизованная настройка темы
- 🖼 Регистрация пользовательских размеров изображений
- 🛠 Расширенная кастомизация через WordPress Customizer
- 🧩 Регистрация собственных виджетов

## Пример расширенной настройки темы

```php
namespace FastyChild\Services;

class ThemeService {
    public function setupTheme() {
        // Поддержка перевода
        load_theme_textdomain('fasty-child', get_stylesheet_directory() . '/languages');
        
        // Регистрация поддержки основных возможностей WordPress
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', [
            'search-form', 
            'comment-form', 
            'comment-list', 
            'gallery', 
            'caption'
        ]);
    }

    public function registerImageSizes() {
        // Добавление пользовательских размеров изображений
        add_image_size('hero-banner', 1920, 600, true);
        add_image_size('blog-thumbnail', 600, 400, true);
    }

    public function registerCustomizer($wp_customize) {
        // Добавление пользовательских настроек в кастомайзер
        $wp_customize->add_section('theme_colors', [
            'title' => __('Цвета темы', 'fasty-child'),
            'priority' => 30,
        ]);

        $wp_customize->add_setting('primary_color', [
            'default' => '#007bff',
            'sanitize_callback' => 'sanitize_hex_color',
        ]);

        $wp_customize->add_control(
            new WP_Customize_Color_Control(
                $wp_customize, 
                'primary_color', 
                [
                    'label' => __('Основной цвет', 'fasty-child'),
                    'section' => 'theme_colors',
                ]
            )
        );
    }

    public function registerWidgets() {
        // Регистрация пользовательских виджетов
        register_sidebar([
            'name'          => __('Боковая панель', 'fasty-child'),
            'id'            => 'sidebar-1',
            'description'   => __('Добавьте виджеты сюда', 'fasty-child'),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        ]);
    }
}
```

## Советы по использованию

1. Используйте `setupTheme()` для базовой настройки темы
2. Создавайте пользовательские размеры изображений с умом
3. Используйте кастомайзер для гибкой настройки темы
4. Регистрируйте виджеты с понятными идентификаторами

## Возможные проблемы

- Следите за совместимостью добавляемых функций
- Не перегружайте тему большим количеством кастомизаций
- Тестируйте совместимость с различными плагинами
- Используйте санитарную обработку для пользовательских настроек

namespace FastyChild\Core\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Services\ThemeService;

class ThemeServiceProvider implements ServiceProvider {
/**
Container instance

#### Parameters

- ``: container Container

### register
<!-- @doc-source: ThemeServiceProvider.register -->
Register theme service in the container

#### Returns



### boot
<!-- @doc-source: ThemeServiceProvider.boot -->
Boot theme service - register features and customizations

#### Returns



