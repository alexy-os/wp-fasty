<?php

declare(strict_types=1);

namespace PhpFasty\Moduly\Kernel;

use PhpFasty\Moduly\Container\ScopedContainer;
use PhpFasty\Moduly\Contract\HostInterface;
use PhpFasty\Moduly\Manifest\Manifest;

/**
 * Everything a module receives from the core: its manifest, folder, scoped container, host and settings.
 */
final class ModuleContext
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public readonly Manifest $manifest,
        public readonly string $path,
        public readonly ScopedContainer $container,
        public readonly HostInterface $host,
        public readonly array $settings,
    ) {
    }

    public function id(): string
    {
        return $this->manifest->id;
    }
}
