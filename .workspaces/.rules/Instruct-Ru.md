# Технические инструкции по архитектуре WP/Flight с Latte

## Настройка окружения разработки

### Стандарты кодирования (PHPCS)

Для обеспечения высокого качества кода и соблюдения стандартов используется PHP_CodeSniffer (PHPCS) с кастомной конфигурацией.

#### Файл конфигурации PHPCS (`phpcs.xml` в корне проекта)

```xml
<?xml version="1.0"?>
<ruleset name="WPFasty">
    <description>Coding standards for WP FastY YM theme with Latte templates and Symfony-style OOP</description>

    <!-- Scan theme directory specifically -->
    <file>wp-content/themes/wp-fasty/wp-fasty-ym</file>

    <!-- Exclude directories -->
    <exclude-pattern>wp-content/themes/wp-fasty/wp-fasty-ym/vendor/*</exclude-pattern>
    <exclude-pattern>wp-content/themes/wp-fasty/wp-fasty-ym/node_modules/*</exclude-pattern>
    <exclude-pattern>wp-content/themes/wp-fasty/wp-fasty-ym/assets/*/js/vendor/*</exclude-pattern>
    <exclude-pattern>wp-content/themes/wp-fasty/wp-fasty-ym/views/cache/*</exclude-pattern>

    <!-- Базовый стандарт PSR2 -->
    <rule ref="PSR2">
        <exclude name="Generic.Arrays.DisallowShortArraySyntax.Found" />
    </rule>

    <!-- Включение PSR4 для автозагрузки -->
    <rule ref="PSR4" />

    <!-- Включение правил WordPress с исключениями -->
    <rule ref="WordPress">
        <!-- Исключаем правила, конфликтующие с PSR-2 -->
        <exclude name="WordPress.Arrays.ArrayDeclaration" />
        <exclude name="WordPress.Files.FileName.NotHyphenatedLowercase" />
        <exclude name="WordPress.Files.FileName.InvalidClassFileName" />
        <exclude name="WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid" />
        <exclude name="WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase" />
        <exclude name="WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase" />
        <exclude name="WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase" />
        <exclude name="WordPress.PHP.YodaConditions.NotYoda" />
        <exclude name="WordPress.WhiteSpace.ControlStructureSpacing" />
        <exclude name="WordPress.WhiteSpace.OperatorSpacing" />
        <exclude name="Generic.WhiteSpace.DisallowSpaceIndent.SpacesUsed" />
        <exclude name="PEAR.Functions.FunctionCallSignature" />
    </rule>

    <!-- Включение элементов стиля Symfony -->
    <rule ref="Symfony">
        <exclude name="Symfony.Commenting.ClassComment.Missing" />
        <exclude name="Symfony.Commenting.FunctionComment.Missing" />
    </rule>

    <!-- Важные проверки WordPress -->
    <rule ref="WordPress.WP.I18n" />
    <rule ref="WordPress.Security.EscapeOutput" />
    <rule ref="WordPress.Security.NonceVerification" />
    <rule ref="WordPress.WP.EnqueuedResources" />

    <!-- Конфигурация домена для интернационализации -->
    <config name="text_domain" value="wp-fasty-ym"/>

    <!-- Особые правила для Latte шаблонов -->
    <rule ref="Internal.NoCodeFound">
        <exclude-pattern>*.latte</exclude-pattern>
    </rule>

    <!-- Настройка для PHP 8 -->
    <config name="php_version" value="80000" />
</ruleset>
```

#### Настройка VS Code для работы с PHPCS

Файл `.vscode/settings.json` в корне проекта:

```json
{
  "phpcs.enable": true,
  "phpcs.standard": "${workspaceFolder}/phpcs.xml",
  "phpcs.executablePath": "${workspaceFolder}/wp-fasty-ym/vendor/bin/phpcs",
  "phpcs.showSources": true,
  "editor.formatOnSave": true,
  "phpcbf.enable": true,
  "phpcbf.executablePath": "${workspaceFolder}/wp-fasty-ym/vendor/bin/phpcbf",
  "phpcbf.standard": "${workspaceFolder}/phpcs.xml",
  "phpcbf.onsave": true,
  "phpcs.composerJsonPath": "${workspaceFolder}/wp-fasty-ym/composer.json"
}
```

### Правила кодирования

1. **Комментарии** - всегда на английском языке
2. **Имена переменных и функций**:
   - `camelCase` для методов и свойств классов (PSR стиль)
   - `snake_case` для переменных в шаблонах Latte
   - Функции хуков WordPress в соответствии со стандартами WP (`wp_fasty_hook_name`)
3. **HTML5 семантика** - использовать правильные семантические теги вместо div
4. **CSS классы** - использовать семантические префиксы с модификаторами
5. **Автозагрузка классов** - PSR-4 с базовым пространством имен `WPFasty`
6. **DRY** - не дублировать логику, выносить общий код в базовые классы или traits
7. **Единственная ответственность** - каждый класс/шаблон отвечает только за одну функцию

