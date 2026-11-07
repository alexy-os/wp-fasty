<?php

declare(strict_types=1);

namespace PhpFasty\Moduly\Contract;

/**
 * What a CMS adapter offers modules generically. Adapters (e.g. phpfasty/wpmodules for WordPress) implement it and
 * may expose richer, host-specific APIs through services in the container.
 */
interface HostInterface
{
    /** Host name used in module.json "requires" (e.g. "wp"). */
    public function name(): string;

    public function version(): string;

    /** @param callable $callback */
    public function on(string $type, string $hook, callable $callback, int $priority = 10, int $args = 1): void;

    /**
     * Module settings validated against its settings schema; defaults applied.
     *
     * @return array<string, mixed>
     */
    public function settings(string $moduleId): array;

    /** @param array<string, mixed> $context */
    public function log(string $level, string $message, array $context = []): void;
}
