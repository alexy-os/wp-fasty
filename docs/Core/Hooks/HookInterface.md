# Интерфейс HookInterface

`HookInterface` - это основной интерфейс для создания хуков в Fasty. Он определяет контракт, которому должны следовать все хуки в системе.

## Основные концепции

- Стандартизация регистрации хуков
- Управление идентификаторами хуков
- Контроль приоритетов выполнения
- Условная регистрация хуков

## Методы интерфейса

### register()

```php
public function register(): void;
```

Основной метод для регистрации хуков WordPress.

#### Пример реализации
```php
class ExampleHook implements HookInterface
{
    public function register(): void
    {
        add_action('init', [$this, 'initialize']);
        add_filter('the_content', [$this, 'filterContent']);
    }
}
```

### getIdentifier()

```php
public function getIdentifier(): string;
```

Возвращает уникальный идентификатор хука.

#### Пример реализации
```php
class ExampleHook implements HookInterface
{
    private $identifier;
    
    public function getIdentifier(): string
    {
        return $this->identifier ?? static::class;
    }
}
```

### setIdentifier()

```php
public function setIdentifier(string $identifier): self;
```

Устанавливает идентификатор хука.

#### Параметры
- `$identifier` (string) - Уникальный идентификатор

#### Пример реализации
```php
class ExampleHook implements HookInterface
{
    private $identifier;
    
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }
}
```

### getPriority()

```php
public function getPriority(): int;
```

Возвращает приоритет выполнения хука.

#### Пример реализации
```php
class ExampleHook implements HookInterface
{
    private $priority = 10;
    
    public function getPriority(): int
    {
        return $this->priority;
    }
}
```

### setPriority()

```php
public function setPriority(int $priority): self;
```

Устанавливает приоритет выполнения хука.

#### Параметры
- `$priority` (int) - Значение приоритета

#### Пример реализации
```php
class ExampleHook implements HookInterface
{
    private $priority = 10;
    
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
}
```

### canRegister()

```php
public function canRegister(): bool;
```

Определяет, может ли хук быть зарегистрирован.

#### Пример реализации
```php
class ExampleHook implements HookInterface
{
    public function canRegister(): bool
    {
        // Проверка условий регистрации
        return true;
    }
}
```

## Примеры использования

### 1. Базовая реализация

```php
class BasicHook implements HookInterface
{
    private $identifier;
    private $priority = 10;
    
    public function register(): void
    {
        add_action('init', [$this, 'initialize']);
    }
    
    public function getIdentifier(): string
    {
        return $this->identifier ?? static::class;
    }
    
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
    
    public function canRegister(): bool
    {
        return true;
    }
    
    public function initialize(): void
    {
        // Инициализация
    }
}
```

### 2. Условная регистрация

```php
class ConditionalHook implements HookInterface
{
    private $identifier;
    private $priority = 10;
    private $condition;
    
    public function __construct(callable $condition)
    {
        $this->condition = $condition;
    }
    
    public function register(): void
    {
        if ($this->canRegister()) {
            add_action('init', [$this, 'initialize']);
        }
    }
    
    public function canRegister(): bool
    {
        return call_user_func($this->condition);
    }
    
    // ... остальные методы интерфейса
}

// Использование
$adminHook = new ConditionalHook(function() {
    return is_admin();
});
```

### 3. Приоритетный хук

```php
class PriorityHook implements HookInterface
{
    private $identifier;
    private $priority;
    
    public function __construct(int $priority = 10)
    {
        $this->priority = $priority;
    }
    
    public function register(): void
    {
        add_action('init', [$this, 'earlyInitialize'], $this->getPriority());
    }
    
    // ... остальные методы интерфейса
}

// Использование
$earlyHook = new PriorityHook(5);
$lateHook = new PriorityHook(20);
```

## Лучшие практики

### 1. Использование трейтов для общего функционала

```php
trait HookIdentifierTrait
{
    private $identifier;
    
    public function getIdentifier(): string
    {
        return $this->identifier ?? static::class;
    }
    
    public function setIdentifier(string $identifier): self
    {
        $this->identifier = $identifier;
        return $this;
    }
}

trait HookPriorityTrait
{
    private $priority = 10;
    
    public function getPriority(): int
    {
        return $this->priority;
    }
    
    public function setPriority(int $priority): self
    {
        $this->priority = $priority;
        return $this;
    }
}

class OptimizedHook implements HookInterface
{
    use HookIdentifierTrait;
    use HookPriorityTrait;
    
    public function register(): void
    {
        // Регистрация хуков
    }
    
    public function canRegister(): bool
    {
        return true;
    }
}
```

### 2. Группировка связанных хуков

```php
class ThemeHook implements HookInterface
{
    use HookIdentifierTrait;
    use HookPriorityTrait;
    
    public function register(): void
    {
        // Регистрация хуков темы
        $this->registerSetup();
        $this->registerAssets();
        $this->registerMenus();
        $this->registerSidebars();
    }
    
    private function registerSetup(): void
    {
        add_action('after_setup_theme', [$this, 'setup']);
    }
    
    private function registerAssets(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }
    
    private function registerMenus(): void
    {
        add_action('init', [$this, 'registerMenus']);
    }
    
    private function registerSidebars(): void
    {
        add_action('widgets_init', [$this, 'registerSidebars']);
    }
    
    public function canRegister(): bool
    {
        return true;
    }
}
```

### 3. Документирование хуков

```php
/**
 * Хук для работы с SEO функционалом.
 *
 * Регистрирует фильтры и действия для:
 * - Мета-тегов
 * - Title
 * - Description
 * - Robots
 * - Sitemap
 */
class SEOHook implements HookInterface
{
    use HookIdentifierTrait;
    use HookPriorityTrait;
    
    public function register(): void
    {
        // Мета-теги
        add_action('wp_head', [$this, 'outputMetaTags'], 1);
        
        // Title
        add_filter('document_title_parts', [$this, 'filterTitle']);
        
        // Description
        add_filter('get_the_excerpt', [$this, 'filterExcerpt']);
        
        // Robots
        add_filter('wp_robots', [$this, 'filterRobots']);
        
        // Sitemap
        add_filter('wp_sitemaps_posts_query_args', [$this, 'filterSitemapArgs']);
    }
    
    public function canRegister(): bool
    {
        return true;
    }
}
```

## Заключение

`HookInterface` предоставляет:
- Стандартизированный способ создания хуков
- Гибкое управление приоритетами
- Возможность условной регистрации
- Поддержку идентификации хуков
- Основу для создания модульной системы хуков


