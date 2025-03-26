# ContainerAwareTrait

`ContainerAwareTrait` - это трейт, предоставляющий классам доступ к контейнеру зависимостей Fasty. Он обеспечивает единый способ внедрения и получения зависимостей во всех компонентах системы.

## Назначение

- Внедрение контейнера зависимостей
- Доступ к сервисам
- Ленивая загрузка зависимостей
- Управление жизненным циклом
- Тестируемость компонентов

## Методы

### setContainer()

```php
public function setContainer(Container $container): void
```

Устанавливает контейнер зависимостей.

#### Параметры
- `$container` (Container) - Экземпляр контейнера зависимостей

#### Пример использования
```php
class ThemeService
{
    use ContainerAwareTrait;
    
    public function __construct(Container $container)
    {
        $this->setContainer($container);
    }
}
```

### getContainer()

```php
protected function getContainer(): Container
```

Возвращает экземпляр контейнера зависимостей.

#### Возвращает
- `Container` - Экземпляр контейнера зависимостей

#### Исключения
- `RuntimeException` - Если контейнер не был установлен

#### Пример использования
```php
class ThemeService
{
    use ContainerAwareTrait;
    
    public function getConfig(): ConfigInterface
    {
        return $this->getContainer()->get(ConfigService::class);
    }
}
```

## Примеры использования

### 1. Базовое использование

```php
class AdminService
{
    use ContainerAwareTrait;
    
    public function initialize(): void
    {
        // Получение зависимостей
        $config = $this->getContainer()->get(ConfigService::class);
        $logger = $this->getContainer()->get(LoggerService::class);
        
        // Использование сервисов
        $logger->info('Инициализация админки', [
            'config' => $config->get('admin')
        ]);
    }
}
```

### 2. Ленивая загрузка

```php
class PostService
{
    use ContainerAwareTrait;
    
    private ?TaxonomyService $taxonomyService = null;
    
    protected function getTaxonomyService(): TaxonomyService
    {
        if ($this->taxonomyService === null) {
            $this->taxonomyService = $this->getContainer()->get(TaxonomyService::class);
        }
        
        return $this->taxonomyService;
    }
    
    public function registerTaxonomies(): void
    {
        $this->getTaxonomyService()->register([
            'portfolio_category',
            'portfolio_tag'
        ]);
    }
}
```

### 3. Условная загрузка

```php
class WooCommerceService
{
    use ContainerAwareTrait;
    
    public function setup(): void
    {
        // Проверка зависимостей
        if (!class_exists('WooCommerce')) {
            return;
        }
        
        // Загрузка сервисов
        $productService = $this->getContainer()->get(ProductService::class);
        $orderService = $this->getContainer()->get(OrderService::class);
        
        // Настройка
        $productService->registerTypes();
        $orderService->setupWorkflow();
    }
}
```

## Лучшие практики

### 1. Проверка контейнера

```php
trait SafeContainerAwareTrait
{
    use ContainerAwareTrait;
    
    protected function safeGet(string $id)
    {
        try {
            return $this->getContainer()->get($id);
        } catch (Exception $e) {
            throw new RuntimeException(
                "Ошибка получения сервиса {$id}: " . $e->getMessage()
            );
        }
    }
    
    protected function has(string $id): bool
    {
        return $this->getContainer()->has($id);
    }
}

class Service
{
    use SafeContainerAwareTrait;
    
    public function process(): void
    {
        if ($this->has(OptionalService::class)) {
            $service = $this->safeGet(OptionalService::class);
            $service->process();
        }
    }
}
```

### 2. Кэширование сервисов

```php
trait CachedContainerAwareTrait
{
    use ContainerAwareTrait;
    
    private array $services = [];
    
    protected function getCached(string $id)
    {
        if (!isset($this->services[$id])) {
            $this->services[$id] = $this->getContainer()->get($id);
        }
        
        return $this->services[$id];
    }
    
    protected function clearCache(): void
    {
        $this->services = [];
    }
}

class CacheableService
{
    use CachedContainerAwareTrait;
    
    public function process(): void
    {
        // Сервис будет загружен только один раз
        $config = $this->getCached(ConfigService::class);
        $logger = $this->getCached(LoggerService::class);
        
        // Использование сервисов
        $logger->info('Обработка', [
            'config' => $config->get('service')
        ]);
    }
}
```

### 3. Тестирование

```php
trait TestableContainerAwareTrait
{
    use ContainerAwareTrait;
    
    protected function mock(string $id, $mock): void
    {
        $this->getContainer()->set($id, $mock);
    }
    
    protected function reset(array $ids): void
    {
        foreach ($ids as $id) {
            $this->getContainer()->remove($id);
        }
    }
}

class TestableService
{
    use TestableContainerAwareTrait;
    
    public function test(): void
    {
        // Создание мока
        $loggerMock = $this->createMock(LoggerInterface::class);
        
        // Установка мока
        $this->mock(LoggerService::class, $loggerMock);
        
        // Тестирование
        $this->process();
        
        // Сброс моков
        $this->reset([LoggerService::class]);
    }
}
```

## Заключение

`ContainerAwareTrait` предоставляет:
- Стандартный способ внедрения зависимостей
- Ленивую загрузку сервисов
- Кэширование зависимостей
- Безопасный доступ к сервисам
- Поддержку тестирования
- Управление жизненным циклом


## Methods

### setContainer
<!-- @doc-source: ContainerAwareTrait.setContainer -->
Provides dependency injection container access to classes
/
trait ContainerAwareTrait
{
/**
Service container

#### Parameters

- ``: container Container

#### Returns



### getContainer
<!-- @doc-source: ContainerAwareTrait.getContainer -->
Get the container

#### Returns



### getService
<!-- @doc-source: ContainerAwareTrait.getService -->
Get service from container

#### Parameters

- ``: string $id Service identifier
- ``: bool $required Whether the service is required
- ``: id string
- ``: required bool

#### Returns



### getApplication
<!-- @doc-source: ContainerAwareTrait.getApplication -->
Get application instance

#### Returns



### hasService
<!-- @doc-source: ContainerAwareTrait.hasService -->
Check if a service exists in the container

#### Parameters

- ``: string $id Service identifier
- ``: id string

#### Returns



### makeInstance
<!-- @doc-source: ContainerAwareTrait.makeInstance -->
Create a new instance with automatic dependency injection

#### Parameters

- ``: string $class Class name
- ``: array $parameters Additional parameters
- ``: class string
- ``: parameters array

#### Returns



### call
<!-- @doc-source: ContainerAwareTrait.call -->
Call a method with automatic dependency injection

#### Parameters

- ``: object |string $instance Object instance or class name for static methods
- ``: string $method Method name
- ``: array $parameters Additional parameters
- ``: instance mixed
- ``: method string
- ``: parameters array

#### Returns



