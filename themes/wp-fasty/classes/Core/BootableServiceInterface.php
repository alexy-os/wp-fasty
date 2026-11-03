<?php

declare(strict_types=1);

namespace WPFasty\Core;

interface BootableServiceInterface
{
    public function boot(): void;
}
