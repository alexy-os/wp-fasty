# Провайдеры Fasty (Providers)

## Обзор

Модуль `Providers` реализует шаблон Service Provider, обеспечивающий гибкую и расширяемую архитектуру внедрения зависимостей в WordPress темы.

## Основные компоненты

### Базовые провайдеры
- `HooksServiceProvider`: Регистрация и управление хуками
- `AssetsServiceProvider`: Управление ресурсами (CSS, JS)
- `ThemeServiceProvider`: Основной провайдер темы

## Принципы работы

1. Декларативная регистрация сервисов
2. Изоляция зависимостей
3. Легкость расширения и замены компонентов

## Использование

```php
use Fasty\Core\Providers\ServiceProvider;
use Fasty\Core\Application;

class CustomThemeProvider extends ServiceProvider {
    public function register(Application $app) {
        // Регистрация пользовательских сервисов
        $app->singleton('custom_service', function() {
            return new CustomService();
        });
    }

    public function boot(Application $app) {
        // Инициализация после регистрации
    }
}
```

## Типы провайдеров

- **Singleton**: Единственный экземпляр сервиса
- **Transient**: Новый экземпляр при каждом вызове
- **Параметризованные**: Настраиваемые сервисы

## Преимущества

- Чистая архитектура
- Простота тестирования
- Гибкость настройки
- Минимизация связанности кода

## Внедрение зависимостей

- Автоматическое разрешение зависимостей
- Поддержка интерфейсов
- Отложенная инициализация сервисов 