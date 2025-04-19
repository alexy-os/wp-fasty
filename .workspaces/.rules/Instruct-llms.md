# WP/Flight Architecture with Latte - LLM Agent Instructions

## Core Architecture Concepts

1. **Template Engine**: Latte templates with framework-agnostic constructs and built-in caching
2. **Hooks System**: Universal syntax `{do_action('hook_name')}` across all templates
3. **HTML5 Semantic Markup**: Prioritize semantic tags, minimize nesting
4. **CSS Naming**: Semantic class naming with modifiers (`button button-primary button-rounded`)
5. **Front Controller Pattern**: Single entry point for each page type
6. **Unified Data Context**: All template data passed through a consistent context object
7. **Critical CSS Inline**: Essential styles embedded directly in `<head>`

## Directory Structure

```
themes/wp-fasty/
├── functions.php       # Bootstrap file
├── index.php, page.php # Entry points
├── src/                # PSR-4 classes
│   ├── Core/           # Core application
│   ├── Model/          # Data models (framework-agnostic)
│   ├── Adapter/        # Data source adapters
│   └── View/           # Presentation layer
├── templates/          # Special page templates
└── views/              # Latte templates and resources
    ├── layout/         # Base layouts
    │   ├── context/    # Layout context
    │   ├── parts/      # Reusable parts
    │   └── default.latte
    └── page/           # Page templates
        ├── context/    # Context data
        ├── config/     # Metadata & configuration
        ├── cache/      # Compiled templates
        ├── css/        # Styles (src & dist)
        └── page.latte  # Page template
```

## Code Examples

### Front Controller (WordPress)

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
$context = $theme->context();  // Collects context from WordPress
echo $theme->render('page/page', $context);
```

### Latte Template

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
    
    {* Framework-agnostic hook *}
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
</body>
</html>
```

### Semantic Component Example

```latte
<button class="button button-primary button-rounded">
  {$label}
</button>

<article class="card card-featured">
  <header class="card-header">
    <h3 class="card-title">{$title}</h3>
  </header>
  <div class="card-content">
    {$content|noescape}
  </div>
</article>
```

## Template Data Context

```php
/**
 * Context structure should remain consistent
 * across different frameworks
 */
$context = [
    'site' => [
        'title' => 'Site Title',
        'url' => 'https://example.com',
        'lang' => 'en'
    ],
    'page' => [
        'title' => 'Page Title',
        'content' => 'Page content...',
        'slug' => 'page-slug'
    ],
    'menu' => [
        'primary' => [...],
        'footer' => [...]
    ]
];
```

## Coding Rules

1. **Comments**: Always in English
2. **Variable/function names**: `camelCase` for methods, `snake_case` for template variables
3. **HTML5 semantics**: Always prefer semantic tags over generic containers
4. **CSS classes**: Use semantic prefixes with modifiers
5. **Templating**: Avoid framework-specific constructs in templates
6. **Hook naming**: Use descriptive, consistent hook names
7. **Context filtering**: Only pass required data to templates

## Performance Guidelines

1. Compile templates ahead of time
2. Inline critical CSS in `<head>`
3. Asynchronously load non-critical CSS
4. Use CSS variables for theming
5. Keep external dependencies to minimum
6. Pre-filter context data to reduce template processing

## Framework Adapters

When moving between WordPress and Flight PHP, maintain the same template structure by:

1. Creating compatible hook systems
2. Adapting data sources to provide consistent context structure
3. Keeping view layer (templates) unchanged
4. Implementing framework-specific controllers

## Common Pitfalls to Avoid

1. Don't use framework-specific functions directly in templates
2. Don't mix presentation logic with business logic
3. Don't duplicate code across templates
4. Don't use generic div containers when semantic tags are appropriate
5. Don't load unused CSS/JS
6. Don't generate CSS at runtime
7. Don't bypass the context object when passing data to templates 