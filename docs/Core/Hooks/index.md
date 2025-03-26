# Система хуков Fasty

## Введение

Система хуков Fasty предоставляет гибкий механизм расширения функциональности темы через события и фильтры. Она построена на следующих принципах:
- Слабая связанность компонентов
- Расширяемость функционала
- Совместимость с WordPress
- Типобезопасность и надежность

## Архитектура

Система хуков состоит из трех основных компонентов:

1. [`HookInterface`](./HookInterface.md) - интерфейс для создания хуков
2. [`AbstractHook`](./AbstractHook.md) - базовый класс для реализации хуков
3. [`HooksManager`](./HooksManager.md) - менеджер для управления хуками

## Основные концепции

### 1. События (Actions)

События позволяют выполнять код в определенные моменты:

```php
// Регистрация события
$hooks->addAction('theme.init', function() {
    // Инициализация темы
});

// Вызов события
$hooks->doAction('theme.init');
```

### 2. Фильтры (Filters)

Фильтры позволяют модифицировать данные:

```php
// Регистрация фильтра
$hooks->addFilter('content.format', function(string $content) {
    return wp_kses_post($content);
});

// Применение фильтра
$content = $hooks->applyFilter('content.format', $rawContent);
```

### 3. Приоритеты

Управление порядком выполнения:

```php
// Ранний обработчик
$hooks->addAction('theme.init', function() {
    // Базовая настройка
}, 5);

// Поздний обработчик
$hooks->addAction('theme.init', function() {
    // Дополнительная настройка
}, 15);
```

## Создание хуков

### 1. Простой хук

```php
class ThemeInitHook extends AbstractHook
{
    public function register(): void
    {
        $this->addAction('after_setup_theme', [$this, 'initialize']);
    }
    
    public function initialize(): void
    {
        // Настройка темы
        load_theme_textdomain('my-theme');
        add_theme_support('post-thumbnails');
    }
}
```

### 2. Хук с фильтрацией

```php
class ContentHook extends AbstractHook
{
    public function register(): void
    {
        $this->addFilter('the_content', [$this, 'formatContent']);
    }
    
    public function formatContent(string $content): string
    {
        // Форматирование контента
        $content = $this->sanitize($content);
        $content = $this->addWrappers($content);
        return $content;
    }
}
```

### 3. Условная регистрация

```php
class AdminHook extends AbstractHook
{
    public function register(): void
    {
        if (is_admin()) {
            $this->addAction('admin_menu', [$this, 'registerMenus']);
            $this->addAction('admin_init', [$this, 'registerSettings']);
        }
    }
}
```

## Использование менеджера хуков

### 1. Регистрация хуков

```php
class ThemeBootstrap
{
    private HooksManager $hooks;
    
    public function __construct()
    {
        $this->hooks = new HooksManager();
        
        // Регистрация хуков
        $this->hooks->register(new ThemeInitHook());
        $this->hooks->register(new ContentHook());
        $this->hooks->register(new AdminHook());
    }
}
```

### 2. Работа с событиями

```php
// Добавление обработчика
$hooks->addAction('theme.loaded', function() {
    // Тема загружена
});

// Удаление обработчика
$hooks->removeAction('theme.loaded', 'handler_name');

// Проверка существования
if ($hooks->hasAction('theme.loaded')) {
    // Событие зарегистрировано
}
```

### 3. Работа с фильтрами

```php
// Добавление фильтра
$hooks->addFilter('excerpt.length', function(int $length) {
    return 20; // Изменение длины отрывка
});

// Применение нескольких фильтров
$title = $hooks->applyFilters('post.title', $rawTitle, [
    'context' => 'single',
    'post_id' => get_the_ID()
]);
```

## Интеграция с WordPress

### 1. Хуки темы

```php
class ThemeHooks extends AbstractHook
{
    public function register(): void
    {
        // WordPress хуки
        $this->addAction('after_setup_theme', [$this, 'setup']);
        $this->addAction('widgets_init', [$this, 'widgets']);
        $this->addAction('wp_enqueue_scripts', [$this, 'assets']);
        
        // Собственные хуки
        $this->addAction('theme.init', [$this, 'initialize']);
        $this->addFilter('theme.config', [$this, 'filterConfig']);
    }
}
```

### 2. Хуки плагинов

```php
class PluginCompatibilityHook extends AbstractHook
{
    public function register(): void
    {
        // WooCommerce
        $this->addAction('woocommerce_init', [$this, 'setupStore']);
        
        // ACF
        $this->addFilter('acf/settings/path', [$this, 'setAcfPath']);
    }
}
```

## Отладка

### 1. Логирование хуков

```php
class DebugHook extends AbstractHook
{
    public function register(): void
    {
        if (WP_DEBUG) {
            $this->addAction('all', [$this, 'logHook']);
        }
    }
    
    public function logHook(string $hook): void
    {
        error_log("Выполнен хук: {$hook}");
    }
}
```

### 2. Профилирование

```php
class ProfilingHook extends AbstractHook
{
    private array $timers = [];
    
    public function register(): void
    {
        $this->addAction('theme.init', [$this, 'startTimer'], 0);
        $this->addAction('theme.init', [$this, 'stopTimer'], 999);
    }
    
    public function startTimer(string $hook): void
    {
        $this->timers[$hook] = microtime(true);
    }
    
    public function stopTimer(string $hook): void
    {
        $time = microtime(true) - $this->timers[$hook];
        error_log("Время выполнения {$hook}: {$time}s");
    }
}
```

## Лучшие практики

1. **Именование хуков**
   - Используйте пространства имен: `theme.init`, `theme.assets`
   - Добавляйте контекст: `post.save.before`, `post.save.after`

2. **Приоритеты**
   - Низкие числа (0-10) для важных операций
   - Средние числа (11-100) для стандартных операций
   - Высокие числа (100+) для поздних модификаций

3. **Документация**
   - Документируйте все хуки
   - Указывайте параметры и возвращаемые значения
   - Приводите примеры использования

4. **Безопасность**
   - Проверяйте права доступа
   - Валидируйте данные
   - Используйте nonce для форм

## Заключение

Система хуков Fasty предоставляет:
- Гибкий механизм расширения
- Совместимость с WordPress
- Типобезопасность
- Удобство отладки
- Масштабируемость 