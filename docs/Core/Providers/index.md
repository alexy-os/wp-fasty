# Провайдеры в Fasty

Провайдеры в Fasty - это классы, отвечающие за регистрацию и инициализацию различных компонентов темы. Они являются центральным местом для настройки сервисов, хуков, ассетов и других элементов темы.

## Основные концепции

- Централизованная регистрация компонентов
- Управление зависимостями
- Отложенная загрузка
- Условная регистрация
- Модульность

## Доступные провайдеры

### [ThemeServiceProvider](./ThemeServiceProvider.md)
Основной провайдер темы, отвечающий за базовую настройку и регистрацию основных сервисов.

### [HooksProvider](./HooksProvider.md)
Провайдер для регистрации хуков WordPress, обеспечивающий централизованное управление действиями и фильтрами.

### [AssetsServiceProvider](./AssetsServiceProvider.md)
Провайдер для управления ассетами темы (CSS, JavaScript, изображения и другие медиа-файлы).

## Архитектура

```php
namespace Fasty\Core\Providers;

interface ServiceProviderInterface
{
    public function register(): void;
    public function boot(): void;
}

abstract class AbstractServiceProvider implements ServiceProviderInterface
{
    protected Container $container;
    
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
}
```

## Жизненный цикл

1. **Регистрация провайдера**
   ```php
   $app->register(new ThemeServiceProvider($container));
   ```

2. **Фаза регистрации**
   ```php
   public function register(): void
   {
       // Регистрация сервисов
       $this->container->singleton(ThemeService::class);
   }
   ```

3. **Фаза загрузки**
   ```php
   public function boot(): void
   {
       // Инициализация компонентов
       $this->container->get(ThemeService::class)->initialize();
   }
   ```

## Примеры использования

### 1. Базовый провайдер

```php
class BasicServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Регистрация сервисов
        $this->container->singleton(ConfigService::class);
        $this->container->singleton(LoggerService::class);
    }
    
    public function boot(): void
    {
        // Инициализация сервисов
        $config = $this->container->get(ConfigService::class);
        $logger = $this->container->get(LoggerService::class);
        
        // Настройка
        $config->load();
        $logger->initialize();
    }
}
```

### 2. Условная регистрация

```php
class ConditionalProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Регистрация только при определенных условиях
        if (is_admin()) {
            $this->container->singleton(AdminService::class);
        }
        
        if (class_exists('WooCommerce')) {
            $this->container->singleton(WooCommerceService::class);
        }
    }
}
```

### 3. Отложенная загрузка

```php
class DeferredProvider extends AbstractServiceProvider
{
    protected array $deferred = [
        SearchService::class,
        CacheService::class
    ];
    
    public function register(): void
    {
        $this->container->singleton(SearchService::class, function () {
            return new SearchService();
        });
        
        $this->container->singleton(CacheService::class, function () {
            return new CacheService();
        });
    }
    
    public function provides(): array
    {
        return $this->deferred;
    }
}
```

## Лучшие практики

### 1. Группировка сервисов

```php
class ModularProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->registerCore();
        $this->registerAdmin();
        $this->registerFrontend();
    }
    
    private function registerCore(): void
    {
        $this->container->singleton(ConfigService::class);
        $this->container->singleton(LoggerService::class);
    }
    
    private function registerAdmin(): void
    {
        if (is_admin()) {
            $this->container->singleton(AdminService::class);
            $this->container->singleton(CustomizerService::class);
        }
    }
    
    private function registerFrontend(): void
    {
        $this->container->singleton(AssetsService::class);
        $this->container->singleton(MenuService::class);
    }
}
```

### 2. Обработка зависимостей

```php
class DependentProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Проверка зависимостей
        if (!$this->checkDependencies()) {
            return;
        }
        
        // Регистрация сервисов
        $this->container->singleton(FeatureService::class);
    }
    
    private function checkDependencies(): bool
    {
        if (!class_exists('RequiredPlugin')) {
            add_action('admin_notices', function () {
                echo '<div class="error"><p>Required plugin is missing!</p></div>';
            });
            return false;
        }
        
        return true;
    }
}
```

### 3. Конфигурация сервисов

```php
class ConfigurableProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(MailService::class, function () {
            $service = new MailService();
            
            // Загрузка конфигурации
            $config = $this->container->get(ConfigService::class);
            
            // Настройка сервиса
            $service->setHost($config->get('mail.host'));
            $service->setPort($config->get('mail.port'));
            $service->setEncryption($config->get('mail.encryption'));
            
            return $service;
        });
    }
}
```

## Заключение

Провайдеры в Fasty предоставляют:
- Централизованное управление сервисами
- Гибкую систему регистрации компонентов
- Поддержку отложенной загрузки
- Управление зависимостями
- Модульную архитектуру
- Условную регистрацию сервисов 