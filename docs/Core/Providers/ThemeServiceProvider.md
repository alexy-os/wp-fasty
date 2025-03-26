# ThemeServiceProvider

`ThemeServiceProvider` - это основной провайдер темы Fasty, отвечающий за регистрацию и инициализацию базовых сервисов темы.

## Назначение

- Регистрация основных сервисов темы
- Настройка базовой функциональности WordPress
- Управление поддержкой темы
- Инициализация компонентов темы

## Методы

### register()

```php
public function register(): void
```

Регистрирует основные сервисы темы.

#### Пример использования
```php
class ThemeServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Регистрация базовых сервисов
        $this->container->singleton(ThemeService::class);
        $this->container->singleton(ConfigService::class);
        $this->container->singleton(MenuService::class);
        
        // Регистрация дополнительных сервисов
        if (is_admin()) {
            $this->container->singleton(CustomizerService::class);
            $this->container->singleton(AdminService::class);
        }
    }
}
```

### boot()

```php
public function boot(): void
```

Инициализирует тему и её компоненты.

#### Пример использования
```php
class ThemeServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        // Настройка поддержки темы
        $this->setupThemeSupport();
        
        // Инициализация сервисов
        $this->container->get(ThemeService::class)->initialize();
        $this->container->get(MenuService::class)->registerMenus();
        
        // Настройка админки
        if (is_admin()) {
            $this->container->get(CustomizerService::class)->setup();
        }
    }
    
    private function setupThemeSupport(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        add_theme_support('html5', [
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption'
        ]);
    }
}
```

## Примеры использования

### 1. Базовая настройка темы

```php
class BasicThemeProvider extends ThemeServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        // Регистрация дополнительных сервисов
        $this->container->singleton(SidebarService::class);
        $this->container->singleton(WidgetService::class);
    }
    
    public function boot(): void
    {
        parent::boot();
        
        // Регистрация сайдбаров
        $this->container->get(SidebarService::class)->register([
            'main-sidebar' => 'Основной сайдбар',
            'footer-widgets' => 'Виджеты подвала'
        ]);
        
        // Регистрация виджетов
        $this->container->get(WidgetService::class)->register([
            SocialLinksWidget::class,
            NewsletterWidget::class
        ]);
    }
}
```

### 2. Интеграция с плагинами

```php
class ExtendedThemeProvider extends ThemeServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        // WooCommerce интеграция
        if (class_exists('WooCommerce')) {
            $this->container->singleton(WooCommerceService::class);
        }
        
        // ACF интеграция
        if (class_exists('ACF')) {
            $this->container->singleton(ACFService::class);
        }
    }
    
    public function boot(): void
    {
        parent::boot();
        
        // Инициализация интеграций
        if (class_exists('WooCommerce')) {
            $this->container->get(WooCommerceService::class)->setup();
        }
        
        if (class_exists('ACF')) {
            $this->container->get(ACFService::class)->registerFields();
        }
    }
}
```

### 3. Настройка функциональности

```php
class CustomThemeProvider extends ThemeServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        // Регистрация кастомных типов записей
        $this->container->singleton(PostTypeService::class);
        
        // Регистрация таксономий
        $this->container->singleton(TaxonomyService::class);
    }
    
    public function boot(): void
    {
        parent::boot();
        
        // Регистрация типов записей
        $this->container->get(PostTypeService::class)
            ->register('portfolio', [
                'public' => true,
                'has_archive' => true,
                'supports' => ['title', 'editor', 'thumbnail']
            ])
            ->register('testimonial', [
                'public' => true,
                'supports' => ['title', 'editor']
            ]);
        
        // Регистрация таксономий
        $this->container->get(TaxonomyService::class)
            ->register('portfolio_category', 'portfolio', [
                'hierarchical' => true,
                'public' => true
            ]);
    }
}
```

## Лучшие практики

### 1. Модульная регистрация

```php
class ModularThemeProvider extends ThemeServiceProvider
{
    public function register(): void
    {
        $this->registerCore();
        $this->registerAdmin();
        $this->registerFrontend();
        $this->registerIntegrations();
    }
    
    private function registerCore(): void
    {
        $this->container->singleton(ThemeService::class);
        $this->container->singleton(ConfigService::class);
    }
    
    private function registerAdmin(): void
    {
        if (is_admin()) {
            $this->container->singleton(CustomizerService::class);
            $this->container->singleton(AdminService::class);
        }
    }
    
    private function registerFrontend(): void
    {
        $this->container->singleton(MenuService::class);
        $this->container->singleton(SidebarService::class);
    }
    
    private function registerIntegrations(): void
    {
        if (class_exists('WooCommerce')) {
            $this->container->singleton(WooCommerceService::class);
        }
    }
}
```

### 2. Конфигурация темы

```php
class ConfigurableThemeProvider extends ThemeServiceProvider
{
    public function boot(): void
    {
        parent::boot();
        
        // Загрузка конфигурации
        $config = $this->container->get(ConfigService::class);
        
        // Настройка изображений
        if ($imageSizes = $config->get('theme.image_sizes')) {
            foreach ($imageSizes as $name => $size) {
                add_image_size($name, $size[0], $size[1], $size[2] ?? false);
            }
        }
        
        // Настройка меню
        if ($menus = $config->get('theme.menus')) {
            register_nav_menus($menus);
        }
        
        // Настройка поддержки
        if ($support = $config->get('theme.support')) {
            foreach ($support as $feature => $args) {
                add_theme_support($feature, $args);
            }
        }
    }
}
```

### 3. Обработка ошибок

```php
class SafeThemeProvider extends ThemeServiceProvider
{
    public function boot(): void
    {
        try {
            parent::boot();
            
            // Дополнительная инициализация
            $this->initializeComponents();
        } catch (Exception $e) {
            // Логирование ошибки
            error_log("Ошибка инициализации темы: {$e->getMessage()}");
            
            // Уведомление администратора
            if (is_admin()) {
                add_action('admin_notices', function () use ($e) {
                    echo '<div class="error"><p>Ошибка инициализации темы: ' . 
                         esc_html($e->getMessage()) . '</p></div>';
                });
            }
        }
    }
    
    private function initializeComponents(): void
    {
        // Безопасная инициализация компонентов
        $services = [
            ThemeService::class,
            MenuService::class,
            SidebarService::class
        ];
        
        foreach ($services as $service) {
            try {
                $this->container->get($service)->initialize();
            } catch (Exception $e) {
                error_log("Ошибка инициализации {$service}: {$e->getMessage()}");
            }
        }
    }
}
```

## Заключение

`ThemeServiceProvider` предоставляет:
- Централизованную регистрацию сервисов темы
- Управление базовой функциональностью WordPress
- Гибкую настройку поддержки темы
- Безопасную инициализацию компонентов
- Интеграцию с плагинами
- Модульную архитектуру


