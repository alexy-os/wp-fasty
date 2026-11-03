<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Model\ArchiveState;

interface ArchiveStateProviderInterface
{
    public function current(): ArchiveState;
}
