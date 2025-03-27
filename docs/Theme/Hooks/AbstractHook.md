# Класс AbstractHook

`AbstractHook` - это базовый класс для создания хуков в Fasty. Он реализует `HookInterface` и предоставляет основные методы для работы с хуками WordPress.

## Методы

### addAction()

```php
protected function addAction(
    string $hook,
    callable $callback,
    int $priority = 10,
    int $args = 1
): void
```

Регистрирует обработчик события WordPress.

#### Параметры
- `$hook` (string) - Имя хука
- `$callback` (callable) - Функция обработчик
- `$priority` (int) - Приоритет выполнения (по умолчанию 10)
- `$args` (int) - Количество аргументов (по умолчанию 1)

#### Пример использования
```php
class ThemeHook extends AbstractHook
{
    public function register(): void
    {
        // Простой обработчик
        $this->addAction('init', [$this, 'initialize']);
        
        // Обработчик с приоритетом
        $this->addAction('wp_enqueue_scripts', [$this, 'enqueueAssets'], 20);
        
        // Обработчик с несколькими аргументами
        $this->addAction('save_post', [$this, 'handleSave'], 10, 2);
    }
}
```

### addFilter()

```php
protected function addFilter(
    string $hook,
    callable $callback,
    int $priority = 10,
    int $args = 1
): void
```

Регистрирует фильтр WordPress.

#### Параметры
- `$hook` (string) - Имя фильтра
- `$callback` (callable) - Функция фильтрации
- `$priority` (int) - Приоритет выполнения (по умолчанию 10)
- `$args` (int) - Количество аргументов (по умолчанию 1)

#### Пример использования
```php
class ContentHook extends AbstractHook
{
    public function register(): void
    {
        // Простой фильтр
        $this->addFilter('the_content', [$this, 'formatContent']);
        
        // Фильтр с приоритетом
        $this->addFilter('the_title', [$this, 'formatTitle'], 20);
        
        // Фильтр с несколькими аргументами
        $this->addFilter('post_class', [$this, 'filterClasses'], 10, 3);
    }
}
```

### removeAction()

```php
protected function removeAction(
    string $hook,
    callable $callback,
    int $priority = 10
): void
```

Удаляет обработчик события.

#### Параметры
- `$hook` (string) - Имя хука
- `$callback` (callable) - Функция обработчик
- `$priority` (int) - Приоритет (по умолчанию 10)

#### Пример использования
```php
class CleanupHook extends AbstractHook
{
    public function register(): void
    {
        // Удаление стандартных хуков
        $this->removeAction('wp_head', 'wp_generator');
        $this->removeAction('wp_head', 'wlwmanifest_link');
    }
}
```

### removeFilter()

```php
protected function removeFilter(
    string $hook,
    callable $callback,
    int $priority = 10
): void
```

Удаляет фильтр.

#### Параметры
- `$hook` (string) - Имя фильтра
- `$callback` (callable) - Функция фильтрации
- `$priority` (int) - Приоритет (по умолчанию 10)

#### Пример использования
```php
class ContentCleanupHook extends AbstractHook
{
    public function register(): void
    {
        // Удаление стандартных фильтров
        $this->removeFilter('the_content', 'wpautop');
        $this->removeFilter('the_excerpt', 'wpautop');
    }
}
```

## Примеры использования

### 1. Хук настройки темы

```php
class ThemeSetupHook extends AbstractHook
{
    public function register(): void
    {
        $this->addAction('after_setup_theme', [$this, 'setup']);
        $this->addAction('widgets_init', [$this, 'widgets']);
        $this->addAction('wp_enqueue_scripts', [$this, 'assets']);
    }
    
    public function setup(): void
    {
        // Поддержка функций темы
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo');
        
        // Регистрация меню
        register_nav_menus([
            'primary' => 'Главное меню',
            'footer' => 'Меню в подвале'
        ]);
    }
    
    public function widgets(): void
    {
        register_sidebar([
            'name' => 'Сайдбар',
            'id' => 'sidebar-1'
        ]);
    }
    
    public function assets(): void
    {
        wp_enqueue_style('theme-style', get_stylesheet_uri());
    }
}
```

