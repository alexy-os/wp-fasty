# Интерфейс ServiceProvider

## Обзор

`ServiceProvider` - это ключевой интерфейс в архитектуре мини-фреймворка Fasty, определяющий контракт для всех сервис-провайдеров.

## Назначение

Интерфейс `ServiceProvider` обеспечивает стандартизированный подход к:
- Регистрации сервисов в контейнере зависимостей
- Инициализации и загрузке сервисов

## Структура интерфейса

```php
interface ServiceProvider {
    public function register(): void;
    public function boot(): void;
}
```

### Метод `register()`

- Отвечает за регистрацию сервисов в контейнере
- Вызывается перед инициализацией других сервисов
- Используется для подготовки и связывания зависимостей

### Метод `boot()`

- Вызывается после регистрации всех сервисов
- Используется для окончательной инициализации и запуска сервисов
- Позволяет выполнять действия, требующие наличия всех зависимостей

## Пример реализации

```php
class ExampleServiceProvider implements ServiceProvider {
    private $container;

    public function __construct(Container $container) {
        $this->container = $container;
    }

    public function register(): void {
        // Регистрация сервиса в контейнере
        $this->container->singleton('example', function() {
            return new ExampleService();
        });
    }

    public function boot(): void {
        // Инициализация сервиса после регистрации
        $exampleService = $this->container->get('example');
        $exampleService->initialize();
    }
}
```

## Ключевые принципы

- 🧩 Модульность: каждый провайдер отвечает за определенную часть функциональности
- 🔗 Инверсия зависимостей: сервисы регистрируются через контейнер
- 🚀 Отложенная инициализация: сервисы загружаются только когда это необходимо

## Советы по использованию

1. Держите провайдеры маленькими и focused
2. Используйте dependency injection
3. Минимизируйте логику в методах `register()` и `boot()`
4. Обрабатывайте возможные ошибки при регистрации сервисов

## Возможные проблемы

- Следите за порядком регистрации провайдеров
- Избегайте циклических зависимостей
- Обрабатывайте ошибки при создании сервисов

namespace FastyChild\Core;

interface ServiceProvider {
/**
Register services in the container

#### Returns



### boot
<!-- @doc-source: ServiceProvider.boot -->
Boot services after all providers are registered

#### Returns



