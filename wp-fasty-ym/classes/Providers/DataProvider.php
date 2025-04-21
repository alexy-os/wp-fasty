<?php

declare(strict_types=1);

namespace WPFasty\Providers;

interface DataProvider
{
    public function getContext(): array;
}