### 2. Хук для работы с контентом

```php
class ContentHook extends AbstractHook
{
    public function register(): void
    {
        // Фильтры контента
        $this->addFilter('the_content', [$this, 'sanitize'], 5);
        $this->addFilter('the_content', [$this, 'format'], 10);
        $this->addFilter('the_content', [$this, 'addWrappers'], 15);
        
        // Фильтры отрывка
        $this->addFilter('excerpt_length', [$this, 'excerptLength']);
        $this->addFilter('excerpt_more', [$this, 'excerptMore']);
    }
    
    public function sanitize(string $content): string
    {
        return wp_kses_post($content);
    }
    
    public function format(string $content): string
    {
        // Форматирование контента
        return str_replace('...', '…', $content);
    }
    
    public function addWrappers(string $content): string
    {
        return sprintf('<div class="content">%s</div>', $content);
    }
    
    public function excerptLength(int $length): int
    {
        return 20; // Новая длина отрывка
    }
    
    public function excerptMore(string $more): string
    {
        return '... <a href="' . get_permalink() . '">Читать далее</a>';
    }
}
```

### 3. Хук для админ-панели

```php
class AdminHook extends AbstractHook
{
    public function register(): void
    {
        if (!is_admin()) {
            return;
        }
        
        $this->addAction('admin_menu', [$this, 'registerPages']);
        $this->addAction('admin_init', [$this, 'registerSettings']);
        $this->addAction('admin_enqueue_scripts', [$this, 'adminAssets']);
    }
    
    public function registerPages(): void
    {
        add_theme_page(
            'Настройки темы',
            'Настройки темы',
            'manage_options',
            'theme-settings',
            [$this, 'renderSettingsPage']
        );
    }
    
    public function registerSettings(): void
    {
        register_setting('theme_options', 'theme_settings');
    }
    
    public function adminAssets(): void
    {
        wp_enqueue_style('admin-style', get_template_directory_uri() . '/admin.css');
    }
}
```

## Лучшие практики

### 1. Организация кода

```php
class ComplexHook extends AbstractHook
{
    public function register(): void
    {
        $this->registerActions();
        $this->registerFilters();
    }
    
    private function registerActions(): void
    {
        $this->addAction('init', [$this, 'initialize']);
        $this->addAction('admin_init', [$this, 'adminInitialize']);
    }
    
    private function registerFilters(): void
    {
        $this->addFilter('the_title', [$this, 'filterTitle']);
        $this->addFilter('the_content', [$this, 'filterContent']);
    }
}
```

### 2. Условная регистрация

```php
class ConditionalHook extends AbstractHook
{
    public function register(): void
    {
        // Регистрация только на определенных страницах
        if (is_single() || is_page()) {
            $this->addFilter('the_content', [$this, 'enhanceContent']);
        }
        
        // Регистрация только для определенных ролей
        if (current_user_can('edit_posts')) {
            $this->addAction('admin_bar_menu', [$this, 'addToolbarItems']);
        }
    }
}
```

### 3. Работа с приоритетами

```php
class PriorityHook extends AbstractHook
{
    // Константы для приоритетов
    private const PRIORITY_EARLY = 5;
    private const PRIORITY_NORMAL = 10;
    private const PRIORITY_LATE = 20;
    
    public function register(): void
    {
        // Ранняя инициализация
        $this->addAction('init', [$this, 'earlyInit'], self::PRIORITY_EARLY);
        
        // Стандартная инициализация
        $this->addAction('init', [$this, 'normalInit'], self::PRIORITY_NORMAL);
        
        // Поздняя инициализация
        $this->addAction('init', [$this, 'lateInit'], self::PRIORITY_LATE);
    }
}
```

## Заключение

`AbstractHook` предоставляет:
- Удобный интерфейс для работы с хуками WordPress
- Организованную структуру кода
- Гибкость в реализации
- Возможность условной регистрации
- Управление приоритетами


