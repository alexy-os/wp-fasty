# Конфигурация Storefront (storefront.php)

## Связанные страницы

- [StorefrontHooks](../Hooks/StorefrontHooks.md)
- [Конфигурация темы](./theme.md)
- [Конфигурация WooCommerce](./woocommerce.md)

## Обзор

Файл `config/storefront.php` содержит настройки для кастомизации темы Storefront. Эти настройки используются сервисом [StorefrontHooks](../Hooks/StorefrontHooks.md) для модификации стандартного поведения темы.

## Структура конфигурации

```php
return [
    // Основные настройки макета
    'page_layout' => 'full-width',  // full-width, left-sidebar, right-sidebar
    'footer_widget_columns' => 4,   // Количество колонок в футере
    'credit_text' => 'Создано на Fasty', // Текст копирайта

    // Замена стандартного header
    'override_header' => true,

    // Настройка хуков Storefront
    'hooks' => [
        // Хуки шапки
        'storefront_header' => [
            'remove' => [
                'storefront_header_cart' => 10,
                'storefront_primary_navigation' => 50
            ],
            'add' => [
                'customHeaderCart' => 10,
                'customPrimaryNavigation' => 50
            ]
        ],

        // Хуки подвала
        'storefront_footer' => [
            'remove' => [
                'storefront_credit' => 20
            ],
            'add' => [
                'customFooterContent' => 20
            ]
        ],

        // Хуки страницы
        'storefront_page' => [
            'remove' => [
                'storefront_page_header' => 10
            ],
            'add' => [
                'customPageHeader' => 10
            ]
        ]
    ],

    // Настройки компонентов
    'components' => [
        'header' => [
            'show_search' => true,
            'show_cart' => true,
            'sticky' => true
        ],
        'footer' => [
            'show_payment_icons' => true,
            'show_social_icons' => true
        ]
    ],

    // Кастомные стили
    'styles' => [
        'primary_color' => '#7f54b3',
        'secondary_color' => '#3d9cd2',
        'button_background' => '#7f54b3',
        'button_text' => '#ffffff'
    ]
];
```

## Параметры

### Основные настройки

| Параметр | Тип | Описание |
|----------|-----|----------|
| `page_layout` | string | Макет страницы (full-width, left-sidebar, right-sidebar) |
| `footer_widget_columns` | int | Количество колонок в футере |
| `credit_text` | string | Текст копирайта |
| `override_header` | bool | Полная замена шапки на кастомную |

### Хуки (`hooks`)

Настройка хуков Storefront для модификации стандартного поведения:
- `remove` - Удаление стандартных хуков
- `add` - Добавление кастомных хуков

### Компоненты (`components`)

Настройка отдельных компонентов темы:
- `header` - Настройки шапки
- `footer` - Настройки подвала

## Где используется

- `StorefrontHooks::processAllHooksFromConfig()` - Обработка хуков
- `StorefrontHooks::modifyPageLayout()` - Изменение макета
- `StorefrontHooks::modifyFooterWidgetColumns()` - Настройка колонок
- `StorefrontHooks::customHeader()` - Кастомная шапка

## Примеры использования

### Кастомизация шапки

```php
'hooks' => [
    'storefront_header' => [
        'remove' => [
            'storefront_header_cart' => 10
        ],
        'add' => [
            'customMiniCart' => [
                'callback' => 'displayCustomMiniCart',
                'priority' => 10
            ]
        ]
    ]
]
```

### Настройка компонентов

```php
'components' => [
    'header' => [
        'sticky' => true,
        'show_search' => true,
        'search_type' => 'ajax',
        'cart_type' => 'mini'
    ]
]
```

### Кастомные стили

```php
'styles' => [
    'header_background' => '#ffffff',
    'header_text_color' => '#333333',
    'footer_background' => '#f8f8f8',
    'button_radius' => '4px'
]
```

## Советы по использованию

1. Сначала проверьте стандартные хуки Storefront
2. Используйте правильные приоритеты при добавлении хуков
3. Тестируйте изменения на разных размерах экрана
4. Следите за совместимостью с обновлениями Storefront

## Возможные проблемы

- Конфликты при удалении важных хуков
- Проблемы с отзывчивостью при кастомной шапке
- Несовместимость с плагинами расширения Storefront
- Потеря функционала при обновлении темы

