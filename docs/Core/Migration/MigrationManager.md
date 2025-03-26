# Класс MigrationManager

`MigrationManager` - это класс, отвечающий за управление миграциями в Fasty. Он предоставляет централизованный способ регистрации, выполнения и отката миграций.

## Основные возможности

- Регистрация миграций
- Выполнение миграций
- Откат миграций
- Управление зависимостями
- Логирование операций
- Обработка ошибок

## Методы

### register()

```php
public function register(string $migrationClass): self
```

Регистрирует новую миграцию.

#### Параметры
- `$migrationClass` (string) - Имя класса миграции

#### Пример использования
```php
$manager->register(CreateCustomTable::class)
        ->register(UpdateThemeOptions::class)
        ->register(InstallPluginSupport::class);
```

### migrate()

```php
public function migrate(): void
```

Выполняет все зарегистрированные миграции.

#### Пример использования
```php
try {
    $manager->migrate();
    update_option('theme_version', $manager->getLastVersion());
} catch (Exception $e) {
    error_log('Ошибка миграции: ' . $e->getMessage());
}
```

### rollback()

```php
public function rollback(string $version): void
```

Откатывает миграции до указанной версии.

#### Параметры
- `$version` (string) - Версия, до которой нужно откатить

#### Пример использования
```php
try {
    $manager->rollback('2024_03_26_001');
    update_option('theme_version', '2024_03_26_001');
} catch (Exception $e) {
    error_log('Ошибка отката: ' . $e->getMessage());
}
```

### getLastVersion()

```php
public function getLastVersion(): string
```

Возвращает версию последней выполненной миграции.

#### Пример использования
```php
$version = $manager->getLastVersion();
echo "Текущая версия: {$version}";
```

### getMigrations()

```php
public function getMigrations(): array
```

Возвращает список всех зарегистрированных миграций.

#### Пример использования
```php
$migrations = $manager->getMigrations();
foreach ($migrations as $migration) {
    echo $migration->getVersion() . "\n";
}
```

## Примеры использования

### 1. Базовая настройка

```php
class ThemeInstaller
{
    private MigrationManager $migrations;
    
    public function __construct(MigrationManager $migrations)
    {
        $this->migrations = $migrations;
    }
    
    public function install(): void
    {
        // Регистрация миграций
        $this->migrations
            ->register(CreateDatabase::class)
            ->register(InstallTables::class)
            ->register(SetupThemeOptions::class);
        
        try {
            // Выполнение миграций
            $this->migrations->migrate();
            
            // Сохранение версии
            update_option('theme_version', $this->migrations->getLastVersion());
        } catch (Exception $e) {
            // Обработка ошибок
            error_log('Ошибка установки: ' . $e->getMessage());
        }
    }
}
```

### 2. Условное выполнение

```php
class ThemeUpgrader
{
    private MigrationManager $migrations;
    
    public function upgrade(): void
    {
        // Проверка текущей версии
        $currentVersion = get_option('theme_version', '0.0.0');
        
        // Регистрация только нужных миграций
        if (version_compare($currentVersion, '2.0.0', '<')) {
            $this->migrations->register(UpgradeTo20::class);
        }
        
        if (version_compare($currentVersion, '2.1.0', '<')) {
            $this->migrations->register(UpgradeTo21::class);
        }
        
        // Выполнение миграций
        $this->migrations->migrate();
    }
}
```

### 3. Обработка зависимостей

```php
class PluginMigrations
{
    private MigrationManager $migrations;
    
    public function setup(): void
    {
        // Базовые миграции
        $this->migrations->register(CreateTables::class);
        
        // Миграции WooCommerce
        if (class_exists('WooCommerce')) {
            $this->migrations
                ->register(CreateProductFields::class)
                ->register(SetupWooCommerceIntegration::class);
        }
        
        // Миграции ACF
        if (class_exists('ACF')) {
            $this->migrations
                ->register(CreateCustomFields::class)
                ->register(SetupACFIntegration::class);
        }
        
        // Выполнение миграций
        $this->migrations->migrate();
    }
}
```

## Лучшие практики

### 1. Группировка миграций

```php
class ModularMigrations
{
    private MigrationManager $migrations;
    
    public function setup(): void
    {
        // Регистрация по модулям
        $this->registerCoreMigrations();
        $this->registerThemeMigrations();
        $this->registerPluginMigrations();
        
        // Выполнение всех миграций
        $this->migrations->migrate();
    }
    
    private function registerCoreMigrations(): void
    {
        $this->migrations
            ->register(CreateDatabase::class)
            ->register(InstallTables::class);
    }
    
    private function registerThemeMigrations(): void
    {
        $this->migrations
            ->register(SetupThemeOptions::class)
            ->register(CreateCustomPostTypes::class);
    }
    
    private function registerPluginMigrations(): void
    {
        if (class_exists('WooCommerce')) {
            $this->migrations->register(WooCommerceSetup::class);
        }
    }
}
```

### 2. Обработка ошибок

```php
class SafeMigrationManager
{
    private MigrationManager $migrations;
    private LoggerInterface $logger;
    
    public function migrate(): void
    {
        try {
            // Начало миграции
            $this->logger->info('Начало миграции');
            
            // Выполнение миграций
            $this->migrations->migrate();
            
            // Успешное завершение
            $this->logger->info('Миграция завершена', [
                'version' => $this->migrations->getLastVersion()
            ]);
        } catch (PDOException $e) {
            // Ошибки базы данных
            $this->logger->error('Ошибка базы данных', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (Exception $e) {
            // Общие ошибки
            $this->logger->error('Ошибка миграции', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

### 3. Отслеживание состояния

```php
class StateAwareMigrationManager
{
    private MigrationManager $migrations;
    
    public function migrate(): void
    {
        // Проверка состояния
        $this->checkState();
        
        // Сохранение текущей версии
        $oldVersion = get_option('theme_version');
        
        try {
            // Выполнение миграций
            $this->migrations->migrate();
            
            // Обновление версии
            update_option('theme_version', $this->migrations->getLastVersion());
            
            // Очистка кэша
            wp_cache_flush();
        } catch (Exception $e) {
            // Восстановление версии при ошибке
            update_option('theme_version', $oldVersion);
            throw $e;
        }
    }
    
    private function checkState(): void
    {
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            throw new RuntimeException('Недостаточно прав');
        }
        
        // Проверка режима обслуживания
        if (defined('WP_MAINTENANCE_MODE') && WP_MAINTENANCE_MODE) {
            throw new RuntimeException('Сайт в режиме обслуживания');
        }
    }
}
```

## Заключение

`MigrationManager` предоставляет:
- Централизованное управление миграциями
- Автоматическое разрешение зависимостей
- Безопасное выполнение и откат миграций
- Логирование операций
- Обработку ошибок
- Отслеживание состояния


