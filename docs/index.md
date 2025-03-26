# Fasty: Современный Фреймворк для WordPress Тем

## Введение

Fasty - это продвинутый PHP-фреймворк для создания дочерних тем в WordPress, разработанный с учетом современных подходов к веб-разработке. Наш фреймворк предоставляет мощный и гибкий инструментарий для быстрого и эффективного создания настраиваемых тем.

## Основные преимущества

- 🚀 Высокая производительность
- 🧩 Модульная архитектура
- 🔧 Расширяемость и гибкость
- 🛡️ Надежная система безопасности
- 📦 Интеграция с современными PHP-практиками

## Структура Фреймворка

Fasty организован по четким архитектурным принципам:

- **Core/** - Ядро фреймворка
- **Theme/** - Компоненты темы
- **Hooks/** - Система хуков WordPress
- **Providers/** - Сервис-провайдеры
- **Services/** - Служебные классы
- **config/** - Конфигурационные файлы

## Начало работы

### Требования

- PHP 7.4+
- WordPress 5.6+

### Установка

```bash
composer require fasty/theme-framework
```

## Документация

Ознакомьтесь с подробной документацией по разделам:

- [Bootstrap](./bootstrap.md)
- [Ядро (Core)](./Core/index.md)
- [Хуки](./Hooks/index.md)
- [Провайдеры](./Providers/index.md)
- [Сервисы](./Services/index.md)

## Лицензия

Fasty распространяется под [лицензией MIT](LICENSE.md)

## Поддержка

По вопросам поддержки и развития фреймворка обращайтесь [на нашем GitHub](https://github.com/fasty/theme-framework)

# Documentation Index

- [bootstrap](./bootstrap.md)
- config/
  - [assets](./config/assets.md)
  - [hooks](./config/hooks.md)
  - [providers](./config/providers.md)
  - [storefront](./config/storefront.md)
  - [theme](./config/theme.md)
  - [woocommerce](./config/woocommerce.md)
- Core/
  - [AbstractServiceProvider](./Core/AbstractServiceProvider.md)
  - [Application](./Core/Application.md)
  - Config/
    - [ConfigInterface](./Core/Config/ConfigInterface.md)
    - [ConfigManager](./Core/Config/ConfigManager.md)
  - [Container](./Core/Container.md)
  - Exceptions/
    - [ConfigurationException](./Core/Exceptions/ConfigurationException.md)
    - [ContainerException](./Core/Exceptions/ContainerException.md)
    - [FastyException](./Core/Exceptions/FastyException.md)
    - [NotFoundException](./Core/Exceptions/NotFoundException.md)
  - Hooks/
    - [AbstractHook](./Core/Hooks/AbstractHook.md)
    - [HookInterface](./Core/Hooks/HookInterface.md)
    - [HooksManager](./Core/Hooks/HooksManager.md)
  - Migration/
    - [AbstractMigration](./Core/Migration/AbstractMigration.md)
    - [MigrationInterface](./Core/Migration/MigrationInterface.md)
    - [MigrationManager](./Core/Migration/MigrationManager.md)
  - Providers/
    - [AssetsServiceProvider](./Core/Providers/AssetsServiceProvider.md)
    - [HooksProvider](./Core/Providers/HooksProvider.md)
    - [ThemeServiceProvider](./Core/Providers/ThemeServiceProvider.md)
  - [ServiceProvider](./Core/ServiceProvider.md)
  - [ThemeLoader](./Core/ThemeLoader.md)
  - Traits/
    - [ContainerAwareTrait](./Core/Traits/ContainerAwareTrait.md)
    - [LoggerTrait](./Core/Traits/LoggerTrait.md)
  - [Utils](./Core/Utils.md)
- Hooks/
  - [AbstractHooks](./Hooks/AbstractHooks.md)
  - [Constants](./Hooks/Constants.md)
  - [HookInterface](./Hooks/HookInterface.md)
  - [HooksManager](./Hooks/HooksManager.md)
  - [StorefrontHooks](./Hooks/StorefrontHooks.md)
  - [ThemeHooks](./Hooks/ThemeHooks.md)
  - [WooCommerceHooks](./Hooks/WooCommerceHooks.md)
- Providers/
  - [HooksServiceProvider](./Providers/HooksServiceProvider.md)
- Services/
  - [AssetsService](./Services/AssetsService.md)
  - [ThemeService](./Services/ThemeService.md)
- Theme/
  - Hooks/
    - [AbstractThemeHook](./Theme/Hooks/AbstractThemeHook.md)
    - [StorefrontHooks](./Theme/Hooks/StorefrontHooks.md)
    - [ThemeHooks](./Theme/Hooks/ThemeHooks.md)
  - Providers/
    - [StorefrontHooksProvider](./Theme/Providers/StorefrontHooksProvider.md)
