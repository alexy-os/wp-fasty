# Интерфейс MigrationInterface

`MigrationInterface` - это основной интерфейс для создания миграций в Fasty. Он определяет контракт, которому должны следовать все миграции в системе.

## Основные концепции

- Версионирование миграций
- Двунаправленные операции (up/down)
- Условное выполнение
- Управление зависимостями

## Методы интерфейса

### getVersion()

```php
public function getVersion(): string;
```

Возвращает версию миграции.

#### Пример реализации
```php
class ExampleMigration implements MigrationInterface
{
    protected string $version = '2024_03_26_001';
    
    public function getVersion(): string
    {
        return $this->version;
    }
}
```

### up()

```php
public function up(): void;
```

Выполняет миграцию.

#### Пример реализации
```php
class ExampleMigration implements MigrationInterface
{
    public function up(): void
    {
        // Создание таблицы
        global $wpdb;
        
        $wpdb->query("
            CREATE TABLE IF NOT EXISTS {$wpdb->prefix}custom_table (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                name varchar(255) NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) {$wpdb->get_charset_collate()};
        ");
    }
}
```

### down()

```php
public function down(): void;
```

Отменяет миграцию.

#### Пример реализации
```php
class ExampleMigration implements MigrationInterface
{
    public function down(): void
    {
        // Удаление таблицы
        global $wpdb;
        
        $wpdb->query("
            DROP TABLE IF EXISTS {$wpdb->prefix}custom_table;
        ");
    }
}
```

### getDependencies()

```php
public function getDependencies(): array;
```

Возвращает список зависимостей миграции.

#### Пример реализации
```php
class ExampleMigration implements MigrationInterface
{
    protected array $dependencies = [
        CreateCustomTable::class,
        UpdateOptions::class
    ];
    
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
}
```

### shouldRun()

```php
public function shouldRun(): bool;
```

Определяет, должна ли выполняться миграция.

#### Пример реализации
```php
class ExampleMigration implements MigrationInterface
{
    public function shouldRun(): bool
    {
        // Проверка условий
        return !$this->isAlreadyInstalled() && 
               $this->meetsRequirements();
    }
    
    private function isAlreadyInstalled(): bool
    {
        return get_option('feature_installed') === true;
    }
    
    private function meetsRequirements(): bool
    {
        return version_compare(get_bloginfo('version'), '5.0.0', '>=');
    }
}
```

## Примеры использования

### 1. Базовая миграция

```php
class CreateCustomTable implements MigrationInterface
{
    protected string $version = '2024_03_26_001';
    protected array $dependencies = [];
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    
    public function shouldRun(): bool
    {
        return true;
    }
    
    public function up(): void
    {
        // Реализация миграции
    }
    
    public function down(): void
    {
        // Реализация отката
    }
}
```

### 2. Миграция с зависимостями

```php
class UpdateTableData implements MigrationInterface
{
    protected string $version = '2024_03_26_002';
    protected array $dependencies = [
        CreateCustomTable::class
    ];
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    
    public function shouldRun(): bool
    {
        global $wpdb;
        
        // Проверка существования таблицы
        $table = $wpdb->prefix . 'custom_table';
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }
    
    public function up(): void
    {
        // Обновление данных
    }
    
    public function down(): void
    {
        // Откат изменений
    }
}
```

### 3. Условная миграция

```php
class InstallPluginSupport implements MigrationInterface
{
    protected string $version = '2024_03_26_003';
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function getDependencies(): array
    {
        return [];
    }
    
    public function shouldRun(): bool
    {
        // Проверка наличия плагина
        return is_plugin_active('woocommerce/woocommerce.php');
    }
    
    public function up(): void
    {
        // Установка поддержки плагина
    }
    
    public function down(): void
    {
        // Удаление поддержки плагина
    }
}
```

## Лучшие практики

### 1. Атомарность операций

```php
class AtomicMigration implements MigrationInterface
{
    public function up(): void
    {
        global $wpdb;
        
        // Начало транзакции
        $wpdb->query('START TRANSACTION');
        
        try {
            // Выполнение операций
            $this->createTable();
            $this->insertData();
            $this->updateOptions();
            
            // Фиксация изменений
            $wpdb->query('COMMIT');
        } catch (Exception $e) {
            // Откат при ошибке
            $wpdb->query('ROLLBACK');
            throw $e;
        }
    }
}
```

### 2. Проверка состояния

```php
class StatefulMigration implements MigrationInterface
{
    public function shouldRun(): bool
    {
        // Проверка версии WordPress
        if (!$this->checkWordPressVersion()) {
            return false;
        }
        
        // Проверка наличия плагинов
        if (!$this->checkRequiredPlugins()) {
            return false;
        }
        
        // Проверка прав доступа
        if (!$this->checkPermissions()) {
            return false;
        }
        
        return true;
    }
    
    private function checkWordPressVersion(): bool
    {
        return version_compare(get_bloginfo('version'), '5.0.0', '>=');
    }
    
    private function checkRequiredPlugins(): bool
    {
        return class_exists('WooCommerce');
    }
    
    private function checkPermissions(): bool
    {
        return current_user_can('manage_options');
    }
}
```

### 3. Документирование миграций

```php
/**
 * Миграция для обновления структуры данных.
 *
 * Выполняет:
 * 1. Создание новой таблицы
 * 2. Перенос данных из старой таблицы
 * 3. Обновление связей
 *
 * Зависимости:
 * - CreateInitialStructure
 * - UpdateDataFormat
 *
 * @version 2024_03_26_004
 */
class UpdateDataStructure implements MigrationInterface
{
    protected string $version = '2024_03_26_004';
    protected array $dependencies = [
        CreateInitialStructure::class,
        UpdateDataFormat::class
    ];
    
    // Реализация методов
}
```

## Заключение

`MigrationInterface` предоставляет:
- Стандартизированный способ создания миграций
- Управление версиями и зависимостями
- Возможность отката изменений
- Условное выполнение миграций
- Атомарность операций