## Структура приложения

### Корневая структура темы WordPress
```
wp-content/themes/wp-fasty/wp-fasty-ym/
├── vendor/
├── composer.json
├── functions.php
├── page-templates/
├── template-parts/
├── views/
├── languages/
├── includes/
├── assets/
├── classes/        # Все PHP классы будут храниться здесь
├── header.php
├── index.php
├── screenshot.png
└── style.css
```

### Структура папки `views/`
```
views/
├── layout/             # Базовые макеты
│   ├── context/        # Контекст для макетов
│   ├── parts/          # Повторно используемые части
│   │   ├── header.latte
│   │   ├── footer.latte
│   │   └── ...
│   └── default.latte   # Основной макет
├── page/               # Шаблоны для страниц
│   ├── context/
│   │   └── data.php    # Определение контекста
│   ├── config/
│   │   └── meta.php    # Метаданные и конфигурация
│   ├── cache/          # Автоматически создается Latte
│   ├── css/
│   │   ├── src/        # Исходные CSS файлы
│   │   └── dist/       # Скомпилированные CSS файлы
│   └── page.latte      # Шаблон страницы
└── ...                 # Другие типы шаблонов
```

## Основные принципы

### Изоляция Core
- Весь код Core находится в директории `classes/Core`
- Core полностью независим от WordPress и не содержит зависимостей от конкретных CMS/фреймворков
- DI-контейнер строится строго на Symfony DI и должен поддерживать замыкания
- При запуске WordPress происходит адаптация Core-логики через адаптеры

### Универсальный подход к хукам
- Используем универсальный синтаксис `{do_action('hook_name')}` во всех шаблонах
- В FlightPHP будет создана совместимая система хуков, аналогичная WordPress
- Это позволит шаблонам работать без изменений при переносе между платформами

### Интеграция с WordPress
- Не вмешиваемся в основной функционал WordPress
- Фронт-контроллеры могут использовать стандартные возможности WordPress
- Недостающие элементы будут использовать дефолтные шаблоны WordPress

### Система именования и стили
- Используем понятные, интуитивные названия классов: `button button-primary button-small`
- Utility-first подход применяется только на этапе сборки (UnoCSS, Tailwind)
- CSS переменные для темизации (светлая/темная) используют современные возможности (OKLCH)

## Точки входа (Front Controllers)

### `page.php` (WordPress)
```php
<?php
/**
 * Page template
 *
 * @package WPFasty
 */

namespace WPFasty;

if (!defined('ABSPATH')) {
    exit;
}

// Load page template
$theme = Core\Application::getInstance()->getTheme();
$context = $theme->context();  // Собирает контекст из WordPress
echo $theme->render('page/page', $context);
```

### `page.php` (Flight PHP)
```php
<?php
/**
 * Page route handler
 *
 * @package FlightFasty
 */

namespace FlightFasty;

// Route definition
Flight::route('/page/@slug', function($slug) {
    // Get page data from database or API
    $pageModel = new Model\Page();
    $pageData = $pageModel->getBySlug($slug);
    
    // Initialize theme and context
    $theme = Core\Application::getInstance()->getTheme();
    $context = $theme->context($pageData);
    
    // Render template
    echo $theme->render('page/page', $context);
});
```

## Модели и адаптеры данных

### `src/Model/Page.php`
```php
<?php
/**
 * Page model - source-agnostic
 *
 * @package Fasty
 */

namespace WPFasty\Model;

use WPFasty\Adapter\DataSourceInterface;

class Page implements ModelInterface {
    protected $adapter;
    
    /**
     * Constructor with dependency injection
     *
     * @param DataSourceInterface $adapter Data source adapter
     */
    public function __construct(DataSourceInterface $adapter = null) {
        // Use provided adapter or get default from container
        $this->adapter = $adapter ?? Application::getInstance()->get('adapter.default');
    }
    
    /**
     * Get page data by ID
     *
     * @param int $id Page ID
     * @return array Page data
     */
    public function getById($id) {
        return $this->adapter->getPage($id);
    }
    
    // Additional methods
}
```

### `src/Adapter/WordPressAdapter.php`
```php
<?php
/**
 * WordPress-specific data adapter
 *
 * @package WPFasty
 */

namespace WPFasty\Adapter;

class WordPressAdapter implements DataSourceInterface {
    /**
     * Get page data
     *
     * @param int $id Page ID
     * @return array Normalized page data
     */
    public function getPage($id) {
        $page = get_post($id);
        
        // Transform WP-specific data to source-agnostic format
        return [
            'id' => $page->ID,
            'title' => $page->post_title,
            'content' => $page->post_content,
            'slug' => $page->post_name,
            'meta' => get_post_meta($id)
        ];
    }
    
    // More methods for other data types
}
```

