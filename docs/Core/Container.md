# Контейнер зависимостей (Container)

## Что такое контейнер зависимостей?

Контейнер зависимостей - это мощный инструмент для управления объектами и их зависимостями в приложении. Представьте его как "умный конструктор", который знает:
- Как создавать объекты
- Какие зависимости им нужны
- Как эти зависимости предоставить

## Основные концепции

### 1. Внедрение зависимостей (Dependency Injection)
Вместо создания зависимостей внутри класса, они передаются извне:

```php
// Плохо - жесткая зависимость
class PostService {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
}

// Хорошо - зависимость внедряется
class PostService {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
}
```

### 2. Инверсия управления (Inversion of Control)
Контейнер берет на себя управление созданием объектов:

```php
// Без контейнера
$db = new Database($config);
$cache = new Cache();
$posts = new PostService($db, $cache);

// С контейнером
$posts = $container->get(PostService::class);
```

## Использование контейнера

### Базовая регистрация

```php
// Регистрация простого значения
$container->bind('config.debug', true);

// Регистрация класса
$container->bind(Database::class, function($container) {
    return new Database('localhost', 'dbname');
});

// Регистрация интерфейса
$container->bind(
    CacheInterface::class, 
    RedisCache::class
);
```

### Получение сервисов

```php
// Получение по имени
$debug = $container->get('config.debug');

// Получение класса с автоматическим разрешением зависимостей
$db = $container->get(Database::class);
```

### Синглтоны

Синглтон гарантирует, что класс будет создан только один раз:

```php
$container->singleton(Cache::class, function() {
    return new RedisCache();
});

// Оба вызова вернут один и тот же объект
$cache1 = $container->get(Cache::class);
$cache2 = $container->get(Cache::class);
```

### Отложенная загрузка

Создание объекта только при первом обращении:

```php
$container->lazy('heavy.service', function() {
    return new HeavyService();
});
```

## Автоматическое разрешение зависимостей

Контейнер может автоматически определять и создавать зависимости:

```php
class UserService {
    public function __construct(
        Database $db,
        Cache $cache,
        Logger $logger
    ) {
        // ...
    }
}

// Контейнер автоматически создаст все зависимости
$userService = $container->make(UserService::class);
```

## Продвинутые возможности

### Контекстные привязки

```php
$container->bind(Logger::class, function($container) {
    return new FileLogger();
});

$container->when(UserService::class)
         ->needs(Logger::class)
         ->give(function() {
             return new DatabaseLogger();
         });
```

### Теги

Группировка сервисов по тегам:

```php
$container->tag(['mailer.smtp', 'mailer.sendgrid'], 'mailers');
$mailers = $container->tagged('mailers');
```

## Лучшие практики

1. **Избегайте сервис-локатора**
```php
// Плохо
class Service {
    public function method() {
        $db = Container::getInstance()->get('db');
    }
}

// Хорошо
class Service {
    private $db;
    
    public function __construct(Database $db) {
        $this->db = $db;
    }
}
```

2. **Используйте интерфейсы**
```php
interface CacheInterface { }
class RedisCache implements CacheInterface { }
class FileCache implements CacheInterface { }

$container->bind(CacheInterface::class, RedisCache::class);
```

3. **Документируйте зависимости**
```php
/**
 * @param Database $db База данных
 * @param CacheInterface $cache Система кеширования
 */
public function __construct(Database $db, CacheInterface $cache)
```

## Отладка

### Проверка существования сервиса
```php
if ($container->has('service.name')) {
    // ...
}
```

### Список зарегистрированных сервисов
```php
$bindings = $container->getBindings();
$instances = $container->getInstances();
```

## Обработка ошибок

Контейнер выбрасывает исключения в случае проблем:

- `NotFoundException`: Сервис не найден
- `ContainerException`: Ошибка при создании сервиса

```php
try {
    $service = $container->get('unknown.service');
} catch (NotFoundException $e) {
    // Обработка ошибки
}
```

## Интеграция с WordPress

```php
// Регистрация глобальных объектов WordPress
$container->singleton('wp.query', function() {
    global $wp_query;
    return $wp_query;
});

// Работа с хуками
$container->singleton('theme.hooks', function($container) {
    return new ThemeHooks(
        $container->get('wp.query'),
        $container->get(Cache::class)
    );
});
```

## Заключение

Контейнер зависимостей - это ключевой компонент современной архитектуры приложений. Он помогает:
- Управлять сложными зависимостями
- Улучшать тестируемость кода
- Следовать SOLID принципам
- Делать код более модульным

# Container

<!-- @doc-source: Container -->


## Methods

### bind
<!-- @doc-source: Container.bind -->
Dependency Injection Container
Manages service bindings and resolutions
/

namespace FastyChild\Core;

use FastyChild\Core\Exceptions\NotFoundException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Core\Traits\LoggerTrait;

class Container {
use LoggerTrait;

/**
Registered bindings

#### Parameters

- ``: abstract string
- ``: concrete mixed
- ``: shared bool

#### Returns



### singleton
<!-- @doc-source: Container.singleton -->
Register a singleton binding in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $concrete Concrete implementation or factory
- ``: abstract string
- ``: concrete mixed

#### Returns



### lazy
<!-- @doc-source: Container.lazy -->
Register a lazy service that will only be instantiated when needed

#### Parameters

- ``: string $abstract Abstract key
- ``: null \Closure $factory Factory function to create the service
- ``: bool $singleton Whether to treat as singleton
- ``: abstract string
- ``: factory \Closure
- ``: singleton bool

#### Returns



### instance
<!-- @doc-source: Container.instance -->
Register an existing instance in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: mixed $instance Concrete instance
- ``: abstract string
- ``: instance mixed

#### Returns



### get
<!-- @doc-source: Container.get -->
Resolve a binding from the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### resolveLazyService
<!-- @doc-source: Container.resolveLazyService -->
Resolve a lazy-loaded service

#### Parameters

- ``: string $abstract Service identifier
- ``: abstract string

#### Returns



### resolve
<!-- @doc-source: Container.resolve -->
Alias for get()

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### has
<!-- @doc-source: Container.has -->
Check if a binding exists in the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### make
<!-- @doc-source: Container.make -->
Make an instance of the given class with automatic dependency injection

#### Parameters

- ``: string $concrete Class name to instantiate
- ``: array <string, mixed> $parameters Additional parameters to pass to constructor
- ``: concrete string
- ``: parameters array

#### Returns



### call
<!-- @doc-source: Container.call -->
Call a method with automatic dependency injection

#### Parameters

- ``: object |string $instance Object instance or class name for static methods
- ``: string $method Method name to call
- ``: array <string, mixed> $parameters Additional parameters to pass to method
- ``: instance mixed
- ``: method string
- ``: parameters array

#### Returns



### getBindings
<!-- @doc-source: Container.getBindings -->
Get all registered bindings

#### Returns



### getInstances
<!-- @doc-source: Container.getInstances -->
Get all resolved instances

#### Returns



### unbind
<!-- @doc-source: Container.unbind -->
Remove a binding from the container

#### Parameters

- ``: string $abstract Abstract key
- ``: abstract string

#### Returns



### clear
<!-- @doc-source: Container.clear -->
Clear all bindings and instances

#### Returns



