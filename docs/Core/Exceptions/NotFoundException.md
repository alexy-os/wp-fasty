# Класс NotFoundException

`NotFoundException` - исключение, возникающее когда запрашиваемая сущность не найдена.

## Конструктор

```php
public function __construct(
    string $entityType,
    string $entityId,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null
)
```

#### Параметры
- `$entityType` (string) - Тип искомой сущности (например, 'service', 'provider')
- `$entityId` (string) - Идентификатор сущности
- `$message` (string) - Дополнительное сообщение об ошибке
- `$code` (int) - Код ошибки
- `$previous` (?\Throwable) - Предыдущее исключение

## Примеры использования

### 1. Поиск сервиса

```php
class ServiceLocator
{
    public function getService(string $id): object
    {
        if (!isset($this->services[$id])) {
            throw new NotFoundException(
                'service',
                $id,
                'Сервис не зарегистрирован'
            );
        }
        
        return $this->services[$id];
    }
}
```

### 2. Загрузка провайдера

```php
class ProviderLoader
{
    public function load(string $provider): void
    {
        if (!class_exists($provider)) {
            throw new NotFoundException(
                'provider',
                $provider,
                'Класс провайдера не найден'
            );
        }
        
        // Загрузка провайдера
    }
}
```

### 3. Обработка исключения

```php
try {
    $service = $app->make('cache');
} catch (NotFoundException $e) {
    error_log(sprintf(
        'Не найден %s [%s]: %s',
        $e->getEntityType(),
        $e->getEntityId(),
        $e->getMessage()
    ));
    
    // Использование альтернативного сервиса
    $service = new FileCache();
}
```

