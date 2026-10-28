<?php

/**
 * Raw-output gate (CI): every place a template prints without escaping — Latte `|noescape`, Twig `|raw`, Twig
 * `{% autoescape false %}`, a Latte `{contentType}` / `{syntax off}` switch — in every tracked .latte / .html.twig file
 * (parent and child themes, presets, archetypes, builder sources and primitives), and every PHP Twig function or filter
 * registered `is_safe` html, must be listed in tools/security/raw-sinks.json with where the value comes from and why
 * it is safe:
 *   generated  files marked "Code generated … DO NOT EDIT" (codegen-x / UI8Kit output, never hand-edited): the
 *              expression must match one of the reviewed patterns (e.g. og_include(…) — a rendered template; og_html(…) —
 *              TrustedHtml, sanitized in the adapter; og_slot(…) — a slot prop, raw only as captured markup, a plain
 *              string escaped by codegen-x itself since 41706af7, so slot props are no longer limited to templates
 *              outside the catalog)
 *   views      hand-written views: exact (file, expression) entries with their count
 *   safe       PHP `is_safe` registrations: exact (file, function name) entries
 * A new place, or one more of a listed expression in a file, fails the build until it is reviewed and added. Entries
 * that no longer exist fail too (the list stays exact).
 *
 *   php tools/security/raw-sinks.php            check
 *   php tools/security/raw-sinks.php --list     print every place found (file:line expression)
 *
 * @package WPFastyBuilder
 */

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$list = in_array('--list', $argv, true);
$allow = json_decode((string) file_get_contents(__DIR__ . '/raw-sinks.json'), true, 512, JSON_THROW_ON_ERROR);

/** @return list<string> */
$git = static function (string $args) use ($root): array {
    $out = (string) shell_exec('git -C ' . escapeshellarg($root) . ' ls-files ' . $args);
    return array_values(array_filter(explode("\n", $out), static fn (string $f): bool => $f !== ''));
};
$norm = static fn (string $e): string => trim((string) preg_replace('/\s+/', ' ', $e));

/**
 * Raw prints of one template: [line, expression].
 *
 * @return list<array{0: int, 1: string}>
 */
$sinks = static function (string $src, bool $twig) use ($norm): array {
    $out = [];
    $line = static fn (int $offset): int => substr_count($src, "\n", 0, $offset) + 1;
    if ($twig) {
        // {{ … }} print tags (strings may contain }}: skip quoted parts)
        preg_match_all('/\{\{-?(?:[^}\'"]|\}(?!\})|\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")*-?\}\}/s', $src, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[0] as [$tag, $at]) {
            $body = trim(substr($tag, 2, -2), "- \t\r\n");
            if (preg_match('/^(.*)\|\s*raw\s*$/s', $body, $mm) === 1) {
                $out[] = [$line($at), $norm($mm[1])];
            } elseif (preg_match('/\|\s*raw\b/', $body) === 1) {
                $out[] = [$line($at), $norm($body)];
            }
        }
        preg_match_all('/\{%-?\s*autoescape\s+(false|[\'"]?(?!html)[a-z]*[\'"]?)\s*-?%\}/', $src, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[0] as [$tag, $at]) {
            $out[] = [$line($at), $norm('autoescape ' . trim(substr($tag, 2, -2), '-% '))];
        }
        return $out;
    }
    // Latte tags: { … } with balanced braces and quoted strings
    $n = strlen($src);
    for ($i = 0; $i < $n; $i++) {
        if ($src[$i] !== '{' || $i + 1 >= $n || ctype_space($src[$i + 1]) || $src[$i + 1] === '*') {
            continue;
        }
        $depth = 0;
        $q = '';
        for ($j = $i; $j < $n; $j++) {
            $c = $src[$j];
            if ($q !== '') {
                if ($c === '\\') {
                    $j++;
                } elseif ($c === $q) {
                    $q = '';
                }
                continue;
            }
            if ($c === "'" || $c === '"') {
                $q = $c;
            } elseif ($c === '{') {
                $depth++;
            } elseif ($c === '}' && --$depth === 0) {
                break;
            }
        }
        $tag = substr($src, $i + 1, $j - $i - 1);
        if (preg_match('/^=?\s*(.*)\|noescape\s*$/s', $tag, $mm) === 1) {
            $out[] = [$line($i), $norm($mm[1])];
        } elseif (preg_match('/^(contentType|syntax\s+off)\b/', $tag) === 1) {
            $out[] = [$line($i), $norm($tag)];
        } elseif (str_contains($tag, '|noescape')) {
            $out[] = [$line($i), $norm($tag)];
        }
    }
    return $out;
};

