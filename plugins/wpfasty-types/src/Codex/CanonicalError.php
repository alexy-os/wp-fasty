<?php

declare(strict_types=1);

namespace WPFastyTypes\Codex;

/** codex-entry/v1 error: `code` from docs/canonical.md, `path` = `$.field` or `@offset` for parse errors. */
final class CanonicalError extends \RuntimeException
{
    public function __construct(public readonly string $codexCode, public readonly string $path, string $message)
    {
        parent::__construct("{$codexCode}: {$message} (at {$path})");
    }
}
