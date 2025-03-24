# Сервис Assets (AssetsService)

## Обзор

`AssetsService` управляет загрузкой и подключением ресурсов (стилей и скриптов) для темы WordPress в мини-фреймворке Fasty.

## Основные возможности

- 🎨 Гибкая загрузка стилей
- 🚀 Динамическое подключение скриптов
- 🔧 Поддержка администраторских ресурсов
- 📦 Автоматическое версионирование файлов

## Конфигурация ресурсов

### Стили

```php
// config/assets.php
return [
    'styles' => [
        // Простое подключение
        'main' => '/assets/css/main.css',
        
        // Расширенная конфигурация
        'custom' => [
            'src' => '/assets/css/custom.css',
            'deps' => ['bootstrap'],
            'media' => 'screen',
            'ver' => '1.0.0'
        ]
    ]
];
```

### Скрипты

```php
// config/assets.php
return [
    'scripts' => [
        // Простое подключение
        'main' => '/assets/js/main.js',
        
        // Расширенная конфигурация
        'custom' => [
            'src' => '/assets/js/custom.js',
            'deps' => ['jquery', 'wp-util'],
            'in_footer' => true,
            'localize' => [
                'object_name' => 'MyThemeSettings',
                'data' => [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('my-theme-nonce')
                ]
            ]
        ]
    ]
];
```

## Ключевые методы

### `enqueueStyles()`

Подключение стилей темы:

```php
public function enqueueStyles(): void {
    $styles = $this->app->config('assets.styles', []);
    
    foreach ($styles as $handle => $style) {
        wp_enqueue_style(
            "fasty-child-{$handle}", 
            FASTY_CHILD_URI . $style['src'], 
            $style['deps'] ?? [], 
            $this->getFileVersion(FASTY_CHILD_PATH . $style['src'])
        );
    }
}
```

### `enqueueScripts()`

Подключение скриптов темы:

```php
public function enqueueScripts(): void {
    $scripts = $this->app->config('assets.scripts', []);
    
    foreach ($scripts as $handle => $script) {
        wp_enqueue_script(
            "fasty-child-{$handle}", 
            FASTY_CHILD_URI . $script['src'], 
            $script['deps'] ?? ['jquery'], 
            $this->getFileVersion(FASTY_CHILD_PATH . $script['src']), 
            $script['in_footer'] ?? true
        );
    }
}
```

### Администраторские ресурсы

```php
public function enqueueAdminStyles(): void {
    $admin_styles = $this->app->config('assets.admin_styles', []);
    // Логика подключения стилей для административной панели
}

public function enqueueAdminScripts(): void {
    $admin_scripts = $this->app->config('assets.admin_scripts', []);
    // Логика подключения скриптов для административной панели
}
```

## Преимущества

- 🔌 Полная интеграция с WordPress
- 🧩 Гибкая настройка через конфигурационные файлы
- 🚀 Автоматическое версионирование ресурсов
- 🛠 Поддержка локализации скриптов

## Советы по использованию

1. Используйте конфигурационные файлы для настройки ресурсов
2. Минимизируйте прямые модификации в коде
3. Всегда указывайте зависимости скриптов
4. Используйте локализацию для передачи данных в JavaScript

## Возможные проблемы

- Следите за производительностью при большом количестве ресурсов
- Проверяйте совместимость подключаемых библиотек
- Избегайте конфликтов с ресурсами плагинов
- Тестируйте на разных устройствах и браузерах

# AssetsService

<!-- @doc-source: AssetsService -->


## Methods

### __construct
<!-- @doc-source: AssetsService.__construct -->
Assets Service
Handles theme assets loading (styles and scripts)
/

namespace FastyChild\Services;

use FastyChild\Core\Application;

class AssetsService {
/**
Application instance

#### Parameters

- ``: app Application

### enqueueStyles
<!-- @doc-source: AssetsService.enqueueStyles -->
Enqueue theme styles

#### Returns



### enqueueScripts
<!-- @doc-source: AssetsService.enqueueScripts -->
Enqueue theme scripts

#### Returns



### enqueueAdminStyles
<!-- @doc-source: AssetsService.enqueueAdminStyles -->
Enqueue admin styles

#### Returns



### enqueueAdminScripts
<!-- @doc-source: AssetsService.enqueueAdminScripts -->
Enqueue admin scripts

#### Returns



### getFileVersion
<!-- @doc-source: AssetsService.getFileVersion -->
Get file version based on file modification time

#### Parameters

- ``: string $file Path to file
- ``: file mixed

#### Returns



