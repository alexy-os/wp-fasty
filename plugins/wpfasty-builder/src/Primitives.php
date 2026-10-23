<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * UI8Kit 3 primitives of the plugin (`primitives/<engine>/ui8kit`, installed with `npx ui8kit@3 add --all`).
 *
 * The bricks of a block source (`ui/<brick>/*.latte|*.html.twig`) are adapters: they compute the variant classes and
 * the attributes of the brick and call `ui_prim('<primitive>', [...])`, which renders the primitive here, on a
 * trusted engine of the plugin. A sandboxed (remote) source therefore only needs the `ui_prim` function, never the
 * primitive templates or their runtime. The two engines stay isolated: Latte primitives for Latte sources, Twig ones
 * for Twig sources.
 */
final class Primitives
{
    /** primitive id → template (relative to `ui8kit/ui`, without the engine extension) */
    public const MAP = [
        'box' => 'box/Box', 'text' => 'text/Text', 'button' => 'button/Button', 'link' => 'link/Link',
        'input' => 'input/Input', 'checkbox' => 'checkbox/Checkbox', 'radio' => 'radio/Radio', 'switch' => 'switch/Switch',
        'textarea' => 'textarea/Textarea', 'select' => 'select/Select', 'option' => 'select/SelectOption',
        'optgroup' => 'select/SelectOptGroup', 'label' => 'label/Label', 'image' => 'image/Image',
        'picture' => 'image/Picture', 'source' => 'image/Source', 'form' => 'form/Form', 'separator' => 'separator/Separator',
        'disclosure' => 'disclosure/Disclosure', 'summary' => 'disclosure/Summary', 'dialog' => 'dialog/Dialog',
        'progress' => 'progress/Progress', 'meter' => 'meter/Meter',
    ];

    private static ?\Latte\Engine $latte = null;
    private static ?\Twig\Environment $twig = null;

    public static function dir(string $engine): string
    {
        return dirname(__DIR__) . '/primitives/' . $engine . '/ui8kit';
    }

    /** loads the UI8Kit 3 runtime of an engine (skips classes another copy, e.g. the theme's, already declared) */
    public static function loadRuntime(string $engine): bool
    {
        $ns = $engine === 'twig' ? 'UI8Kit\\Twig\\' : 'UI8Kit\\Latte\\';
        $runtime = self::dir($engine) . '/runtime';
        foreach (['Tags', 'CnMerge', 'Rt', 'Classes', $engine === 'twig' ? 'TwigExtension' : null] as $f) {
            if ($f === null || class_exists($ns . $f)) {
                continue;
            }
            if (!is_file("$runtime/$f.php")) {
                return false;
            }
            require_once "$runtime/$f.php";
        }
        return true;
    }

    /**
     * Renders a primitive. `$args`: the primitive's parameters (`class`, `tag`, `href`, `value`, …), `attrs` (attribute
     * map, null/false dropped) and `extra` (the brick's free `Attrs`, printed after the brick's own attributes).
     *
     * @param array<string, mixed> $args
     */
    public static function latte(string $name, array $args): string
    {
        if (!self::loadRuntime('latte')) {
            return '';
        }
        $name = self::pick($name, $args, \UI8Kit\Latte\Tags::GROUPS);
        if (!isset(self::MAP[$name])) {
            return '';
        }
        if (self::$latte === null) {
            self::$latte = new \Latte\Engine();
            self::$latte->setLoader(new \Latte\Loaders\FileLoader(self::dir('latte') . '/ui'));
            self::$latte->setStrictTypes(true);
        }
        return self::$latte->renderToString(self::MAP[$name] . '.latte', self::params($args));
    }

    /** @param array<string, mixed> $args */
    public static function twig(string $name, array $args): string
    {
        if (!self::loadRuntime('twig')) {
            return '';
        }
        $name = self::pick($name, $args, \UI8Kit\Twig\Tags::GROUPS);
        if (!isset(self::MAP[$name])) {
            return '';
        }
        if (self::$twig === null) {
            self::$twig = new \Twig\Environment(new \Twig\Loader\FilesystemLoader(self::dir('twig') . '/ui'), ['autoescape' => 'html']);
            self::$twig->addExtension(new \UI8Kit\Twig\TwigExtension());
        }
        return self::$twig->render(self::MAP[$name] . '.html.twig', self::params($args));
    }

    /**
     * `auto` (a brick whose tag is a prop): Box for structural tags, Text for text tags.
     *
     * @param array<string, mixed> $args
     * @param array<string, list<string>> $groups
     */
    private static function pick(string $name, array $args, array $groups): string
    {
        if ($name !== 'auto') {
            return $name;
        }
        $tag = strtolower(trim((string) ($args['tag'] ?? '')));
        return in_array($tag, $groups['Text'] ?? [], true) ? 'text' : 'box';
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    private static function params(array $args): array
    {
        $attrs = [];
        foreach ([(array) ($args['attrs'] ?? []), is_array($args['extra'] ?? null) ? $args['extra'] : []] as $map) {
            foreach ($map as $k => $v) {
                if ($v === null || $v === false || array_key_exists((string) $k, $attrs)) {
                    continue;
                }
                $attrs[(string) $k] = $v === true ? true : (is_scalar($v) || $v instanceof \Stringable ? (string) $v : '');
            }
        }
        unset($args['attrs'], $args['extra']);
        $out = ['attrs' => $attrs];
        foreach ($args as $k => $v) {
            $out[(string) $k] = is_bool($v) ? $v : (is_scalar($v) || $v instanceof \Stringable || $v === null ? (string) $v : $v);
        }
        return $out;
    }
}
