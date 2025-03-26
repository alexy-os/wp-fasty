# LoggerTrait

`LoggerTrait` - это трейт, предоставляющий классам возможность логирования в Fasty. Он обеспечивает единый интерфейс для записи логов во всех компонентах системы.

## Назначение

- Единый интерфейс логирования
- Уровни логирования
- Контекстное логирование
- Форматирование сообщений
- Обработка ошибок

## Методы

### setLogger()

```php
public function setLogger(LoggerInterface $logger): void
```

Устанавливает логгер.

#### Параметры
- `$logger` (LoggerInterface) - Экземпляр логгера

#### Пример использования
```php
class ThemeService
{
    use LoggerTrait;
    
    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }
}
```

### log()

```php
protected function log(string $level, string $message, array $context = []): void
```

Записывает сообщение в лог.

#### Параметры
- `$level` (string) - Уровень логирования (debug, info, warning, error, critical)
- `$message` (string) - Сообщение для записи
- `$context` (array) - Дополнительный контекст [опционально]

#### Пример использования
```php
class AdminService
{
    use LoggerTrait;
    
    public function process(): void
    {
        $this->log('info', 'Начало обработки', [
            'time' => time(),
            'user' => wp_get_current_user()->ID
        ]);
    }
}
```

## Примеры использования

### 1. Базовое логирование

```php
class PostService
{
    use LoggerTrait;
    
    public function createPost(array $data): void
    {
        try {
            // Логирование начала операции
            $this->log('info', 'Создание поста', [
                'title' => $data['title']
            ]);
            
            // Создание поста
            $postId = wp_insert_post($data);
            
            // Логирование успеха
            $this->log('info', 'Пост создан', [
                'post_id' => $postId
            ]);
        } catch (Exception $e) {
            // Логирование ошибки
            $this->log('error', 'Ошибка создания поста', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            throw $e;
        }
    }
}
```

### 2. Уровни логирования

```php
class SecurityService
{
    use LoggerTrait;
    
    public function authenticate(string $username, string $password): void
    {
        // Отладочная информация
        $this->log('debug', 'Попытка аутентификации', [
            'username' => $username
        ]);
        
        try {
            $user = wp_authenticate($username, $password);
            
            if (is_wp_error($user)) {
                // Предупреждение
                $this->log('warning', 'Неудачная попытка входа', [
                    'username' => $username,
                    'error' => $user->get_error_message()
                ]);
                
                throw new Exception($user->get_error_message());
            }
            
            // Информация
            $this->log('info', 'Успешная аутентификация', [
                'user_id' => $user->ID
            ]);
        } catch (Exception $e) {
            // Критическая ошибка
            $this->log('critical', 'Ошибка аутентификации', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }
}
```

### 3. Контекстное логирование

```php
class WooCommerceService
{
    use LoggerTrait;
    
    public function processOrder(WC_Order $order): void
    {
        $context = [
            'order_id' => $order->get_id(),
            'status' => $order->get_status(),
            'total' => $order->get_total()
        ];
        
        // Начало обработки
        $this->log('info', 'Обработка заказа', $context);
        
        try {
            // Проверка наличия
            $this->checkStock($order);
            
            // Обработка оплаты
            $this->processPayment($order);
            
            // Обновление статуса
            $order->update_status('processing');
            
            // Успешное завершение
            $this->log('info', 'Заказ обработан', array_merge($context, [
                'new_status' => 'processing'
            ]));
        } catch (Exception $e) {
            // Ошибка обработки
            $this->log('error', 'Ошибка обработки заказа', array_merge($context, [
                'error' => $e->getMessage()
            ]));
            
            throw $e;
        }
    }
}
```

## Лучшие практики

### 1. Форматирование сообщений

```php
trait FormattedLoggerTrait
{
    use LoggerTrait;
    
    protected function logf(string $level, string $message, array $args = [], array $context = []): void
    {
        $formattedMessage = vsprintf($message, $args);
        $this->log($level, $formattedMessage, $context);
    }
}

class Service
{
    use FormattedLoggerTrait;
    
    public function process(int $id, string $type): void
    {
        $this->logf(
            'info',
            'Обработка %s с ID %d',
            [$type, $id],
            ['extra' => 'data']
        );
    }
}
```

### 2. Группировка логов

```php
trait GroupedLoggerTrait
{
    use LoggerTrait;
    
    private string $currentGroup = '';
    
    protected function beginGroup(string $name): void
    {
        $this->currentGroup = $name;
        $this->log('info', "=== Начало {$name} ===");
    }
    
    protected function endGroup(): void
    {
        $this->log('info', "=== Конец {$this->currentGroup} ===");
        $this->currentGroup = '';
    }
    
    protected function groupLog(string $level, string $message, array $context = []): void
    {
        $groupedMessage = $this->currentGroup ? "[{$this->currentGroup}] {$message}" : $message;
        $this->log($level, $groupedMessage, $context);
    }
}

class ImportService
{
    use GroupedLoggerTrait;
    
    public function import(): void
    {
        $this->beginGroup('Импорт');
        
        try {
            $this->groupLog('info', 'Загрузка данных');
            // Импорт
            
            $this->groupLog('info', 'Обработка данных');
            // Обработка
            
            $this->groupLog('info', 'Сохранение данных');
            // Сохранение
        } finally {
            $this->endGroup();
        }
    }
}
```

### 3. Обработка ошибок

```php
trait SafeLoggerTrait
{
    use LoggerTrait;
    
    protected function safeLog(string $level, string $message, array $context = []): void
    {
        try {
            $this->log($level, $message, $context);
        } catch (Exception $e) {
            // Запись в системный лог
            error_log(sprintf(
                "Ошибка логирования: %s. Сообщение: %s, Контекст: %s",
                $e->getMessage(),
                $message,
                json_encode($context)
            ));
        }
    }
}

class Service
{
    use SafeLoggerTrait;
    
    public function process(): void
    {
        try {
            // Обработка
            $this->safeLog('info', 'Процесс выполнен');
        } catch (Exception $e) {
            $this->safeLog('error', 'Ошибка процесса', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## Заключение

`LoggerTrait` предоставляет:
- Единый интерфейс логирования
- Поддержку различных уровней логов
- Контекстное логирование
- Форматирование сообщений
- Группировку логов
- Безопасное логирование


