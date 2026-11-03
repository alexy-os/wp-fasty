<?php

declare(strict_types=1);

namespace WPFasty\Core\Port;

use WPFasty\Core\Html\TrustedHtml;

interface WidgetAreaProviderInterface
{
    /** Rendered widgets of an active sidebar, else empty. */
    public function render(string $id): TrustedHtml;
}
