# Провайдер Assets (AssetsServiceProvider)

## Обзор

`AssetsServiceProvider` отвечает за регистрацию и загрузку ресурсов темы (стилей и скриптов) в мини-фреймворке Fasty.

## Основные функции

### Регистрация сервиса

```php
public function register(): void {
    $this->container->singleton('assets', function() {
        return new AssetsService($this->container->get('app'));
    });
}
```

- Регистрирует `AssetsService` как синглтон в контейнере
- Создает единственный экземпляр сервиса управления ресурсами

### Инициализация ресурсов

```php
public function boot(): void {
    $assets = $this->container->get('assets');
    
    // Подключение стилей и скриптов для фронтенда
    add_action('wp_enqueue_scripts', [$assets, 'enqueueStyles']);
    add_action('wp_enqueue_scripts', [$assets, 'enqueueScripts']);
    
    // Подключение стилей и скриптов для административной панели
    add_action('admin_enqueue_scripts', [$assets, 'enqueueAdminStyles']);
    add_action('admin_enqueue_scripts', [$assets, 'enqueueAdminScripts']);
}
```

## Ключевые возможности

- 🎨 Централизованное управление ресурсами темы
- 🚀 Быстрая регистрация стилей и скриптов
- 🔧 Раздельная загрузка ресурсов для фронтенда и административной панели

## Пример использования

```php
// В вашем AssetsService
public function enqueueStyles() {
    wp_enqueue_style(
        'theme-main-style', 
        get_stylesheet_directory_uri() . '/assets/css/main.css', 
        [], 
        '1.0.0'
    );
}

public function enqueueScripts() {
    wp_enqueue_script(
        'theme-main-script', 
        get_stylesheet_directory_uri() . '/assets/js/main.js', 
        ['jquery'], 
        '1.0.0', 
        true
    );
}
```

## Советы по использованию

1. Всегда указывайте версию ресурсов для корректного кэширования
2. Используйте зависимости при подключении скриптов
3. Подключайте скрипты с параметром `true` для загрузки в футере

## Возможные проблемы

- Убедитесь, что пути к файлам ресурсов корректны
- Проверьте совместимость версий подключаемых библиотек
- Следите за производительностью при большом количестве ресурсов

namespace FastyChild\Core\Providers;

use FastyChild\Core\Container;
use FastyChild\Core\ServiceProvider;
use FastyChild\Services\AssetsService;

class AssetsServiceProvider implements ServiceProvider {
/**
Container instance

#### Parameters

- ``: container Container

### register
<!-- @doc-source: AssetsServiceProvider.register -->
Register assets service in the container

#### Returns



### boot
<!-- @doc-source: AssetsServiceProvider.boot -->
Boot assets service - hook into WordPress

#### Returns