## Слой представления

### `src/View/Theme.php`
```php
<?php
/**
 * Theme abstraction for template rendering
 *
 * @package Fasty
 */

namespace WPFasty\View;

use Latte\Engine;

class Theme {
    protected $latte;
    protected $viewsPath;
    protected $cachePath;
    
    /**
     * Initialize Latte engine
     */
    public function __construct() {
        $this->viewsPath = get_stylesheet_directory() . '/views';
        $this->cachePath = get_stylesheet_directory() . '/views/cache';
        
        $this->latte = new Engine();
        $this->latte->setTempDirectory($this->cachePath);
        
        // Register custom macros and filters
        $this->registerCustomMacros();
        $this->registerFilters();
    }
    
    /**
     * Build context for templates
     *
     * @param array $extraData Additional data to include
     * @return array Complete context
     */
    public function context(array $extraData = []) {
        $context = [
            'site' => $this->getSiteContext(),
            'current' => $this->getCurrentContext()
        ];
        
        // Merge extra data
        return array_merge($context, $extraData);
    }
    
    /**
     * Render a template
     *
     * @param string $template Template path relative to views directory
     * @param array $context Context data
     * @param array $args Additional rendering arguments
     * @return string Rendered template
     */
    public function render($template, array $context = [], array $args = []) {
        $templatePath = $this->viewsPath . '/' . $template . '.latte';
        
        // Filter context based on template requirements
        $filteredContext = $this->filterContext($template, $context);
        
        return $this->latte->renderToString($templatePath, $filteredContext);
    }
    
    /**
     * Filter context based on template requirements
     *
     * @param string $template Template path
     * @param array $context Full context
     * @return array Filtered context
     */
    protected function filterContext($template, array $context) {
        $configPath = dirname($this->viewsPath . '/' . $template) . '/config/meta.php';
        
        if (file_exists($configPath)) {
            $config = include $configPath;
            
            if (isset($config['required_context'])) {
                // Only include required context fields
                return array_intersect_key($context, array_flip($config['required_context']));
            }
        }
        
        return $context;
    }
    
    /**
     * Register WordPress/framework-agnostic macros for Latte
     */
    protected function registerCustomMacros() {
        $macroSet = new \Latte\Macros\MacroSet($this->latte->getCompiler());
        
        // Universal action hook macro (framework agnostic name)
        $macroSet->addMacro('do_action', function($node, $writer) {
            return $writer->write('<?php do_action(%node.args); ?>');
        });
        
        // Other universal macros
    }
    
    /**
     * Get site-wide context data
     *
     * @return array Site context
     */
    protected function getSiteContext() {
        // Implementation will differ between WP and Flight
        // but the structure should be identical
    }
    
    /**
     * Get current request context
     *
     * @return array Current page/post context
     */
    protected function getCurrentContext() {
        // Implementation will differ between WP and Flight
        // but the structure should be identical
    }
}
```

## Шаблоны Latte

### `views/layout/default.latte`
```latte
{** 
 * Default layout template
 *
 * Required context:
 * - site: Site-wide data
 * - content: Main content HTML
 *}
<!DOCTYPE html>
<html lang="{$site->lang}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$site->title}</title>
    
    {* Inline critical CSS *}
    <style>
      {include "../css/dist/critical.css"|noescape}
    </style>
    
    {* Async load full CSS *}
    <link rel="preload" href="{$basePath}/views/page/css/dist/styles.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="{$basePath}/views/page/css/dist/styles.css"></noscript>
    
    {* Universal action hook - framework agnostic *}
    {do_action 'head'}
</head>
<body class="theme-light">
    <header class="site-header">
        {include 'parts/header.latte'}
    </header>

    <main class="site-content">
        {do_action 'before_content'}
        
        {$content|noescape}
        
        {do_action 'after_content'}
    </main>

    <footer class="site-footer">
        {include 'parts/footer.latte'}
    </footer>
    
    {do_action 'footer'}
</body>
</html>
```

### `views/page/page.latte`
```latte
{**
 * Basic page template
 *
 * Required context:
 * - page: Page data
 *}

{* Define content to be inserted into layout *}
{var $content}
    <article class="page">
        <header class="page-header">
            <h1 class="page-title">{$page->title}</h1>
        </header>
        
        <div class="page-content">
            {$page->content|noescape}
        </div>
    </article>
{/var}

{* Extend the default layout with our content *}
{layout '../layout/default.latte', content => $content}
```

### `views/page/config/meta.php`
```php
<?php
/**
 * Page template configuration
 */
return [
    // Defines exactly what context fields this template requires
    'required_context' => [
        'site',
        'page'
    ],
    
    // Custom template options
    'options' => [
        'cache_lifetime' => 3600, // 1 hour
    ]
];
```

