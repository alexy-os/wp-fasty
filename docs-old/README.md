# Fasty Framework

Мини-фреймворк для создания продвинутых дочерних тем WordPress с фокусом на WooCommerce и Storefront.

## 🚀 Возможности

- Объектно-ориентированная архитектура с внедрением зависимостей
- Конфигурационный подход к настройке темы
- Сервис-провайдеры для модульной функциональности
- Система переопределения хуков Storefront
- Расширенная интеграция с WooCommerce
- Поддержка Tailwind CSS

## 📁 Структура фреймворка

```
wp-fasty-storefront/
├── assets/              # Ресурсы темы
│   ├── css/            # Скомпилированные CSS файлы
│   ├── scss/           # SCSS исходники
│   ├── js/             # JavaScript файлы
│   └── fonts/          # Шрифты
├── fasty/              # Ядро фреймворка
│   ├── Core/           # Основные классы
│   ├── Hooks/          # Обработчики хуков
│   ├── Services/       # Сервисы
│   └── config/         # Конфигурационные файлы
├── woocommerce/        # Переопределение шаблонов WooCommerce
├── template-parts/     # Части шаблонов
└── languages/          # Файлы переводов
```

## 🎯 С чего начать

### 1. Установка

1. Создайте дочернюю тему Storefront
2. Скопируйте структуру Fasty в вашу тему
3. Подключите фреймворк в `functions.php`:

```php
require_once get_stylesheet_directory() . '/fasty/bootstrap.php';
```

### 2. Конфигурация

Основные настройки находятся в директории `fasty/config/`:

- [Конфигурация ресурсов](./Configs/assets.md) - Управление стилями и скриптами
- [Конфигурация темы](./Configs/theme.md) - Настройки темы WordPress
- [Кастомизация Storefront](./Configs/storefront.md) - Настройки Storefront
- [Настройки WooCommerce](./Configs/woocommerce.md) - Конфигурация магазина
- [Управление провайдерами](./Configs/providers.md) - Сервис-провайдеры

### 3. Основные компоненты

#### Ядро (Core)
- [Приложение](./Core/Application.md) - Основной класс приложения
- [Контейнер](./Core/Container.md) - Контейнер зависимостей
- [Сервис-провайдер](./Core/ServiceProvider.md) - Базовый класс провайдеров
- [Загрузчик темы](./Core/ThemeLoader.md) - Загрузчик темы

#### Сервисы
- [Сервис ресурсов](./Services/AssetsService.md) - Управление ресурсами
- [Сервис темы](./Services/ThemeService.md) - Базовые функции темы

#### Хуки
- [Хуки Storefront](./Hooks/StorefrontHooks.md) - Модификация Storefront
- [Хуки WordPress](./Hooks/ThemeHooks.md) - Общие хуки темы
- [Хуки WooCommerce](./Hooks/WooCommerceHooks.md) - Интеграция с магазином

## 📚 Руководство по использованию

### Работа с ресурсами

1. Настройте ваши стили и скрипты в `assets.php`:
```php
return [
    'styles' => [
        'main' => '/assets/css/main.css',
        'custom' => [
            'src' => '/assets/css/custom.css',
            'deps' => ['storefront-style']
        ]
    ]
];
```

2. Компиляция SCSS:
```bash
# Сборка
npm run scss:build

# Разработка
npm run scss:watch
```

### Кастомизация Storefront

1. Настройте макет в `storefront.php`:
```php
return [
    'page_layout' => 'full-width',
    'override_header' => true,
    'components' => [
        'header' => [
            'sticky' => true,
            'show_search' => true
        ]
    ]
];
```

2. Создайте свои хуки в `Hooks/CustomHooks.php`:
```php
namespace FastyChild\Hooks;

class CustomHooks {
    public function register(): void {
        add_action('storefront_header', [$this, 'customHeader'], 10);
    }
}
```

### Интеграция с WooCommerce

1. Настройте магазин в `woocommerce.php`:
```php
return [
    'catalog' => [
        'products_per_page' => 12,
        'columns' => 4
    ],
    'checkout_fields' => [
        'billing_company' => [
            'required' => false
        ]
    ]
];
```

## 🔧 Расширение функционала

### Создание сервиса

1. Создайте класс сервиса:
```php
namespace FastyChild\Services;

class CustomService {
    public function initialize(): void {
        // Ваш код
    }
}
```

2. Создайте провайдер:
```php
namespace FastyChild\Providers;

use FastyChild\Core\ServiceProvider;

class CustomServiceProvider implements ServiceProvider {
    public function register(): void {
        $this->container->singleton('custom', function() {
            return new CustomService();
        });
    }
}
```

3. Зарегистрируйте провайдер в `providers.php`

## 📖 Документация

- [Главная страница](./index.md)
- [Управление ресурсами](./Configs/assets.md)
- [Настройка темы](./Configs/theme.md)
- [Настройка магазина](./Configs/woocommerce.md)
- [Кастомизация Storefront](./Configs/storefront.md)

## 🤝 Поддержка

- [GitHub](https://github.com/alexy-os/wp-fasty)

## 📝 Лицензия

MIT License - [подробнее](LICENSE.md) 