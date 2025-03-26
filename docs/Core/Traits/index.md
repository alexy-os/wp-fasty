# Трейты в Fasty

Трейты в Fasty - это переиспользуемые компоненты, которые добавляют общую функциональность классам. Они позволяют избежать дублирования кода и обеспечивают единообразное поведение компонентов системы.

## Основные концепции

- Переиспользование кода
- Горизонтальная композиция
- Внедрение зависимостей
- Логирование
- Управление состоянием

## Доступные трейты

### [ContainerAwareTrait](./ContainerAwareTrait.md)
Трейт для работы с контейнером зависимостей, предоставляющий доступ к сервисам и компонентам.

### [LoggerTrait](./LoggerTrait.md)
Трейт для логирования, обеспечивающий единый интерфейс для записи логов во всех компонентах.

## Архитектура

```php
namespace Fasty\Core\Traits;

trait ContainerAwareTrait
{
    protected Container $container;
    
    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }
}

trait LoggerTrait
{
    protected LoggerInterface $logger;
    
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }
}
```

## Использование трейтов

### 1. Базовое использование

```php
class ThemeService
{
    use ContainerAwareTrait;
    use LoggerTrait;
    
    public function initialize(): void
    {
        // Доступ к контейнеру
        $config = $this->container->get(ConfigService::class);
        
        // Логирование
        $this->logger->info('Инициализация темы');
        
        try {
            // Инициализация
            $this->setup($config);
        } catch (Exception $e) {
            $this->logger->error('Ошибка инициализации', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

### 2. Композиция трейтов

```php
trait ConfigAwareTrait
{
    use ContainerAwareTrait;
    
    protected function getConfig(): ConfigInterface
    {
        return $this->container->get(ConfigService::class);
    }
}

class AdminService
{
    use ConfigAwareTrait;
    use LoggerTrait;
    
    public function setup(): void
    {
        $config = $this->getConfig();
        
        $this->logger->info('Настройка админки', [
            'config' => $config->get('admin')
        ]);
    }
}
```

### 3. Разрешение конфликтов

```php
trait DatabaseLoggerTrait
{
    public function log(string $message): void
    {
        // Логирование в базу данных
    }
}

trait FileLoggerTrait
{
    public function log(string $message): void
    {
        // Логирование в файл
    }
}

class Service
{
    use DatabaseLoggerTrait, FileLoggerTrait {
        DatabaseLoggerTrait::log insteadof FileLoggerTrait;
        FileLoggerTrait::log as logToFile;
    }
    
    public function process(): void
    {
        // Логирование в БД
        $this->log('Процесс начат');
        
        // Логирование в файл
        $this->logToFile('Процесс начат');
    }
}
```

## Лучшие практики

### 1. Инициализация трейтов

```php
trait InitializableTrait
{
    protected bool $initialized = false;
    
    protected function initialize(): void
    {
        if ($this->initialized) {
            return;
        }
        
        $this->doInitialize();
        $this->initialized = true;
    }
    
    abstract protected function doInitialize(): void;
}

class Service
{
    use InitializableTrait;
    
    protected function doInitialize(): void
    {
        // Инициализация сервиса
    }
    
    public function process(): void
    {
        $this->initialize();
        // Обработка
    }
}
```

### 2. Проверка зависимостей

```php
trait DependencyAwareTrait
{
    protected function checkDependencies(array $required): void
    {
        foreach ($required as $class) {
            if (!class_exists($class)) {
                throw new RuntimeException(
                    "Отсутствует обязательная зависимость: {$class}"
                );
            }
        }
    }
}

class WooCommerceService
{
    use DependencyAwareTrait;
    
    public function __construct()
    {
        $this->checkDependencies([
            'WooCommerce',
            'WC_Product',
            'WC_Order'
        ]);
    }
}
```

### 3. Состояние и валидация

```php
trait StatefulTrait
{
    protected array $state = [];
    
    protected function setState(string $key, $value): void
    {
        $this->validateState($key, $value);
        $this->state[$key] = $value;
    }
    
    protected function getState(string $key)
    {
        if (!isset($this->state[$key])) {
            throw new RuntimeException("Неизвестный ключ состояния: {$key}");
        }
        
        return $this->state[$key];
    }
    
    abstract protected function validateState(string $key, $value): void;
}

class ThemeCustomizer
{
    use StatefulTrait;
    
    protected function validateState(string $key, $value): void
    {
        $validators = [
            'color' => 'is_string',
            'size' => 'is_int',
            'enabled' => 'is_bool'
        ];
        
        if (!isset($validators[$key])) {
            throw new RuntimeException("Неподдерживаемый ключ: {$key}");
        }
        
        if (!$validators[$key]($value)) {
            throw new RuntimeException("Неверный тип для {$key}");
        }
    }
}
```

## Заключение

Трейты в Fasty предоставляют:
- Переиспользуемые компоненты
- Гибкую композицию функциональности
- Управление зависимостями
- Единообразное логирование
- Безопасное управление состоянием
- Валидацию данных 