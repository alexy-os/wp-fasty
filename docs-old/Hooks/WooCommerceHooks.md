# Хуки WooCommerce (WooCommerceHooks)

## Обзор

`WooCommerceHooks` управляет специфическими хуками и модификациями WooCommerce в мини-фреймворке Fasty.

## Основные возможности

- 🛒 Кастомизация страниц продуктов
- 🏷 Модификация меток распродаж
- 🔧 Гибкая настройка полей оформления заказа
- 🚀 Динамическая обработка хуков из конфигурации

## Ключевые методы

### `register()`

Регистрация основных хуков WooCommerce:

```php
public function register(): void {
    // Только если WooCommerce активен
    if (!class_exists('WooCommerce')) {
        return;
    }
    
    add_filter('woocommerce_product_loop_title_classes', [$this, 'modifyProductTitleClasses']);
    add_filter('woocommerce_sale_flash', [$this, 'modifySaleFlash'], 10, 3);
    add_filter('woocommerce_output_related_products_args', [$this, 'modifyRelatedProductsArgs']);
    add_filter('woocommerce_checkout_fields', [$this, 'modifyCheckoutFields']);
    add_filter('woocommerce_locate_template', [$this, 'overrideWooCommerceTemplates'], 10, 3);
    add_filter('woocommerce_product_tabs', [$this, 'modifyProductTabs']);
}
```

## Примеры кастомизации

### Настройка меток распродаж

```php
// config/woocommerce.php
return [
    'sale_flash' => '-{percentage}%', // Показывает процент скидки
];
```

### Модификация полей оформления заказа

```php
// config/woocommerce.php
return [
    'checkout_fields' => [
        'billing' => [
            'billing_company' => [
                'required' => false, // Необязательное поле
                'label' => 'Название компании'
            ]
        ],
        'shipping' => [
            'shipping_phone' => [
                'remove' => true // Удаление поля
            ]
        ]
    ]
];
```

### Настройка связанных продуктов

```php
// config/woocommerce.php
return [
    'related_products' => [
        'posts_per_page' => 4, // Количество связанных продуктов
        'columns' => 4 // Количество колонок
    ]
];
```

### Модификация вкладок продукта

```php
// config/woocommerce.php
return [
    'product_tabs' => [
        'remove' => ['reviews'], // Удаление вкладки отзывов
        'add' => [
            'custom_tab' => [
                'title' => 'Дополнительно',
                'priority' => 50,
                'callback' => [$this, 'customTabContent']
            ]
        ],
        'reorder' => [
            'description' => 20, // Изменение приоритета вкладки описания
        ]
    ]
];
```

### Переопределение шаблонов

```php
// Создайте файл в директории woocommerce вашей темы
// woocommerce/single-product/tabs/description.php
```

## Пример кастомного метода для вкладки

```php
public function customTabContent() {
    echo '<div class="custom-product-tab">';
    echo '<h3>Дополнительная информация</h3>';
    echo '<p>Здесь может быть любой контент</p>';
    echo '</div>';
}
```

## Преимущества

- 🔌 Полная совместимость с WooCommerce
- 🧩 Гибкая настройка через конфигурационные файлы
- 🚀 Быстрая кастомизация без правки исходного кода
- 🛠 Расширенные возможности настройки магазина

## Советы по использованию

1. Используйте конфигурационные файлы для настройки
2. Минимизируйте прямые модификации в коде
3. Тестируйте изменения на разных устройствах
4. Следите за совместимостью с плагинами

## Возможные проблемы

- Проверяйте совместимость с обновлениями WooCommerce
- Избегайте слишком сложных кастомизаций
- Следите за производительностью при большом количестве хуков
- Обрабатывайте возможные конфликты с другими плагинами


## Methods

### __construct
<!-- @doc-source: WooCommerceHooks.__construct -->
WooCommerce Hooks
Handles WooCommerce-specific customizations
/

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class WooCommerceHooks {
/**
Container instance

#### Parameters

- ``: container Container

### register
<!-- @doc-source: WooCommerceHooks.register -->
Register hook handlers

#### Returns



### modifyProductTitleClasses
<!-- @doc-source: WooCommerceHooks.modifyProductTitleClasses -->
Modify product title classes

#### Parameters

- ``: string $classes Default classes
- ``: classes string

#### Returns



### modifySaleFlash
<!-- @doc-source: WooCommerceHooks.modifySaleFlash -->
Modify sale flash

#### Parameters

- ``: string $html Sale flash HTML
- ``: null \WP_Post $post Post object
- ``: null \WC_Product $product Product object
- ``: html string
- ``: post mixed
- ``: product mixed

#### Returns



### modifyRelatedProductsArgs
<!-- @doc-source: WooCommerceHooks.modifyRelatedProductsArgs -->
Modify related products args

#### Parameters

- ``: array $args Default args
- ``: args array

#### Returns



### modifyCheckoutFields
<!-- @doc-source: WooCommerceHooks.modifyCheckoutFields -->
Modify checkout fields

#### Parameters

- ``: array $fields Default fields
- ``: fields array

#### Returns



### overrideWooCommerceTemplates
<!-- @doc-source: WooCommerceHooks.overrideWooCommerceTemplates -->
Override WooCommerce templates

#### Parameters

- ``: string $template Template path
- ``: string $template_name Template name
- ``: string $template_path Template directory
- ``: template string
- ``: template_name string
- ``: template_path string

#### Returns



### modifyProductTabs
<!-- @doc-source: WooCommerceHooks.modifyProductTabs -->
Modify product tabs

#### Parameters

- ``: array $tabs Default tabs
- ``: tabs array

#### Returns



### overrideWooCommerceHooks
<!-- @doc-source: WooCommerceHooks.overrideWooCommerceHooks -->
Override WooCommerce hooks

#### Returns



