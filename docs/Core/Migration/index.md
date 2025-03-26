# Система миграций Fasty

## Введение

Система миграций Fasty предоставляет надежный механизм для управления изменениями в базе данных и структуре темы. Она построена на следующих принципах:
- Версионирование изменений
- Атомарность операций
- Обратимость изменений
- Безопасное обновление

## Архитектура

Система миграций состоит из трех основных компонентов:

1. [`MigrationInterface`](./MigrationInterface.md) - интерфейс для создания миграций
2. [`AbstractMigration`](./AbstractMigration.md) - базовый класс для реализации миграций
3. [`MigrationManager`](./MigrationManager.md) - менеджер для управления миграциями

## Основные концепции

### 1. Версионирование

Каждая миграция имеет уникальный идентификатор версии:

```php
class CreateCustomPostType extends AbstractMigration
{
    protected string $version = '2024_03_26_001';
    
    public function up(): void
    {
        // Создание нового типа записи
    }
    
    public function down(): void
    {
        // Удаление типа записи
    }
}
```

### 2. Атомарность

Миграции выполняются как атомарные операции:

```php
class UpdateThemeOptions extends AbstractMigration
{
    public function up(): void
    {
        try {
            // Начало транзакции
            $this->beginTransaction();
            
            // Обновление опций
            update_option('theme_version', '2.0.0');
            update_option('theme_settings', [
                'layout' => 'modern',
                'colors' => 'dark'
            ]);
            
            // Фиксация изменений
            $this->commit();
        } catch (Exception $e) {
            // Откат при ошибке
            $this->rollback();
            throw $e;
        }
    }
}
```

### 3. Обратимость

Каждая миграция может быть отменена:

```php
class AddCustomTaxonomy extends AbstractMigration
{
    private $taxonomy = 'product_category';
    
    public function up(): void
    {
        // Создание таксономии
        register_taxonomy($this->taxonomy, 'product', [
            'hierarchical' => true,
            'public' => true
        ]);
    }
    
    public function down(): void
    {
        // Удаление таксономии
        unregister_taxonomy($this->taxonomy);
    }
}
```

## Создание миграций

### 1. Базовая миграция

```php
class CreateMenuLocations extends AbstractMigration
{
    protected string $version = '2024_03_26_002';
    
    public function up(): void
    {
        // Регистрация меню
        register_nav_menus([
            'primary' => 'Главное меню',
            'footer' => 'Меню в подвале'
        ]);
        
        // Сохранение настроек
        update_option('theme_menu_version', $this->version);
    }
    
    public function down(): void
    {
        // Удаление меню
        unregister_nav_menu('primary');
        unregister_nav_menu('footer');
        
        // Удаление настроек
        delete_option('theme_menu_version');
    }
}
```

### 2. Миграция с зависимостями

```php
class UpdatePostMeta extends AbstractMigration
{
    protected string $version = '2024_03_26_003';
    protected array $dependencies = [
        CreateCustomPostType::class
    ];
    
    public function up(): void
    {
        // Обновление мета-данных
        $posts = get_posts(['post_type' => 'product']);
        
        foreach ($posts as $post) {
            update_post_meta($post->ID, '_product_version', '2.0.0');
        }
    }
    
    public function down(): void
    {
        // Удаление мета-данных
        $posts = get_posts(['post_type' => 'product']);
        
        foreach ($posts as $post) {
            delete_post_meta($post->ID, '_product_version');
        }
    }
}
```

### 3. Условная миграция

```php
class InstallWooCommerceSupport extends AbstractMigration
{
    protected string $version = '2024_03_26_004';
    
    public function shouldRun(): bool
    {
        return class_exists('WooCommerce');
    }
    
    public function up(): void
    {
        // Добавление поддержки WooCommerce
        add_theme_support('woocommerce');
        add_theme_support('wc-product-gallery-zoom');
        add_theme_support('wc-product-gallery-lightbox');
    }
    
    public function down(): void
    {
        // Удаление поддержки WooCommerce
        remove_theme_support('woocommerce');
        remove_theme_support('wc-product-gallery-zoom');
        remove_theme_support('wc-product-gallery-lightbox');
    }
}
```

## Использование менеджера миграций

### 1. Запуск миграций

```php
class ThemeUpdater
{
    private MigrationManager $migrations;
    
    public function __construct(MigrationManager $migrations)
    {
        $this->migrations = $migrations;
    }
    
    public function update(): void
    {
        try {
            // Запуск всех новых миграций
            $this->migrations->migrate();
            
            // Обновление версии темы
            update_option('theme_db_version', $this->migrations->getLastVersion());
        } catch (Exception $e) {
            // Обработка ошибок
            error_log('Ошибка обновления: ' . $e->getMessage());
        }
    }
}
```

### 2. Откат миграций

```php
class ThemeRollback
{
    private MigrationManager $migrations;
    
    public function rollbackTo(string $version): void
    {
        try {
            // Откат до указанной версии
            $this->migrations->rollback($version);
            
            // Обновление версии темы
            update_option('theme_db_version', $version);
        } catch (Exception $e) {
            // Обработка ошибок
            error_log('Ошибка отката: ' . $e->getMessage());
        }
    }
}
```

## Лучшие практики

### 1. Именование миграций

```php
// Формат: YYYY_MM_DD_XXX
class M2024_03_26_001_CreateCustomPostType extends AbstractMigration
{
    protected string $version = '2024_03_26_001';
}

class M2024_03_26_002_AddCustomFields extends AbstractMigration
{
    protected string $version = '2024_03_26_002';
}
```

### 2. Группировка миграций

```php
// migrations/
//   ├── post-types/
//   │   ├── M2024_03_26_001_CreateProductType.php
//   │   └── M2024_03_26_002_CreateServiceType.php
//   ├── taxonomies/
//   │   ├── M2024_03_26_003_CreateProductCategories.php
//   │   └── M2024_03_26_004_CreateServiceTags.php
//   └── settings/
//       ├── M2024_03_26_005_UpdateThemeOptions.php
//       └── M2024_03_26_006_UpdateMenuLocations.php
```

### 3. Документирование миграций

```php
/**
 * Миграция для создания структуры каталога продуктов.
 *
 * Создает:
 * - Тип записи 'product'
 * - Таксономию 'product_category'
 * - Мета-поля для продуктов
 *
 * @version 2024_03_26_001
 * @package Theme\Migrations
 */
class CreateProductCatalog extends AbstractMigration
{
    protected string $version = '2024_03_26_001';
    
    public function up(): void
    {
        // Реализация
    }
    
    public function down(): void
    {
        // Реализация
    }
}
```

## Отладка

### 1. Логирование миграций

```php
class LoggingMigrationManager extends MigrationManager
{
    private $logger;
    
    protected function beforeMigrate(AbstractMigration $migration): void
    {
        $this->logger->info(
            "Начало миграции {$migration->getVersion()}"
        );
    }
    
    protected function afterMigrate(AbstractMigration $migration): void
    {
        $this->logger->info(
            "Завершение миграции {$migration->getVersion()}"
        );
    }
}
```

### 2. Отладочная информация

```php
class DebugMigration extends AbstractMigration
{
    public function up(): void
    {
        if (WP_DEBUG) {
            error_log("Миграция {$this->version}: начало выполнения");
        }
        
        // Выполнение миграции
        
        if (WP_DEBUG) {
            error_log("Миграция {$this->version}: завершение");
        }
    }
}
```

## Заключение

Система миграций Fasty предоставляет:
- Надежное управление версиями базы данных
- Безопасное обновление структуры темы
- Возможность отката изменений
- Поддержку зависимостей
- Гибкую систему логирования 