# Конфигурация провайдеров (providers.php)

## Связанные страницы

- [ServiceProvider](../Core/ServiceProvider.md)
- [Container](../Core/Container.md)
- [ThemeLoader](../Core/ThemeLoader.md)
- [AssetsServiceProvider](../Core/Providers/AssetsServiceProvider.md)
- [ThemeServiceProvider](../Core/Providers/ThemeServiceProvider.md)

## Обзор

Файл `config/providers.php` определяет список сервис-провайдеров, которые будут зарегистрированы и загружены в вашей теме. Эти настройки используются классом [ThemeLoader](../Core/ThemeLoader.md) для инициализации всех необходимых сервисов.

## Структура конфигурации

```php
return [
    // Базовые провайдеры
    \FastyChild\Providers\AssetsServiceProvider::class,
    \FastyChild\Providers\ThemeServiceProvider::class,
    
    // Провайдеры хуков
    \FastyChild\Providers\StorefrontHooksProvider::class,
    \FastyChild\Providers\WooCommerceHooksProvider::class,
    \FastyChild\Providers\ThemeHooksProvider::class,
    
    // Пользовательские провайдеры
    \YourNamespace\Providers\CustomServiceProvider::class
];
```

## Стандартные провайдеры

### AssetsServiceProvider
Управляет загрузкой стилей и скриптов темы.

```php
\FastyChild\Providers\AssetsServiceProvider::class
```

### ThemeServiceProvider
Отвечает за базовую настройку темы WordPress.

```php
\FastyChild\Providers\ThemeServiceProvider::class
```

### StorefrontHooksProvider
Управляет хуками и модификациями темы Storefront.

```php
\FastyChild\Providers\StorefrontHooksProvider::class
```

### WooCommerceHooksProvider
Управляет хуками и модификациями WooCommerce.

```php
\FastyChild\Providers\WooCommerceHooksProvider::class
```

### ThemeHooksProvider
Управляет базовыми хуками WordPress.

```php
\FastyChild\Providers\ThemeHooksProvider::class
```

## Создание пользовательского провайдера

```php
namespace YourNamespace\Providers;

use FastyChild\Core\ServiceProvider;
use FastyChild\Core\Container;

class CustomServiceProvider implements ServiceProvider {
    private $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    public function register(): void {
        // Регистрация сервисов
        $this->container->singleton('custom.service', function() {
            return new CustomService();
        });
    }
    
    public function boot(): void {
        // Инициализация после регистрации всех сервисов
        $service = $this->container->get('custom.service');
        $service->initialize();
    }
}
```

## Где используется

- `ThemeLoader::registerProvider()` - Регистрация провайдеров
- `ThemeLoader::boot()` - Загрузка всех провайдеров

## Порядок загрузки

1. Регистрация всех провайдеров
2. Вызов метода `register()` каждого провайдера
3. Вызов метода `boot()` каждого провайдера
4. Выполнение хука `fasty_child_booted`

## Примеры использования

### Добавление нового провайдера

```php
return [
    // Стандартные провайдеры
    \FastyChild\Providers\AssetsServiceProvider::class,
    \FastyChild\Providers\ThemeServiceProvider::class,
    
    // Ваш пользовательский провайдер
    \YourNamespace\Providers\CustomFeaturesProvider::class
];
```

### Условная загрузка провайдера

```php
$providers = [
    \FastyChild\Providers\AssetsServiceProvider::class,
    \FastyChild\Providers\ThemeServiceProvider::class
];

// Добавляем WooCommerce провайдер только если плагин активен
if (class_exists('WooCommerce')) {
    $providers[] = \FastyChild\Providers\WooCommerceHooksProvider::class;
}

return $providers;
```

## Советы по использованию

1. Соблюдайте правильный порядок загрузки провайдеров
2. Разделяйте логику на отдельные провайдеры
3. Используйте условную загрузку для оптимизации
4. Документируйте зависимости провайдеров

## Возможные проблемы

- Неправильный порядок загрузки провайдеров
- Циклические зависимости между сервисами
- Конфликты при регистрации одинаковых сервисов
- Проблемы производительности при большом количестве провайдеров

