# Контейнер зависимостей (Container)

## Обзор

`Container` - это ключевой компонент системы внедрения зависимостей (Dependency Injection) в мини-фреймворке Fasty, отвечающий за управление связями и разрешение зависимостей.

## Основные возможности

- 🔗 Регистрация и разрешение зависимостей
- 🧩 Поддержка синглтонов
- 🚀 Ленивая загрузка сервисов
- 🛠 Гибкое управление объектами

## Ключевые методы

### `bind(string $abstract, $concrete)`

Регистрация простой привязки:

```php
$container->bind('logger', function() {
    return new LoggerService();
});
```

### `singleton(string $abstract, $concrete)`

Регистрация синглтона с ленивой инициализацией:

```php
$container->singleton('config', function() {
    return new ConfigService();
});
```

### `get(string $abstract)`

Получение зарегистрированного сервиса:

```php
$config = $container->get('config');
```

### `make(string $concrete, array $parameters = [])`

Создание нового экземпляра с разрешением зависимостей:

```php
$service = $container->make(UserService::class, ['param1' => 'value']);
```

## Принципы работы

### Регистрация зависимостей

```php
// Простая регистрация
$container->bind('database', new DatabaseConnection());

// Регистрация с фабрикой
$container->singleton('cache', function($container) {
    return new CacheService($container->get('config'));
});
```

### Разрешение зависимостей

```php
// Получение сервиса
$database = $container->get('database');

// Проверка наличия сервиса
if ($container->has('cache')) {
    $cache = $container->get('cache');
}
```

## Преимущества

- 🔍 Централизованное управление зависимостями
- 🧠 Автоматическое разрешение сложных зависимостей
- 🔒 Контроль над жизненным циклом объектов

## Советы по использованию

1. Используйте синглтоны для сервисов, которые должны быть единственными
2. Применяйте ленивую загрузку для ресурсоемких сервисов
3. Группируйте связанные зависимости
4. Используйте интерфейсы для абстракции

## Пример продвинутого использования

```php
class UserService {
    private $database;
    private $logger;

    public function __construct(DatabaseConnection $database, LoggerService $logger) {
        $this->database = $database;
        $this->logger = $logger;
    }

    // Методы сервиса
}

// Регистрация зависимостей
$container->singleton(DatabaseConnection::class, function() {
    return new DatabaseConnection(/* параметры */);
});

$container->singleton(LoggerService::class, function() {
    return new LoggerService(/* параметры */);
});

$container->singleton(UserService::class, function($container) {
    return new UserService(
        $container->get(DatabaseConnection::class),
        $container->get(LoggerService::class)
    );
});
```

## Возможные проблемы

- Избегайте слишком сложных цепочек зависимостей
- Следите за производительностью при большом количестве сервисов
- Обрабатывайте возможные ошибки при создании объектов
- Используйте осторожно с глобальным состоянием

