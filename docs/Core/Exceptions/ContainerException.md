# Класс ContainerException

`ContainerException` - исключение, возникающее при ошибках в контейнере зависимостей.

## Конструктор

```php
public function __construct(
    string $errorType,
    string $subject,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null
)
```

#### Параметры
- `$errorType` (string) - Тип ошибки контейнера (например, 'binding', 'resolution')
- `$subject` (string) - Объект ошибки (класс/метод)
- `$message` (string) - Дополнительное сообщение об ошибке
- `$code` (int) - Код ошибки
- `$previous` (?\Throwable) - Предыдущее исключение

## Примеры использования

### 1. Ошибка разрешения зависимости

```php
class Container
{
    public function make(string $abstract)
    {
        if (!$this->has($abstract)) {
            throw new ContainerException(
                'resolution',
                $abstract,
                'Не удалось разрешить зависимость'
            );
        }
        
        // Создание экземпляра
    }
}
```

### 2. Циклическая зависимость

```php
class DependencyResolver
{
    public function resolve(string $class): object
    {
        if ($this->hasCircularDependency($class)) {
            throw new ContainerException(
                'circular_dependency',
                $class,
                'Обнаружена циклическая зависимость'
            );
        }
        
        // Разрешение зависимости
    }
}
```

### 3. Обработка исключения

```php
try {
    $service = $container->make(UserService::class);
} catch (ContainerException $e) {
    error_log(sprintf(
        'Ошибка контейнера [%s - %s]: %s',
        $e->getErrorType(),
        $e->getSubject(),
        $e->getMessage()
    ));
    
    // Использование альтернативной реализации
    $service = new DefaultUserService();
}
```