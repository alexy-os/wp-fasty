<?php

/**
 * Validates template contexts against schema/theme-context.schema.json (view profile view/v1, a copy of.
 *
 * @ui8kit/contract schemas/view.schema.json pinned in schema/VIEW_SCHEMA_SOURCE; registered under its own $id).
 *   php tools/qa/context-schema.php <dir-or-file.json>...   (files: {"kind": "page|archive|error", "context": {...}})
 * Exit 1 on the first invalid context. Library use: wpfasty_context_schema_errors($context, $kind).
 */

declare(strict_types=1);

use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Validator;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

if (!function_exists('wpfasty_context_schema_errors')) {
    /**
     * @param mixed  $data a context (or a part of it) as decoded JSON / PHP arrays
     * @param string $def  `#/$defs/<def>` of the schema: pageContext, archiveContext, errorContext, post, ...
     *
     * @return list<string> errors ('' path = root)
     */
    function wpfasty_context_schema_errors(mixed $data, string $def): array
    {
        static $validator = null;
        static $schemaId = '';
        $schemaFile = dirname(__DIR__, 2).'/schema/theme-context.schema.json';
        if (null === $validator) {
            $validator = new Validator();
            $schemaId = (string) (json_decode((string) file_get_contents($schemaFile), true)['$id'] ?? '');
            $validator->resolver()?->registerFile($schemaId, $schemaFile);
            // Codex v0.4 record schemas the view profile references (https://schema.buildy.tw/*), from @ui8kit/contract
            foreach (glob(dirname(__DIR__, 2).'/node_modules/@ui8kit/contract/schemas/*.schema.json') ?: [] as $codex) {
                if ('https://schema.buildy.tw/'.basename($codex) !== $schemaId) {
                    $validator->resolver()?->registerFile('https://schema.buildy.tw/'.basename($codex), $codex);
                }
            }
        }
        // arrays -> objects the way json_decode() gives them (lists stay arrays, maps become objects)
        $json = json_decode((string) json_encode($data, JSON_PRESERVE_ZERO_FRACTION));
        $result = $validator->validate($json, $schemaId.'#/$defs/'.$def);
        if ($result->isValid()) {
            return [];
        }
        $out = [];
        foreach ((new ErrorFormatter())->format($result->error(), true) as $path => $messages) {
            foreach ((array) $messages as $m) {
                $out[] = ('' === $path ? '/' : $path).': '.$m;
            }
        }

        return $out;
    }
}

if (PHP_SAPI === 'cli' && isset($argv) && __FILE__ === realpath($argv[0])) {
    $files = [];
    foreach (array_slice($argv, 1) as $arg) {
        $files = [...$files, ...(is_dir($arg) ? glob(rtrim($arg, '/').'/*.json') ?: [] : [$arg])];
    }
    if ([] === $files) {
        fwrite(STDERR, "context-schema: no context files\n");
        exit(1);
    }
    $bad = 0;
    foreach ($files as $file) {
        $doc = json_decode((string) file_get_contents($file), true);
        $kind = is_array($doc) ? (string) ($doc['kind'] ?? '') : '';
        $errors = in_array($kind, ['page', 'archive', 'error'], true)
            ? wpfasty_context_schema_errors($doc['context'] ?? null, $kind.'Context')
            : ['not a {"kind", "context"} file'];
        if ([] !== $errors) {
            ++$bad;
            echo basename($file).': '.implode('; ', array_slice($errors, 0, 5))."\n";
        }
    }
    echo sprintf("context-schema: %d contexts, %d invalid\n", count($files), $bad);
    exit($bad > 0 ? 1 : 0);
}
