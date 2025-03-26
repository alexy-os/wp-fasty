# Система исключений Fasty

## Введение

Система исключений в Fasty построена на принципах ООП и обеспечивает:
- Иерархическую структуру исключений
- Типизацию ошибок
- Информативные сообщения
- Возможность отслеживания

## Иерархия исключений

```
Exception (PHP)
└── FastyException
    ├── ConfigurationException
    ├── ContainerException
    └── NotFoundException
```

## Базовое исключение (FastyException)

`FastyException` - это базовый класс для всех исключений фреймворка. Он добавляет:
- Контекст ошибки (тип сущности и идентификатор)
- Стандартизированные сообщения
- Возможность отслеживания цепочки ошибок

```php
try {
    // Код, который может вызвать ошибку
} catch (FastyException $e) {
    error_log(sprintf(
        "Ошибка в %s (ID: %s): %s",
        $e->getEntityType(),
        $e->getEntityId(),
        $e->getMessage()
    ));
}
```

## Специализированные исключения

### 1. ConfigurationException

Исключение для ошибок конфигурации:
- Неверные значения настроек
- Отсутствующие конфигурационные файлы
- Ошибки формата данных

```php
try {
    $config->load('invalid-config.php');
} catch (ConfigurationException $e) {
    // Доступ к деталям ошибки
    $configKey = $e->getConfigKey();
    $configValue = $e->getConfigValue();
}
```

### 2. ContainerException

Исключение для ошибок контейнера зависимостей:
- Циклические зависимости
- Отсутствующие привязки
- Ошибки создания объектов

```php
try {
    $container->make('service');
} catch (ContainerException $e) {
    // Информация об ошибке контейнера
    $errorType = $e->getErrorType();
    $subject = $e->getSubject();
}
```

### 3. NotFoundException

Исключение для отсутствующих сущностей:
- Сервисы
- Провайдеры
- Конфигурации
- Ресурсы

```php
try {
    $service = $container->get('non-existent');
} catch (NotFoundException $e) {
    // Детали отсутствующей сущности
    $type = $e->getEntityType(); // 'service'
    $id = $e->getEntityId();     // 'non-existent'
}
```

## Лучшие практики ООП

### 1. Принцип единой ответственности (SRP)

Каждый класс исключения отвечает за конкретный тип ошибки:
```php
class ConfigurationException extends FastyException
{
    private string $configKey;
    private mixed $configValue;
    
    public function __construct(string $configKey, mixed $configValue)
    {
        $this->configKey = $configKey;
        $this->configValue = $configValue;
        parent::__construct(
            'configuration',
            $configKey,
            "Неверное значение конфигурации"
        );
    }
}
```

### 2. Принцип открытости/закрытости (OCP)

Система исключений расширяема:
```php
// Создание специализированного исключения
class ValidationException extends FastyException
{
    private array $errors;
    
    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct('validation', '', 'Ошибка валидации');
    }
    
    public function getErrors(): array
    {
        return $this->errors;
    }
}
```

### 3. Принцип подстановки Лисков (LSP)

Все исключения могут использоваться взаимозаменяемо:
```php
try {
    // Код, который может вызвать различные исключения
} catch (FastyException $e) {
    // Общая обработка для всех исключений фреймворка
    $this->logger->error($e->getMessage(), [
        'type' => $e->getEntityType(),
        'id' => $e->getEntityId()
    ]);
}
```

## Обработка исключений

### 1. Глобальный обработчик

```php
class ExceptionHandler
{
    public function handle(\Throwable $e): void
    {
        if ($e instanceof FastyException) {
            // Специальная обработка для исключений фреймворка
            $this->handleFastyException($e);
        } else {
            // Обработка остальных исключений
            $this->handleGenericException($e);
        }
    }
}
```

### 2. Контекстная обработка

```php
class ThemeBootstrapper
{
    public function boot(): void
    {
        try {
            $this->loadConfiguration();
            $this->registerServices();
            $this->bootProviders();
        } catch (ConfigurationException $e) {
            // Обработка ошибок конфигурации
        } catch (ContainerException $e) {
            // Обработка ошибок контейнера
        } catch (FastyException $e) {
            // Обработка остальных ошибок фреймворка
        }
    }
}
```

## Заключение

Система исключений Fasty:
- Следует принципам ООП
- Обеспечивает типизацию ошибок
- Предоставляет контекстную информацию
- Упрощает отладку
- Позволяет создавать специализированные обработчики 