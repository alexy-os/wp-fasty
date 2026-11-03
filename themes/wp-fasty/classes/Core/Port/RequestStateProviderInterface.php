<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Model\RequestState;

interface RequestStateProviderInterface
{
    public function current(): RequestState;
}
