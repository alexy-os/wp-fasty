<?php

declare(strict_types=1);

// PHP-CS-Fixer with the @Symfony ruleset. Report-only for now: `composer cs` runs --dry-run --diff;
// `composer cs-fix` applies fixes (do not mass-reformat, fix per touched file).
$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__.'/themes/latte/classes',
        __DIR__.'/plugins/wpfasty-builder/src',
        __DIR__.'/plugins/wpfasty-types/src',
        __DIR__.'/tests',
    ])
    ->exclude(['fixtures']);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@Symfony' => true,
    ])
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache')
    ->setFinder($finder);
