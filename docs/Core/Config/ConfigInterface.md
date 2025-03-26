# Интерфейс ConfigInterface

`ConfigInterface` определяет контракт для работы с конфигурациями в Fasty. Этот интерфейс обеспечивает единый способ доступа к настройкам темы.

## Методы

### get()

```php
public function get(string $key, mixed $default = null): mixed
```

Получает значение конфигурации по ключу.

#### Параметры
- `$key` (string) - Ключ конфигурации в формате точечной нотации (например, 'theme.colors.primary')
- `$default` (mixed) - Значение по умолчанию, если ключ не найден

#### Возвращает
- `mixed` - Значение конфигурации или значение по умолчанию

#### Пример использования
```php
// Получение значения с значением по умолчанию
$debug = $config->get('app.debug', false);

// Получение вложенного значения
$primaryColor = $config->get('theme.colors.primary', '#000000');

// Получение массива
$menuItems = $config->get('navigation.main.items', []);
```

### set()

```php
public function set(string $key, mixed $value): void
```

Устанавливает значение конфигурации.

#### Параметры
- `$key` (string) - Ключ конфигурации в формате точечной нотации
- `$value` (mixed) - Значение для установки

#### Пример использования
```php
// Установка простого значения
$config->set('app.name', 'My Theme');

// Установка вложенного значения
$config->set('theme.colors.primary', '#007bff');

// Установка массива
$config->set('social.networks', [
    'twitter' => '@username',
    'facebook' => 'facebook.com/page'
]);
```

### has()

```php
public function has(string $key): bool
```

Проверяет существование ключа конфигурации.

#### Параметры
- `$key` (string) - Ключ конфигурации для проверки

#### Возвращает
- `bool` - `true` если ключ существует, иначе `false`

#### Пример использования
```php
// Проверка существования конфигурации
if ($config->has('theme.features.customizer')) {
    // Использование настроек кастомайзера
}

// Проверка вложенного ключа
if ($config->has('social.networks.twitter')) {
    // Доступ к настройкам Twitter
}
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
    // Загрузка основной конфигурации
    $config->load('config/app.php');
    
    // Загрузка конфигурации темы
    $config->load('config/theme.php');
} catch (ConfigurationException $e) {
    error_log("Ошибка загрузки конфигурации: " . $e->getMessage());
}
```

### saveCache()

```php
public function saveCache(): void
```

Сохраняет текущую конфигурацию в кэш.

#### Пример использования
```php
// Сохранение конфигурации в кэш после всех загрузок
$config->load('config/app.php');
$config->load('config/theme.php');
$config->saveCache();
```

### loadCache()

```php
public function loadCache(): void
```

Загружает конфигурацию из кэша.

#### Возвращает
- `bool` - `true` если кэш успешно загружен, иначе `false`

#### Пример использования
```php
// Попытка загрузки из кэша
if (!$config->loadCache()) {
    // Кэш не найден, загрузка из файлов
    $config->load('config/app.php');
    $config->saveCache();
}
```

### clearCache()

```php
public function clearCache(): void
```

Очищает кэш конфигурации.

#### Пример использования
```php
// Очистка кэша при обновлении темы
add_action('after_theme_update', function() use ($config) {
    $config->clearCache();
});
```

## Интеграция с WordPress

### Использование в хуках

```php
class ThemeSetup
{
    private ConfigInterface $config;
    
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        add_action('after_setup_theme', [$this, 'setupTheme']);
    }
    
    public function setupTheme(): void
    {
        // Загрузка текстового домена
        load_theme_textdomain(
            $this->config->get('theme.textdomain', 'my-theme')
        );
        
        // Поддержка миниатюр
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size(
            $this->config->get('theme.thumbnails.width', 1200),
            $this->config->get('theme.thumbnails.height', 900)
        );
    }
}
```

### Использование в виджетах

```php
class ThemeWidget extends WP_Widget
{
    private ConfigInterface $config;
    
    public function __construct(ConfigInterface $config)
    {
        $this->config = $config;
        parent::__construct(
            $this->config->get('widgets.social.id'),
            $this->config->get('widgets.social.name'),
            $this->config->get('widgets.social.options', [])
        );
    }
}
```


