# Сервисы Fasty (Services)

## Обзор

Модуль `Services` предоставляет набор служебных классов для решения типовых задач в разработке WordPress тем.

## Основные компоненты

### Сервисы темы
- `AssetsService`: Управление ресурсами темы
  - Подключение CSS и JavaScript
  - Оптимизация загрузки ресурсов
- `ThemeService`: Базовые функции темы
  - Регистрация областей меню
  - Настройка поддержки WordPress

## Принципы работы

1. Инкапсуляция логики
2. Единая точка управления
3. Расширяемость и настройка

## Использование

```php
use Fasty\Services\AssetsService;
use Fasty\Services\ThemeService;

class MyTheme {
    private $assetsService;
    private $themeService;

    public function __construct(
        AssetsService $assetsService, 
        ThemeService $themeService
    ) {
        $this->assetsService = $assetsService;
        $this->themeService = $themeService;
    }

    public function setup() {
        $this->themeService->registerMenus([
            'primary' => 'Основное меню',
            'footer' => 'Меню подвала'
        ]);

        $this->assetsService->enqueueStyle('main', 'assets/css/main.css');
        $this->assetsService->enqueueScript('theme', 'assets/js/theme.js');
    }
}
```

## Функциональность

### AssetsService
- Условная загрузка ресурсов
- Минификация и объединение файлов
- Версионность ресурсов

### ThemeService
- Поддержка WordPress features
- Настройка изображений
- Регистрация типов записей и таксономий

## Преимущества

- Модульность
- Простота тестирования
- Гибкая конфигурация
- Производительность

## Расширение

Легко создавать собственные сервисы, наследуясь от базовых классов или реализуя интерфейсы. 