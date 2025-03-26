# Bootstrap Fasty

## Обзор

Bootstrap - это процесс инициализации и настройки фреймворка Fasty, который происходит при загрузке темы WordPress.

## Цель

Подготовка окружения, регистрация сервисов, инициализация ключевых компонентов темы.

## Процесс Загрузки

1. **Инициализация ядра**
   - Создание экземпляра `Application`
   - Настройка контейнера зависимостей

2. **Регистрация провайдеров**
   - Загрузка базовых сервис-провайдеров
   - Подключение пользовательских провайдеров

3. **Конфигурация**
   - Загрузка настроек темы
   - Применение глобальных параметров

## Пример Кода

```php
<?php
// bootstrap.php

use Fasty\Core\Application;
use Fasty\Core\Providers\ThemeServiceProvider;
use Fasty\Hooks\HooksManager;

$app = Application::getInstance();

// Регистрация базовых провайдеров
$app->register(new ThemeServiceProvider());

// Инициализация хуков
$hooksManager = new HooksManager($app);
$hooksManager->init();

// Пользовательская конфигурация
$app->configure([
    'theme_support' => [
        'title-tag',
        'post-thumbnails',
        'responsive-embeds'
    ]
]);
```

## Ключевые Этапы

### Инициализация Ядра
- Создание единого экземпляра приложения
- Настройка базовых служб

### Регистрация Сервисов
- Подключение провайдеров
- Внедрение зависимостей

### Конфигурация
- Загрузка настроек
- Применение глобальных параметров

## Расширение

Можно легко кастомизировать процесс загрузки:
- Добавление собственных провайдеров
- Модификация конфигурации
- Внедрение дополнительных служб

## Преимущества

- Прозрачность процесса
- Гибкость настройки
- Минимальные накладные расходы
- Полный контроль над инициализацией

# bootstrap

<!-- @doc-source: bootstrap -->


## Methods

### initializeFramework
<!-- @doc-source: bootstrap.initializeFramework -->
Bootstrap file for the FastyChild framework
Initializes the application and registers necessary providers
/

namespace FastyChild;

use FastyChild\Core\ThemeLoader;
use FastyChild\Core\Exceptions\FastyException;
use FastyChild\Core\Exceptions\ContainerException;
use FastyChild\Hooks\Constants;

// Ensure this file is not accessed directly
if (!defined('ABSPATH')) {
exit;
}

/**
Initialize framework core components

#### Returns



### registerErrorHandler
<!-- @doc-source: bootstrap.registerErrorHandler -->
Register error handler for development

#### Returns



### registerProviders
<!-- @doc-source: bootstrap.registerProviders -->
Register service providers from configuration

#### Parameters

- ``: ThemeLoader $themeLoader
- ``: themeLoader ThemeLoader

#### Returns



### logAndNotifyError
<!-- @doc-source: bootstrap.logAndNotifyError -->
Log and notify about errors

#### Parameters

- ``: null \Throwable $e
- ``: e \Throwable

#### Returns



