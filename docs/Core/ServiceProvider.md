# Интерфейс ServiceProvider

## Введение

`ServiceProvider` - это интерфейс, определяющий контракт для всех сервис-провайдеров в фреймворке Fasty. Сервис-провайдеры являются центральным местом для конфигурации вашего приложения и регистрации различных компонентов темы.

## Концепция сервис-провайдеров

Сервис-провайдеры решают несколько важных задач:
1. **Организация кода** - группировка связанной функциональности
2. **Управление зависимостями** - регистрация сервисов в контейнере
3. **Инициализация компонентов** - настройка частей темы
4. **Отложенная загрузка** - загрузка компонентов по требованию

## Методы интерфейса

### register()

```php
public function register(): void;
```

Метод `register()` используется для регистрации сервисов в контейнере. В этом методе вы должны:
- Регистрировать привязки в контейнере
- Настраивать базовые параметры
- Определять зависимости

#### Пример регистрации:
```php
public function register(): void
{
    // Регистрация простого сервиса
    $this->bind('theme.menu', function() {
        return new MenuService();
    });
    
    // Регистрация синглтона
    $this->singleton('theme.config', function() {
        return new ConfigService();
    });
}
```

### boot()

```php
public function boot(): void;
```

Метод `boot()` вызывается после того, как все сервис-провайдеры зарегистрированы. Здесь вы можете:
- Использовать зарегистрированные сервисы
- Добавлять хуки WordPress
- Инициализировать компоненты

#### Пример использования:
```php
public function boot(): void
{
    // Получение зарегистрированного сервиса
    $menu = $this->get('theme.menu');
    
    // Добавление хуков
    add_action('init', [$menu, 'registerMenus']);
    
    // Инициализация компонентов
    $this->initializeWidgets();
}
```

### provides()

```php
public function provides(): array;
```

Метод `provides()` возвращает массив сервисов, предоставляемых провайдером. Используется для:
- Отложенной загрузки провайдеров
- Документирования доступных сервисов
- Управления зависимостями

#### Пример реализации:
```php
public function provides(): array
{
    return [
        'theme.menu',
        'theme.widgets',
        'theme.customizer'
    ];
}
```

## Лучшие практики

### 1. Организация кода

```php
class ThemeServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Группировка связанных сервисов
        $this->registerMenus();
        $this->registerWidgets();
        $this->registerCustomizer();
    }
    
    private function registerMenus(): void
    {
        $this->singleton('theme.menu', MenuService::class);
    }
}
```

### 2. Отложенная загрузка

```php
class HeavyFeatureProvider extends AbstractServiceProvider
{
    public function provides(): array
    {
        return ['heavy.feature'];
    }
    
    public function register(): void
    {
        $this->lazy('heavy.feature', function() {
            return new HeavyFeature();
        });
    }
}
```

### 3. Управление зависимостями

```php
class PostsProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Регистрация с зависимостями
        $this->bind('theme.posts', function() {
            return new PostsService(
                $this->get('theme.cache'),
                $this->get('theme.config')
            );
        });
    }
}
```

## Интеграция с WordPress

### Хуки и фильтры

```php
class ThemeProvider extends AbstractServiceProvider
{
    public function boot(): void
    {
        // Инициализация темы
        add_action('after_setup_theme', function() {
            $this->get('theme.setup')->initialize();
        });
        
        // Регистрация меню
        add_action('init', function() {
            $this->get('theme.menu')->register();
        });
        
        // Фильтрация контента
        add_filter('the_content', function($content) {
            return $this->get('theme.content')->process($content);
        });
    }
}
```

### Условная регистрация

```php
class AdminProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        if (is_admin()) {
            $this->registerAdminServices();
        }
    }
    
    private function registerAdminServices(): void
    {
        $this->singleton('admin.pages', AdminPagesService::class);
        $this->singleton('admin.settings', SettingsService::class);
    }
}
```

## Отладка и разработка

### Логирование

```php
class DebugProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        if (WP_DEBUG) {
            $this->debug('Регистрация отладочных сервисов...');
            $this->singleton('debug.logger', DebugLogger::class);
        }
    }
}
```

### Тестирование

```php
class TestableProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        // Легко тестируемая регистрация
        $this->bind('service.interface', ConcreteService::class);
        
        // Возможность подмены в тестах
        $this->singleton('service.config', function() {
            return defined('TESTING') 
                ? new TestConfig() 
                : new ProductionConfig();
        });
    }
}
```

## Заключение

Сервис-провайдеры - это мощный инструмент для:
- Организации кода темы
- Управления зависимостями
- Отложенной загрузки
- Тестируемости
- Масштабируемости

Используя их правильно, вы создаете чистую и поддерживаемую архитектуру темы WordPress.


