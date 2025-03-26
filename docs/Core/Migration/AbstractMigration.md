# Класс AbstractMigration

`AbstractMigration` - это базовый класс для создания миграций в Fasty. Он реализует `MigrationInterface` и предоставляет основные методы для работы с миграциями.

## Основные возможности

- Базовая реализация методов интерфейса
- Управление транзакциями
- Логирование операций
- Обработка ошибок
- Вспомогательные методы

## Свойства

### version

```php
protected string $version;
```

Версия миграции.

### dependencies

```php
protected array $dependencies = [];
```

Список зависимостей миграции.

### logger

```php
protected LoggerInterface $logger;
```

Объект для логирования.

## Методы

### getVersion()

```php
public function getVersion(): string
```

Возвращает версию миграции.

#### Пример использования
```php
class ThemeMigration extends AbstractMigration
{
    protected string $version = '2024_03_26_001';
}

$migration = new ThemeMigration();
echo $migration->getVersion(); // '2024_03_26_001'
```

### getDependencies()

```php
public function getDependencies(): array
```

Возвращает список зависимостей миграции.

#### Пример использования
```php
class ThemeMigration extends AbstractMigration
{
    protected array $dependencies = [
        CreateDatabase::class,
        InstallTables::class
    ];
}

$migration = new ThemeMigration();
$deps = $migration->getDependencies();
```

### shouldRun()

```php
public function shouldRun(): bool
```

Определяет, должна ли выполняться миграция.

#### Пример использования
```php
class ThemeMigration extends AbstractMigration
{
    public function shouldRun(): bool
    {
        // Проверка условий
        return !$this->isInstalled() && 
               $this->checkRequirements();
    }
}
```

### beginTransaction()

```php
protected function beginTransaction(): void
```

Начинает транзакцию.

#### Пример использования
```php
class ThemeMigration extends AbstractMigration
{
    public function up(): void
    {
        try {
            $this->beginTransaction();
            
            // Выполнение операций
            
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

### commit()

```php
protected function commit(): void
```

Фиксирует транзакцию.

### rollback()

```php
protected function rollback(): void
```

Откатывает транзакцию.

### log()

```php
protected function log(string $message, array $context = []): void
```

Записывает сообщение в лог.

#### Параметры
- `$message` (string) - Сообщение для логирования
- `$context` (array) - Контекст сообщения

#### Пример использования
```php
class ThemeMigration extends AbstractMigration
{
    public function up(): void
    {
        $this->log('Начало миграции', [
            'version' => $this->version
        ]);
        
        // Выполнение операций
        
        $this->log('Миграция завершена');
    }
}
```

## Примеры использования

### 1. Базовая миграция

```php
class CreateCustomTable extends AbstractMigration
{
    protected string $version = '2024_03_26_001';
    
    public function up(): void
    {
        try {
            $this->beginTransaction();
            
            global $wpdb;
            
            // Создание таблицы
            $wpdb->query("
                CREATE TABLE IF NOT EXISTS {$wpdb->prefix}custom_table (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) {$wpdb->get_charset_collate()};
            ");
            
            $this->commit();
            $this->log('Таблица создана успешно');
        } catch (Exception $e) {
            $this->rollback();
            $this->log('Ошибка создания таблицы', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function down(): void
    {
        try {
            $this->beginTransaction();
            
            global $wpdb;
            
            // Удаление таблицы
            $wpdb->query("
                DROP TABLE IF EXISTS {$wpdb->prefix}custom_table;
            ");
            
            $this->commit();
            $this->log('Таблица удалена успешно');
        } catch (Exception $e) {
            $this->rollback();
            $this->log('Ошибка удаления таблицы', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

### 2. Миграция с проверками

```php
class UpdateThemeOptions extends AbstractMigration
{
    protected string $version = '2024_03_26_002';
    
    public function shouldRun(): bool
    {
        // Проверка версии WordPress
        if (!$this->checkWordPressVersion()) {
            $this->log('Требуется WordPress 5.0+');
            return false;
        }
        
        // Проверка наличия опций
        if ($this->optionsExist()) {
            $this->log('Опции уже установлены');
            return false;
        }
        
        return true;
    }
    
    private function checkWordPressVersion(): bool
    {
        return version_compare(get_bloginfo('version'), '5.0.0', '>=');
    }
    
    private function optionsExist(): bool
    {
        return get_option('theme_options') !== false;
    }
    
    public function up(): void
    {
        try {
            $this->beginTransaction();
            
            // Установка опций
            update_option('theme_options', [
                'version' => $this->version,
                'layout' => 'modern',
                'colors' => 'dark'
            ]);
            
            $this->commit();
            $this->log('Опции темы обновлены');
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

### 3. Миграция с зависимостями

```php
class UpdatePostMeta extends AbstractMigration
{
    protected string $version = '2024_03_26_003';
    protected array $dependencies = [
        CreateCustomTable::class,
        UpdateThemeOptions::class
    ];
    
    public function up(): void
    {
        try {
            $this->beginTransaction();
            
            // Получение постов
            $posts = get_posts([
                'post_type' => 'post',
                'posts_per_page' => -1
            ]);
            
            foreach ($posts as $post) {
                // Обновление мета-данных
                update_post_meta(
                    $post->ID,
                    '_theme_version',
                    $this->version
                );
            }
            
            $this->commit();
            $this->log('Мета-данные обновлены', [
                'count' => count($posts)
            ]);
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

## Лучшие практики

### 1. Обработка ошибок

```php
class SafeMigration extends AbstractMigration
{
    public function up(): void
    {
        try {
            $this->beginTransaction();
            
            // Операции с базой данных
            
            $this->commit();
        } catch (PDOException $e) {
            $this->rollback();
            $this->log('Ошибка базы данных', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            throw $e;
        } catch (Exception $e) {
            $this->rollback();
            $this->log('Общая ошибка', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
```

### 2. Проверка состояния

```php
class StatefulMigration extends AbstractMigration
{
    private function checkState(): void
    {
        // Проверка прав доступа
        if (!current_user_can('manage_options')) {
            throw new RuntimeException(
                'Недостаточно прав для выполнения миграции'
            );
        }
        
        // Проверка состояния базы данных
        global $wpdb;
        if ($wpdb->last_error) {
            throw new RuntimeException(
                'База данных в некорректном состоянии'
            );
        }
    }
    
    public function up(): void
    {
        $this->checkState();
        
        try {
            $this->beginTransaction();
            // Выполнение миграции
            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }
}
```

### 3. Документирование миграций

```php
/**
 * Миграция для обновления структуры темы.
 *
 * Выполняет:
 * 1. Создание таблиц
 * 2. Установку опций
 * 3. Обновление мета-данных
 *
 * Требования:
 * - WordPress 5.0+
 * - PHP 7.4+
 * - MySQL 5.7+
 *
 * @version 2024_03_26_004
 */
class ThemeStructureMigration extends AbstractMigration
{
    protected string $version = '2024_03_26_004';
    
    public function shouldRun(): bool
    {
        return $this->checkRequirements();
    }
    
    private function checkRequirements(): bool
    {
        // Проверка версии PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->log('Требуется PHP 7.4+');
            return false;
        }
        
        // Проверка версии MySQL
        global $wpdb;
        if (version_compare($wpdb->db_version(), '5.7', '<')) {
            $this->log('Требуется MySQL 5.7+');
            return false;
        }
        
        return true;
    }
}
```

## Заключение

`AbstractMigration` предоставляет:
- Базовую реализацию интерфейса миграций
- Управление транзакциями
- Систему логирования
- Обработку ошибок
- Проверку состояния
- Документирование миграций


