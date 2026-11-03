<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Model\SiteInfo;

interface SiteInfoProviderInterface
{
    public function site(): SiteInfo;
}
