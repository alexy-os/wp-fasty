<?php

declare(strict_types=1);

namespace PhpFasty\Moduly\Contract;

use PhpFasty\Moduly\Kernel\ModuleContext;

/**
 * Entry class of a module (module.json "entry"). Host-specific work (hooks, routes) goes through
 * the host adapter object in the context; the core itself never calls a CMS.
 */
interface ModuleInterface
{
    /** Register services and hooks. No I/O, no output. Called on every request while enabled. */
    public function boot(ModuleContext $context): void;

    /** Enabled by an administrator: create pages, capabilities, run forward-only migrations. Idempotent. */
    public function activate(ModuleContext $context): void;

    /** Disabled: stop scheduled work. Must never delete user data. */
    public function deactivate(ModuleContext $context): void;
}
