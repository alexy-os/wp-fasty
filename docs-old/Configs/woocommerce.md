# Конфигурация WooCommerce (woocommerce.php)

## Связанные страницы

- [WooCommerceHooks](../Hooks/WooCommerceHooks.md)
- [Конфигурация Storefront](./storefront.md)
- [Конфигурация темы](./theme.md)

## Обзор

Файл `config/woocommerce.php` содержит настройки для кастомизации WooCommerce в вашей теме. Эти настройки используются сервисом [WooCommerceHooks](../Hooks/WooCommerceHooks.md) для модификации стандартного поведения магазина.

## Структура конфигурации

```php
return [
    // Настройки страницы товара
    'product_title_classes' => 'custom-title product-name',
    'sale_flash' => '-{percentage}% Скидка!',
    
    // Настройки связанных товаров
    'related_products' => [
        'posts_per_page' => 4,
        'columns' => 4,
        'orderby' => 'rand'
    ],

    // Настройка полей оформления заказа
    'checkout_fields' => [
        'billing' => [
            'billing_company' => [
                'required' => false,
                'label' => 'Название компании',
                'priority' => 25
            ],
            'billing_phone' => [
                'required' => true,
                'label' => 'Телефон для связи',
                'priority' => 20
            ]
        ],
        'shipping' => [
            'shipping_address_2' => [
                'remove' => true
            ]
        ]
    ],

    // Настройка вкладок товара
    'product_tabs' => [
        'remove' => ['reviews', 'additional_information'],
        'add' => [
            'custom_tab' => [
                'title' => 'Дополнительно',
                'priority' => 50,
                'callback' => 'customTabContent'
            ]
        ],
        'reorder' => [
            'description' => 10,
            'custom_tab' => 20
        ]
    ],

    // Настройка хуков WooCommerce
    'hooks' => [
        // Хуки страницы товара
        'woocommerce_single_product_summary' => [
            'remove' => [
                'woocommerce_template_single_meta' => 40
            ],
            'add' => [
                'customProductMeta' => 45
            ]
        ],
        
        // Хуки архива товаров
        'woocommerce_archive_description' => [
            'remove' => [
                'woocommerce_taxonomy_archive_description' => 10
            ],
            'add' => [
                'customCategoryDescription' => 10
            ]
        ]
    ],

    // Настройки каталога
    'catalog' => [
        'products_per_page' => 12,
        'columns' => 4,
        'show_ratings' => true,
        'show_price' => true,
        'show_categories' => true
    ]
];
```

## Параметры

### Страница товара

| Параметр | Тип | Описание |
|----------|-----|----------|
| `product_title_classes` | string | CSS классы для заголовка товара |
| `sale_flash` | string | Формат метки распродажи |

### Связанные товары (`related_products`)

| Параметр | Тип | Описание |
|----------|-----|----------|
| `posts_per_page` | int | Количество товаров |
| `columns` | int | Количество колонок |
| `orderby` | string | Сортировка (rand, date, title) |

### Поля оформления заказа (`checkout_fields`)

Настройка полей в секциях:
- `billing` - Платежные данные
- `shipping` - Данные доставки
- `account` - Данные аккаунта

### Вкладки товара (`product_tabs`)

- `remove` - Удаление стандартных вкладок
- `add` - Добавление новых вкладок
- `reorder` - Изменение порядка вкладок

## Где используется

- `WooCommerceHooks::modifyProductTitleClasses()` - Классы заголовка
- `WooCommerceHooks::modifySaleFlash()` - Метка распродажи
- `WooCommerceHooks::modifyCheckoutFields()` - Поля оформления
- `WooCommerceHooks::modifyProductTabs()` - Вкладки товара

## Примеры использования

### Кастомизация метки распродажи

```php
'sale_flash' => '<span class="onsale">Скидка {percentage}%!</span>'
```

### Настройка полей оформления

```php
'checkout_fields' => [
    'billing' => [
        'billing_address_2' => [
            'remove' => true
        ],
        'billing_company' => [
            'required' => false,
            'label' => 'ИНН',
            'placeholder' => 'Введите ИНН организации'
        ]
    ]
]
```

### Добавление кастомной вкладки

```php
'product_tabs' => [
    'add' => [
        'delivery_info' => [
            'title' => 'Доставка',
            'priority' => 30,
            'callback' => function() {
                echo '<div class="delivery-info">';
                echo '<h3>Информация о доставке</h3>';
                // Контент вкладки
                echo '</div>';
            }
        ]
    ]
]
```

## Советы по использованию

1. Проверяйте совместимость с версией WooCommerce
2. Тестируйте оформление заказа после изменения полей
3. Используйте правильные приоритеты хуков
4. Добавляйте документацию к кастомным вкладкам

## Возможные проблемы

- Конфликты с плагинами WooCommerce
- Проблемы с валидацией при изменении полей
- Ошибки в работе корзины при модификации хуков
- Проблемы с обновлением при сильной кастомизации

