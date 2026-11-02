<?php

/**
 * Rector — PHP 8.2 language level + the dead-code, code-quality and early-return sets over the theme and plugin
 * classes. CI runs `composer rector` (dry run) and fails on a pending change; `composer rector:fix` applies them.
 */

declare(strict_types=1);

use Rector\CodeQuality\Rector\BooleanOr\RepeatedOrEqualToInArrayRector;
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\Concat\DirnameDirConcatStringToDirectStringPathRector;
use Rector\CodeQuality\Rector\If_\ExplicitNullableObjectInstanceRector;
use Rector\CodingStyle\Rector\FuncCall\FunctionFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector;
use Rector\Instanceof_\Rector\Ternary\FlipNegatedTernaryInstanceofRector;
use Rector\Php55\Rector\String_\StringClassNameToClassConstantRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/themes/latte/classes',
        __DIR__.'/plugins/wpfasty-builder/src',
        __DIR__.'/plugins/wpfasty-types/src',
    ])
    ->withBootstrapFiles([__DIR__.'/themes/latte/vendor/autoload.php'])
    ->withCache(__DIR__.'/var/cache/rector')
    ->withPhpSets(php82: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, earlyReturn: true)
    // Skipped on purpose. Public API of the parent theme and plugins, extended or hooked from child themes:
    // - readonly classes: a child class must be readonly too (BC break for child themes);
    // - first-class callables in hooks: remove_action()/remove_filter() with [$obj, 'method'] would no longer match;
    // - unused public parameters: they are hook / interface signatures;
    // - static -> instance and 'Class' -> Class::class: callers in child themes, class_exists() guards on optional
    //   classes (WooCommerce, WP_CLI) must not autoload.
    // Readability (house style): `null === $x` over `!$x instanceof \Long\Fqcn`, dirname(__DIR__) over '/../' paths,
    // explicit `||` chains over in_array(null, [...]).
    ->withSkip([
        ReadOnlyClassRector::class,
        ArrayToFirstClassCallableRector::class,
        FunctionFirstClassCallableRector::class,
        RemoveUnusedPublicMethodParameterRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        StringClassNameToClassConstantRector::class,
        ExplicitNullableObjectInstanceRector::class,
        FlipNegatedTernaryInstanceofRector::class,
        DirnameDirConcatStringToDirectStringPathRector::class,
        RepeatedOrEqualToInArrayRector::class,
    ]);
