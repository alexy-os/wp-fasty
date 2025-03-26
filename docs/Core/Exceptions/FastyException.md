# Класс FastyException

`FastyException` - это базовый класс исключений фреймворка Fasty, от которого наследуются все остальные исключения.

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
- `$entityType` (string) - Тип сущности, вызвавшей ошибку (например, 'config', 'service')
- `$entityId` (string) - Идентификатор сущности
- `$message` (string) - Сообщение об ошибке
- `$code` (int) - Код ошибки
- `$previous` (?\Throwable) - Предыдущее исключение

## Методы

### getEntityType()

```php
public function getEntityType(): string
```

Возвращает тип сущности, вызвавшей ошибку.

#### Пример использования
```php
try {
    // Код, который может вызвать исключение
} catch (FastyException $e) {
    error_log("Ошибка в компоненте: " . $e->getEntityType());
}
```

### getEntityId()

```php
public function getEntityId(): string
```

Возвращает идентификатор сущности.

#### Пример использования
```php
try {
    // Код, который может вызвать исключение
} catch (FastyException $e) {
    error_log(sprintf(
        "Ошибка в %s (ID: %s): %s",
        $e->getEntityType(),
        $e->getEntityId(),
        $e->getMessage()
    ));
}
```

## Пример использования

```php
class ThemeService
{
    public function loadComponent(string $id): void
    {
        if (!$this->exists($id)) {
            throw new FastyException(
                'component',
                $id,
                "Компонент темы не найден"
            );
        }
    }
}
```

