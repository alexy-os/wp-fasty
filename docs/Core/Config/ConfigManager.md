# Класс ConfigManager

`ConfigManager` - это основная реализация интерфейса `ConfigInterface`, которая обеспечивает управление конфигурациями темы.

## Свойства

```php
private const CACHE_PREFIX = 'fasty_config_';  // Префикс для ключей кэша
private const CACHE_TIME = DAY_IN_SECONDS;     // Время жизни кэша
```

## Конструктор

```php
public function __construct(string $configDir)
```

Создает новый экземпляр менеджера конфигураций.

#### Параметры
- `$configDir` (string) - Путь к директории с конфигурационными файлами

#### Пример использования
```php
$configManager = new ConfigManager(__DIR__ . '/config');
```

## Методы

### initialize()

```php
public function initialize(): void
```

Инициализирует конфигурации, загружая их из файлов или кэша.

#### Пример использования
```php
$configManager = new ConfigManager($configDir);
$configManager->initialize();
```

### loadAllConfigs()

```php
protected function loadAllConfigs(): void
```

Загружает все конфигурационные файлы из директории конфигураций.

#### Пример работы
```php
// Внутренний процесс загрузки конфигураций
protected function loadAllConfigs(): void
{
    foreach ($this->getConfigFiles() as $file) {
        $this->load($file);
    }
}
```

### getConfigFiles()

```php
protected function getConfigFiles(): array
```

Получает список всех конфигурационных файлов.

#### Возвращает
- `array` - Массив путей к конфигурационным файлам

#### Пример работы
```php
// Поиск конфигурационных файлов
$files = glob($this->configDir . '/*.php');
```

### load()

```php
public function load(string $file): void
```

Загружает конфигурацию из файла.

#### Параметры
- `$file` (string) - Путь к файлу конфигурации

#### Исключения
- `ConfigurationException` - если файл не существует или имеет неверный формат

#### Пример использования
```php
try {
    $configManager->load('config/app.php');
} catch (ConfigurationException $e) {
    // Обработка ошибки
}
```

### sanitizeConfig()

```php
protected function sanitizeConfig(mixed $config): mixed
```

Рекурсивно очищает значения конфигурации.

#### Параметры
- `$config` (mixed) - Конфигурация для очистки

#### Возвращает
- `mixed` - Очищенная конфигурация

#### Пример работы
```php
// Очистка значений конфигурации
protected function sanitizeConfig($config)
{
    if (is_array($config)) {
        return array_map([$this, 'sanitizeConfig'], $config);
    }
    return $this->sanitizeValue($config);
}
```

### sanitizeValue()

```php
protected function sanitizeValue(string $value): string
```

Очищает отдельное значение конфигурации.

#### Параметры
- `$value` (string) - Значение для очистки

#### Возвращает
- `string` - Очищенное значение

#### Пример использования
```php
// Очистка HTML из значений
$cleanValue = $this->sanitizeValue('<script>alert("xss")</script>');
// Результат: 'alert("xss")'
```

### get()

```php
public function get(string $key, mixed $default = null): mixed
```

Получает значение конфигурации по ключу.

#### Параметры
- `$key` (string) - Ключ конфигурации
- `$default` (mixed) - Значение по умолчанию

#### Возвращает
- `mixed` - Значение конфигурации или значение по умолчанию

#### Пример использования
```php
// Получение значений разных типов
$debug = $configManager->get('app.debug', false);
$colors = $configManager->get('theme.colors', []);
$title = $configManager->get('site.title', 'My Site');
```

### set()

```php
public function set(string $key, mixed $value): void
```

Устанавливает значение конфигурации.

#### Параметры
- `$key` (string) - Ключ конфигурации
- `$value` (mixed) - Значение для установки

#### Пример использования
```php
// Установка различных типов значений
$configManager->set('app.environment', 'production');
$configManager->set('theme.features', ['customizer', 'widgets']);
```

### has()

```php
public function has(string $key): bool
```

Проверяет существование ключа конфигурации.

#### Параметры
- `$key` (string) - Ключ для проверки

#### Возвращает
- `bool` - `true` если ключ существует

#### Пример использования
```php
if ($configManager->has('theme.custom_header')) {
    // Настройка пользовательского заголовка
}
```

### saveCache()

```php
public function saveCache(): void
```

Сохраняет конфигурации в кэш WordPress.

#### Пример использования
```php
// Сохранение в кэш после всех изменений
$configManager->set('theme.version', '1.0.0');
$configManager->saveCache();
```

### loadCache()

```php
public function loadCache(): bool
```

Загружает конфигурации из кэша.

#### Возвращает
- `bool` - Успешность загрузки из кэша

#### Пример использования
```php
if (!$configManager->loadCache()) {
    // Кэш не найден, загрузка из файлов
    $configManager->loadAllConfigs();
}
```

### clearCache()

```php
public function clearCache(): void
```

Очищает кэш конфигураций.

#### Пример использования
```php
// Очистка при обновлении темы
add_action('after_theme_update', function() use ($configManager) {
    $configManager->clearCache();
});
```

### all()

```php
public function all(): array
```

Получает все конфигурации.

#### Возвращает
- `array` - Массив всех конфигураций

#### Пример использования
```php
// Получение всех настроек
$allConfigs = $configManager->all();
```

## Примеры использования

### 1. Базовая настройка темы

```php
class ThemeBootstrap
{
    private ConfigManager $config;
    
    public function __construct()
    {
        $this->config = new ConfigManager(__DIR__ . '/config');
        $this->config->initialize();
        
        // Регистрация хуков
        add_action('after_setup_theme', [$this, 'setup']);
    }
    
    public function setup(): void
    {
        // Настройка темы из конфигурации
        $features = $this->config->get('theme.features', []);
        foreach ($features as $feature) {
            add_theme_support($feature);
        }
    }
}
```

### 2. Кэширование в продакшене

```php
class ConfigServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $config = new ConfigManager($this->app->configPath());
        
        if (wp_get_environment_type() === 'production') {
            if ($config->loadCache()) {
                return;
            }
        }
        
        $config->initialize();
        $config->saveCache();
        
        $this->app->instance('config', $config);
    }
}
```

### 3. Работа с конфигурациями в виджетах

```php
class SocialWidget extends WP_Widget
{
    private ConfigManager $config;
    
    public function __construct(ConfigManager $config)
    {
        $this->config = $config;
        
        parent::__construct(
            'social_widget',
            $this->config->get('widgets.social.title'),
            $this->config->get('widgets.social.options', [])
        );
    }
    
    public function widget($args, $instance): void
    {
        $networks = $this->config->get('social.networks', []);
        foreach ($networks as $network => $url) {
            echo $this->renderNetwork($network, $url);
        }
    }
}
```