$found = ['generated' => [], 'views' => [], 'safe' => []];
$fail = [];
foreach ($git("'*.latte' '*.twig'") as $file) {
    $src = (string) file_get_contents("$root/$file");
    $generated = preg_match('/Code generated|DO NOT EDIT/', substr($src, 0, 400)) === 1;
    foreach ($sinks($src, str_ends_with($file, '.twig')) as [$line, $expr]) {
        if ($list) {
            fwrite(STDOUT, ($generated ? 'G ' : 'V ') . "$file:$line  $expr\n");
        }
        if ($generated) {
            $ok = false;
            foreach ($allow['generated'] as $rule) {
                if (preg_match('#' . $rule['pattern'] . '#s', $expr) === 1) {
                    $ok = true;
                    $found['generated'][$rule['pattern']] = ($found['generated'][$rule['pattern']] ?? 0) + 1;
                    break;
                }
            }
            if (!$ok) {
                $fail[] = "$file:$line generated raw print not covered by a reviewed pattern: $expr";
            }
        } else {
            $found['views']["$file\0$expr"] = ($found['views']["$file\0$expr"] ?? 0) + 1;
            $found['views']["$file\0$expr\0lines"][] = $line;
        }
    }
}
foreach ($git("'*.php'") as $file) {
    // dev tools and tests are not shipped (render-block-check / parity register wpfb_block for their own pages)
    if (str_starts_with($file, 'tools/') || str_starts_with($file, 'tests/')) {
        continue;
    }
    $src = (string) file_get_contents("$root/$file");
    if (!str_contains($src, 'is_safe')) {
        continue;
    }
    // each is_safe belongs to the nearest `new TwigFunction(` / `new TwigFilter(` before it; its name is a string
    // literal or a variable ($name of a loop over a reviewed map); anything else is listed as "*"
    preg_match_all('/is_safe/', $src, $hits, PREG_OFFSET_CAPTURE);
    foreach ($hits[0] as [, $at]) {
        $name = '*';
        $head = substr($src, 0, $at);
        if (preg_match_all('/new\s+\\\\?(?:Twig\\\\)?Twig(?:Function|Filter)\(\s*(?:([\'"])([^\'"]+)\1|(\$\w+))/', $head, $m, PREG_SET_ORDER) > 0) {
            $last = end($m);
            $name = ($last[3] ?? '') !== '' ? $last[3] : $last[2];
        }
        $found['safe']["$file\0$name"] = true;
        if ($list) {
            fwrite(STDOUT, "S $file  $name\n");
        }
    }
}

$views = [];
foreach ($allow['views'] as $v) {
    $views[$v['file'] . "\0" . $norm($v['expr'])] = $v;
}
foreach ($found['views'] as $key => $count) {
    if (str_ends_with($key, "\0lines")) {
        continue;
    }
    [$file, $expr] = explode("\0", $key);
    $lines = implode(',', $found['views']["$key\0lines"]);
    if (!isset($views[$key])) {
        $fail[] = "$file:$lines raw print of a view not reviewed: $expr";
    } elseif ((int) ($views[$key]['count'] ?? 1) !== $count) {
        $fail[] = "$file:$lines $expr printed raw $count× (reviewed: " . (int) ($views[$key]['count'] ?? 1) . '×)';
    }
}
foreach ($views as $key => $v) {
    if (!isset($found['views'][$key])) {
        $fail[] = "{$v['file']}: listed raw print no longer exists, remove it from raw-sinks.json: {$v['expr']}";
    }
}
$safe = [];
foreach ($allow['safe'] as $s) {
    $safe[$s['file'] . "\0" . $s['name']] = true;
}
foreach (array_keys($found['safe']) as $key) {
    if (!isset($safe[$key])) {
        [$file, $name] = explode("\0", $key);
        $fail[] = "$file: Twig $name registered is_safe html, not reviewed";
    }
}
foreach (array_keys($safe) as $key) {
    if (!isset($found['safe'][$key])) {
        [$file, $name] = explode("\0", $key);
        $fail[] = "$file: listed is_safe $name no longer exists, remove it from raw-sinks.json";
    }
}
foreach ($allow['generated'] as $rule) {
    if (!isset($found['generated'][$rule['pattern']])) {
        $fail[] = "generated pattern matches nothing any more, remove it: {$rule['pattern']}";
    }
}
foreach ($fail as $f) {
    fwrite(STDERR, "✗ $f\n");
}
fwrite(STDOUT, sprintf(
    "raw-sinks: %d generated raw prints (%d reviewed patterns), %d in views (%d reviewed), %d is_safe functions — %s\n",
    array_sum($found['generated']),
    count($allow['generated']),
    array_sum(array_filter($found['views'], 'is_int')),
    count($allow['views']),
    count($found['safe']),
    $fail === [] ? 'all reviewed' : count($fail) . ' not reviewed',
));
exit($fail === [] ? 0 : 1);
