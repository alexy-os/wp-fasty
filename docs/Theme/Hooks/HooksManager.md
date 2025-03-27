# Класс HooksManager

`HooksManager` - это класс, отвечающий за управление хуками в Fasty. Он предоставляет централизованный способ регистрации, загрузки и управления хуками WordPress.

## Основные возможности

- Регистрация хуков через контейнер зависимостей
- Автоматическая загрузка хуков при инициализации
- Управление приоритетами выполнения хуков
- Условная регистрация хуков
- Отложенная загрузка хуков

## Методы

### register()

```php
public function register(string $hookClass): self
```

Регистрирует новый хук в менеджере.

#### Параметры
- `$hookClass` (string) - Имя класса хука, реализующего `HookInterface`

#### Пример использования
```php
$manager->register(ThemeSetupHook::class);
$manager->register(ContentHook::class);
$manager->register(AdminHook::class);
```

### boot()

```php
public function boot(): void
```

Загружает все зарегистрированные хуки.

#### Пример использования
```php
// В файле bootstrap.php
$hooksManager = $container->get(HooksManager::class);
$hooksManager->boot();
```

### getHooks()

```php
public function getHooks(): array
```

Возвращает массив всех зарегистрированных хуков.

## Примеры использования

### 1. Базовая регистрация хуков

```php
class ThemeBootstrap
{
    private $hooksManager;
    
    public function __construct(HooksManager $hooksManager)
    {
        $this->hooksManager = $hooksManager;
    }
    
    public function initialize(): void
    {
        // Регистрация основных хуков темы
        $this->hooksManager
            ->register(ThemeSetupHook::class)
            ->register(AssetsHook::class)
            ->register(MenuHook::class)
            ->register(SidebarHook::class)
            ->boot();
    }
}
```

### 2. Условная регистрация хуков

```php
class PluginCompatibility
{
    private $hooksManager;
    
    public function __construct(HooksManager $hooksManager)
    {
        $this->hooksManager = $hooksManager;
    }
    
    public function initialize(): void
    {
        // WooCommerce хуки
        if (class_exists('WooCommerce')) {
            $this->hooksManager->register(WooCommerceHook::class);
        }
        
        // ACF хуки
        if (class_exists('ACF')) {
            $this->hooksManager->register(ACFHook::class);
        }
        
        // Загрузка хуков
        $this->hooksManager->boot();
    }
}
```

### 3. Работа с приоритетами

```php
class ContentManager
{
    private $hooksManager;
    
    public function __construct(HooksManager $hooksManager)
    {
        $this->hooksManager = $hooksManager;
    }
    
    public function initialize(): void
    {
        // Регистрация хуков с разными приоритетами
        $this->hooksManager
            ->register(ContentSanitizerHook::class) // Приоритет 5
            ->register(ContentFormatterHook::class) // Приоритет 10
            ->register(ContentEnhancerHook::class)  // Приоритет 15
            ->boot();
    }
}
```

### 4. Отложенная загрузка хуков

```php
class AdminManager
{
    private $hooksManager;
    
    public function __construct(HooksManager $hooksManager)
    {
        $this->hooksManager = $hooksManager;
    }
    
    public function initialize(): void
    {
        if (!is_admin()) {
            return;
        }
        
        // Регистрация админ хуков
        $this->hooksManager
            ->register(AdminMenuHook::class)
            ->register(AdminAssetsHook::class)
            ->register(AdminCustomizerHook::class)
            ->boot();
    }
}
```

## Лучшие практики

### 1. Организация хуков по модулям

```php
class ModuleManager
{
    private $hooksManager;
    
    public function __construct(HooksManager $hooksManager)
    {
        $this->hooksManager = $hooksManager;
    }
    
    public function initialize(): void
    {
        // Основные хуки
        $this->registerCoreHooks();
        
        // Хуки темы
        $this->registerThemeHooks();
        
        // Админ хуки
        $this->registerAdminHooks();
        
        // Загрузка всех хуков
        $this->hooksManager->boot();
    }
    
    private function registerCoreHooks(): void
    {
        $this->hooksManager
            ->register(CoreSetupHook::class)
            ->register(CoreAssetsHook::class);
    }
    
    private function registerThemeHooks(): void
    {
        $this->hooksManager
            ->register(ThemeSetupHook::class)
            ->register(ThemeAssetsHook::class);
    }
    
    private function registerAdminHooks(): void
    {
        if (is_admin()) {
            $this->hooksManager
                ->register(AdminMenuHook::class)
                ->register(AdminAssetsHook::class);
        }
    }
}
```

### 2. Обработка ошибок

```php
class ErrorAwareHooksManager
{
    private $hooksManager;
    private $logger;
    
    public function __construct(
        HooksManager $hooksManager,
        LoggerInterface $logger
    ) {
        $this->hooksManager = $hooksManager;
        $this->logger = $logger;
    }
    
    public function safeRegister(string $hookClass): void
    {
        try {
            $this->hooksManager->register($hookClass);
        } catch (Exception $e) {
            $this->logger->error(
                "Ошибка регистрации хука {$hookClass}: " . $e->getMessage()
            );
        }
    }
    
    public function safeBoot(): void
    {
        try {
            $this->hooksManager->boot();
        } catch (Exception $e) {
            $this->logger->error(
                "Ошибка загрузки хуков: " . $e->getMessage()
            );
        }
    }
}
```

### 3. Тестирование хуков

```php
class HookTester
{
    private $hooksManager;
    
    public function __construct(HooksManager $hooksManager)
    {
        $this->hooksManager = $hooksManager;
    }
    
    public function testHook(string $hookClass): bool
    {
        try {
            // Регистрация хука
            $this->hooksManager->register($hookClass);
            
            // Получение экземпляра хука
            $hooks = $this->hooksManager->getHooks();
            $hook = end($hooks);
            
            // Проверка методов
            if (!method_exists($hook, 'register')) {
                throw new Exception('Метод register не найден');
            }
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
```

## Заключение

`HooksManager` предоставляет:
- Централизованное управление хуками
- Гибкую систему регистрации
- Поддержку приоритетов
- Условную загрузку
- Интеграцию с контейнером зависимостей
- Возможности для тестирования и отладки


