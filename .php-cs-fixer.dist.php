<?php

declare(strict_types=1);

// PHP-CS-Fixer, @Symfony ruleset. `composer cs` checks (blocking), `composer cs-fix` applies.
// Same scope as phpcs.xml.dist: generated / third-party code (vendor, UI8Kit 3 primitives, builder sources and
// editor bundles, test fixtures) is never touched.
$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/themes',
        __DIR__.'/plugins',
        __DIR__.'/presets',
        __DIR__.'/tools',
        __DIR__.'/tests',
    ])
    ->append([__DIR__.'/index.php', __FILE__])
    ->exclude([
        'vendor',
        'node_modules',
        '~cache',
        'fixtures',
        'latte/ui8kit',
        'latte/twig/ui8kit',
        'wpfasty-builder/primitives',
        'wpfasty-builder/sources',
        'wpfasty-builder/editor',
    ])
    ->notName(['*.latte', '*.twig']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@Symfony' => true,
        // public → protected → private (Symfony.Functions.ScopeOrder in phpcs.xml.dist), declaration order kept
        'ordered_class_elements' => [
            'order' => ['use_trait', 'case', 'constant', 'property', 'construct', 'destruct', 'magic', 'method_public', 'method_protected', 'method_private'],
            'sort_algorithm' => 'none',
        ],
    ])
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache')
    ->setFinder($finder);
