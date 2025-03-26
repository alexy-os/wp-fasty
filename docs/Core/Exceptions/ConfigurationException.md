# Класс ConfigurationException

`ConfigurationException` - исключение, возникающее при ошибках в конфигурации темы.

## Конструктор

```php
public function __construct(
    string $configKey,
    string $configValue,
    string $message = '',
    int $code = 0,
    ?\Throwable $previous = null
)
```

#### Параметры
- `$configKey` (string) - Ключ конфигурации, вызвавший ошибку
- `$configValue` (string) - Значение конфигурации
- `$message` (string) - Дополнительное сообщение об ошибке
- `$code` (int) - Код ошибки
- `$previous` (?\Throwable) - Предыдущее исключение

## Примеры использования

### 1. Проверка значения конфигурации

```php
class ThemeConfig
{
    public function validateColor(string $key, string $value): void
    {
        if (!preg_match('/^#[0-9a-f]{6}$/i', $value)) {
            throw new ConfigurationException(
                $key,
                $value,
                'Неверный формат цвета. Используйте HEX формат (#RRGGBB)'
            );
        }
    }
}
```

### 2. Загрузка конфигурационного файла

```php
class ConfigLoader
{
    public function load(string $file): array
    {
        if (!file_exists($file)) {
            throw new ConfigurationException(
                'config_file',
                $file,
                'Файл конфигурации не найден'
            );
        }

        $config = require $file;
        if (!is_array($config)) {
            throw new ConfigurationException(
                'config_format',
                $file,
                'Файл конфигурации должен возвращать массив'
            );
        }

        return $config;
    }
}
```

### 3. Обработка исключения

```php
try {
    $config->load('theme.php');
} catch (ConfigurationException $e) {
    error_log(sprintf(
        'Ошибка конфигурации [%s = %s]: %s',
        $e->getConfigKey(),
        $e->getConfigValue(),
        $e->getMessage()
    ));
    
    // Загрузка конфигурации по умолчанию
    $config->load('default.php');
}
```

