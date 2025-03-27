# Конфигурация ресурсов (assets.php)

## Связанные страницы

- [AssetsService](../Services/AssetsService.md)
- [AssetsServiceProvider](../Core/Providers/AssetsServiceProvider.md)

## Обзор

Файл `config/assets.php` управляет подключением стилей и скриптов в вашей теме. Эти настройки используются сервисом [AssetsService](../Services/AssetsService.md) для регистрации и загрузки ресурсов.

## Структура конфигурации

```php
return [
    // Стили для фронтенда
    'styles' => [
        // Простое подключение
        'main' => '/assets/css/main.css',
        
        // Расширенное подключение
        'custom' => [
            'src' => '/assets/css/custom.css',
            'deps' => ['bootstrap'], // Зависимости
            'media' => 'screen',     // Медиа-запрос
            'ver' => '1.0.0'        // Версия
        ]
    ],

    // Скрипты для фронтенда
    'scripts' => [
        // Простое подключение
        'main' => '/assets/js/main.js',
        
        // Расширенное подключение
        'custom' => [
            'src' => '/assets/js/custom.js',
            'deps' => ['jquery', 'wp-util'], // Зависимости
            'in_footer' => true,             // Загрузка в футере
            'localize' => [                  // Локализация
                'object_name' => 'ThemeSettings',
                'data' => [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('theme-nonce')
                ]
            ]
        ]
    ],

    // Стили для админки
    'admin_styles' => [
        'admin' => '/assets/css/admin.css'
    ],

    // Скрипты для админки
    'admin_scripts' => [
        'admin' => '/assets/js/admin.js'
    ]
];
```

## Параметры

### Стили (`styles`)

| Параметр | Тип | Описание |
|----------|-----|----------|
| `src` | string | Путь к файлу стилей относительно корня темы |
| `deps` | array | Массив зависимостей (других стилей) |
| `media` | string | Медиа-запрос (screen, print, all) |
| `ver` | string | Версия файла |

### Скрипты (`scripts`)

| Параметр | Тип | Описание |
|----------|-----|----------|
| `src` | string | Путь к файлу скрипта относительно корня темы |
| `deps` | array | Массив зависимостей (других скриптов) |
| `in_footer` | bool | Загружать в футере (true) или в хедере (false) |
| `ver` | string | Версия файла |
| `localize` | array | Данные для локализации скрипта |

## Где используется

- `AssetsService::enqueueStyles()` - Подключение стилей
- `AssetsService::enqueueScripts()` - Подключение скриптов
- `AssetsService::enqueueAdminStyles()` - Подключение стилей админки
- `AssetsService::enqueueAdminScripts()` - Подключение скриптов админки

## Примеры использования

### Подключение Bootstrap и кастомных стилей

```php
'styles' => [
    'bootstrap' => [
        'src' => '/assets/css/bootstrap.min.css',
        'ver' => '5.3.0'
    ],
    'theme' => [
        'src' => '/assets/css/theme.css',
        'deps' => ['bootstrap']
    ]
]
```

### Локализация скрипта

```php
'scripts' => [
    'ajax-handler' => [
        'src' => '/assets/js/ajax-handler.js',
        'deps' => ['jquery'],
        'localize' => [
            'object_name' => 'AjaxHandler',
            'data' => [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('ajax-nonce'),
                'isLoggedIn' => is_user_logged_in()
            ]
        ]
    ]
]
```

## Советы по использованию

1. Всегда указывайте зависимости для корректной загрузки
2. Используйте `in_footer => true` для оптимизации загрузки страницы
3. Группируйте и минифицируйте файлы для продакшена
4. Используйте версионирование для кэширования

## Возможные проблемы

- Неправильный порядок загрузки зависимостей
- Конфликты с плагинами при использовании одинаковых handle
- Проблемы с кэшированием при отсутствии версий
- Ошибки JavaScript при неправильной локализации

