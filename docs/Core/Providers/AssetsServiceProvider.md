# AssetsServiceProvider

`AssetsServiceProvider` - это провайдер, отвечающий за управление ассетами (CSS, JavaScript, изображения и другие медиа-файлы) в теме Fasty.

## Назначение

- Регистрация и подключение стилей
- Регистрация и подключение скриптов
- Управление зависимостями ассетов
- Оптимизация загрузки
- Условное подключение

## Методы

### register()

```php
public function register(): void
```

Регистрирует сервисы для работы с ассетами.

#### Пример использования
```php
class AssetsServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Регистрация сервисов
        $this->container->singleton(StylesService::class);
        $this->container->singleton(ScriptsService::class);
        $this->container->singleton(OptimizationService::class);
    }
}
```

### boot()

```php
public function boot(): void
```

Инициализирует и загружает ассеты.

#### Пример использования
```php
class AssetsServiceProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        // Подключение стилей
        $this->container->get(StylesService::class)->register();
        
        // Подключение скриптов
        $this->container->get(ScriptsService::class)->register();
        
        // Оптимизация
        if (!is_admin()) {
            $this->container->get(OptimizationService::class)->optimize();
        }
    }
}
```

## Примеры использования

### 1. Базовая регистрация ассетов

```php
class BasicAssetsProvider extends AssetsServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        // Регистрация основных стилей
        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_style(
                'theme-style',
                get_stylesheet_uri(),
                [],
                $this->getThemeVersion()
            );
            
            wp_enqueue_style(
                'theme-main',
                get_template_directory_uri() . '/assets/css/main.css',
                [],
                $this->getThemeVersion()
            );
            
            // Регистрация основных скриптов
            wp_enqueue_script(
                'theme-main',
                get_template_directory_uri() . '/assets/js/main.js',
                ['jquery'],
                $this->getThemeVersion(),
                true
            );
        });
    }
    
    private function getThemeVersion(): string
    {
        return wp_get_theme()->get('Version');
    }
}
```

### 2. Условная загрузка ассетов

```php
class ConditionalAssetsProvider extends AssetsServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        add_action('wp_enqueue_scripts', function () {
            // Базовые стили
            wp_enqueue_style('theme-style', get_stylesheet_uri());
            
            // Стили для WooCommerce
            if (class_exists('WooCommerce')) {
                wp_enqueue_style(
                    'theme-woocommerce',
                    get_template_directory_uri() . '/assets/css/woocommerce.css'
                );
            }
            
            // Стили для мобильных устройств
            if (wp_is_mobile()) {
                wp_enqueue_style(
                    'theme-mobile',
                    get_template_directory_uri() . '/assets/css/mobile.css'
                );
            }
            
            // Скрипты для комментариев
            if (is_singular() && comments_open()) {
                wp_enqueue_script('comment-reply');
            }
        });
    }
}
```

### 3. Оптимизация ассетов

```php
class OptimizedAssetsProvider extends AssetsServiceProvider
{
    public function register(): void
    {
        parent::register();
        
        // Отложенная загрузка стилей
        add_filter('style_loader_tag', function ($html, $handle, $href, $media) {
            if (!is_admin()) {
                $html = str_replace(
                    "rel='stylesheet'",
                    "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"",
                    $html
                );
            }
            return $html;
        }, 10, 4);
        
        // Отложенная загрузка скриптов
        add_filter('script_loader_tag', function ($tag, $handle, $src) {
            if (!is_admin()) {
                return str_replace(
                    ' src=',
                    ' defer src=',
                    $tag
                );
            }
            return $tag;
        }, 10, 3);
    }
    
    public function boot(): void
    {
        parent::boot();
        
        // Удаление ненужных стилей
        add_action('wp_enqueue_scripts', function () {
            wp_dequeue_style('wp-block-library');
            wp_dequeue_style('wp-embed');
        }, 100);
        
        // Минификация HTML
        if (!is_admin()) {
            ob_start(function ($html) {
                return $this->minifyHtml($html);
            });
        }
    }
    
    private function minifyHtml(string $html): string
    {
        $search = [
            '/\>[^\S ]+/s',  // strip whitespaces after tags
            '/[^\S ]+\</s',  // strip whitespaces before tags
            '/(\s)+/s'       // shorten multiple whitespace sequences
        ];
        
        $replace = [
            '>',
            '<',
            '\\1'
        ];
        
        return preg_replace($search, $replace, $html);
    }
}
```

