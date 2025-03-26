# Хуки Fasty (Hooks)

## Обзор

Модуль `Hooks` предоставляет мощный и гибкий механизм для расширения и кастомизации функциональности WordPress через хуки.

## Основные компоненты

### Базовые классы
- `AbstractHooks`: Абстрактный базовый класс для создания групп хуков
- `HookInterface`: Интерфейс для реализации пользовательских хуков

### Специализированные хуки
- `StorefrontHooks`: Хуки для магазинов на базе WooCommerce
- `ThemeHooks`: Хуки для настройки темы
- `WooCommerceHooks`: Специфические хуки для WooCommerce

### Менеджеры
- `HooksManager`: Централизованное управление хуками

## Принципы работы

1. Декларативное определение хуков
2. Простота расширения
3. Полная совместимость с WordPress

## Использование

```php
use Fasty\Hooks\AbstractHooks;

class MyCustomHooks extends AbstractHooks {
    public function register() {
        $this->addAction('wp_enqueue_scripts', 'enqueueStyles');
        $this->addFilter('body_class', 'addCustomBodyClasses');
    }

    public function enqueueStyles() {
        // Подключение стилей
    }

    public function addCustomBodyClasses($classes) {
        // Добавление пользовательских классов
        return $classes;
    }
}
```

## Типы хуков

- **Action Hooks**: Выполнение определенных действий
- **Filter Hooks**: Модификация данных перед выводом

## Преимущества

- Чистая и понятная структура
- Легкость тестирования
- Минимальные накладные расходы
- Полная типизация 