## Пример семантического именования в шаблоне

```latte
<button class="button button-primary button-rounded">
  {$label}
</button>

<div class="card card-featured">
  <div class="card-header">
    <h3 class="card-title">{$title}</h3>
  </div>
  <div class="card-content">
    {$content|noescape}
  </div>
</div>
```

## Пример CSS переменных и темизации

```css
:root {
    --background: oklch(0.95 0.01 230);
    --foreground: oklch(0.25 0.05 240);
    --primary: oklch(0.62 0.15 242.99);
    --primary-foreground: oklch(0.95 0.01 0);
    --card: oklch(0.90 0.02 230);
    --card-foreground: oklch(0.25 0.05 240);
    --radius: 0.75rem;
    /* ... другие переменные ... */
}
  
.dark {
    --background: oklch(0.20 0.06 240);
    --foreground: oklch(0.90 0.02 225);
    --primary: oklch(0.50 0.15 243.11);
    /* ... другие переменные для темной темы ... */
}

@layer base {
    * {
        @apply border-border outline-ring/50;
    }

    body {
        @apply bg-background text-foreground;
    }
}
```

## Правила кодирования

1. **Комментарии** - всегда на английском языке
2. **Имена переменных и функций** - `camelCase` для методов, `snake_case` для переменных в шаблонах
3. **HTML5 семантика** - использовать правильные семантические теги вместо div
4. **CSS классы** - использовать семантические префиксы с модификаторами
5. **PSR-4** для автозагрузки классов
6. **DRY** - не дублировать логику, выносить общий код в базовые классы или traits
7. **Единственная ответственность** - каждый класс/шаблон отвечает только за одну функцию

## Оптимизация производительности

1. **Встроенные критические стили**
   - Критический CSS встраивается непосредственно в `<head>` страницы
   - Остальные стили загружаются асинхронно для ускорения первого рендеринга
   - Генерация только необходимых стилей для каждого шаблона

2. **Минимизация внешних зависимостей**
   - Все нужные ресурсы должны быть локальными, без внешних API вызовов
   - Предкомпиляция всех шаблонов для минимизации времени выполнения

3. **Агностичный подход к расширениям**
   - Шаблоны не должны иметь специфичных для платформы конструкций
   - Все расширения должны быть универсально применимы или легко трансформируемы

## Миграция между платформами

При миграции с WordPress на Flight PHP:

1. Сохраняются все файлы в папке `views/`
2. Заменяются адаптеры данных
3. Обновляются контроллеры (точки входа)
4. Core функциональность переписывается под новую платформу

При правильной реализации, смена фреймворка или CMS потребует только изменения адаптеров данных и контроллеров, в то время как представление (views) останется неизменным.

## Команды для работы со стандартами кода

```bash
# Проверка соответствия стандартам
composer phpcs

# Автоматическое исправление ошибок, где возможно
composer phpcbf
```

=======================

Да, совершенно согласен, что можно обойтись без YAML-конфигурации и использовать чистый PHP для настройки контейнера сервисов. Это более прямолинейный подход, который уменьшает количество зависимостей.

Целесообразность использования Symfony в WordPress-теме имеет несколько веских причин:

1. **Повышение качества кода**. Symfony продвигает хорошие практики проектирования, такие как SOLID, чистая архитектура и разделение ответственности. Это значительно улучшает поддерживаемость кода в долгосрочной перспективе.

2. **Трансфер знаний и навыков**. Использование Symfony в WordPress позволяет разработчикам применять одни и те же паттерны и принципы в разных проектах, независимо от базовой платформы. Это экономит время на переобучение.

3. **Подготовка к миграции**. Если в будущем потребуется перенести логику из WordPress в полноценное Symfony-приложение, имея уже структурированный код, будет намного проще выполнить эту задачу.

4. **Тестируемость**. Код, построенный с использованием DI, значительно легче тестировать с помощью юнит-тестов.

Что касается шаблонизаторов: Symfony действительно хорошо интегрируется с Twig, но также может работать с Latte без проблем. Latte имеет свои преимущества, такие как более PHP-подобный синтаксис, что может быть удобно для разработчиков WordPress. Вам просто потребуется создать сервис, отвечающий за рендеринг шаблонов Latte и зарегистрировать его в контейнере.

Использование PHP-конфигурации вместо YAML — это разумный выбор, особенно учитывая, что PHP-конфигурация:
1. Обеспечивает строгую типизацию
2. Даёт подсказки IDE и проверку синтаксиса
3. Позволяет использовать всю мощь PHP для создания более сложных конфигураций
4. Уменьшает зависимости проекта (не нужен yaml-компонент)

В контексте WordPress, где уже используется PHP, добавление еще одного синтаксиса (YAML) может только усложнить понимание кода для новых разработчиков проекта.