## Лучшие практики

### 1. Модульная организация

```php
class ModularAssetsProvider extends AssetsServiceProvider
{
    public function register(): void
    {
        $this->registerStyles();
        $this->registerScripts();
        $this->registerOptimizations();
    }
    
    private function registerStyles(): void
    {
        add_action('wp_enqueue_scripts', function () {
            // Основные стили
            wp_enqueue_style('theme-core', 'assets/css/core.css');
            
            // Компонентные стили
            wp_enqueue_style('theme-components', 'assets/css/components.css');
            
            // Утилиты
            wp_enqueue_style('theme-utilities', 'assets/css/utilities.css');
        });
    }
    
    private function registerScripts(): void
    {
        add_action('wp_enqueue_scripts', function () {
            // Основные скрипты
            wp_enqueue_script('theme-core', 'assets/js/core.js');
            
            // Компоненты
            wp_enqueue_script('theme-components', 'assets/js/components.js');
            
            // Утилиты
            wp_enqueue_script('theme-utilities', 'assets/js/utilities.js');
        });
    }
    
    private function registerOptimizations(): void
    {
        if (!is_admin()) {
            add_filter('style_loader_tag', [$this, 'deferStyles'], 10, 4);
            add_filter('script_loader_tag', [$this, 'deferScripts'], 10, 3);
        }
    }
}
```

### 2. Управление зависимостями

```php
class DependentAssetsProvider extends AssetsServiceProvider
{
    private array $dependencies = [
        'theme-core' => [],
        'theme-components' => ['theme-core'],
        'theme-utilities' => ['theme-core', 'theme-components'],
        'theme-woocommerce' => ['theme-core', 'theme-components']
    ];
    
    public function register(): void
    {
        add_action('wp_enqueue_scripts', function () {
            foreach ($this->dependencies as $handle => $deps) {
                $path = "assets/css/{$handle}.css";
                
                if (file_exists(get_template_directory() . '/' . $path)) {
                    wp_enqueue_style(
                        $handle,
                        get_template_directory_uri() . '/' . $path,
                        $deps,
                        $this->getThemeVersion()
                    );
                }
            }
        });
    }
}
```

### 3. Безопасная загрузка

```php
class SafeAssetsProvider extends AssetsServiceProvider
{
    public function register(): void
    {
        add_action('wp_enqueue_scripts', function () {
            try {
                // Проверка существования файлов
                $this->validateAssets();
                
                // Регистрация стилей
                $this->registerStyles();
                
                // Регистрация скриптов
                $this->registerScripts();
            } catch (Exception $e) {
                // Логирование ошибки
                error_log("Ошибка загрузки ассетов: {$e->getMessage()}");
                
                // Уведомление администратора
                if (is_admin()) {
                    add_action('admin_notices', function () use ($e) {
                        echo '<div class="error"><p>Ошибка загрузки ассетов: ' . 
                             esc_html($e->getMessage()) . '</p></div>';
                    });
                }
            }
        });
    }
    
    private function validateAssets(): void
    {
        $required = [
            'css/core.css',
            'js/core.js'
        ];
        
        foreach ($required as $file) {
            $path = get_template_directory() . '/assets/' . $file;
            
            if (!file_exists($path)) {
                throw new RuntimeException(
                    "Отсутствует обязательный файл: {$file}"
                );
            }
        }
    }
}
```

## Заключение

`AssetsServiceProvider` предоставляет:
- Централизованное управление ассетами
- Гибкую систему регистрации
- Оптимизацию загрузки
- Управление зависимостями
- Условное подключение
- Безопасную обработку ошибок


