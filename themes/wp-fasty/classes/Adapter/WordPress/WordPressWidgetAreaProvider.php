<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Html\TrustedHtml;
use WPFasty\Core\Port\WidgetAreaProviderInterface;

/** dynamic_sidebar() output (core widgets escape their own output). */
final class WordPressWidgetAreaProvider implements WidgetAreaProviderInterface
{
    public function render(string $id): TrustedHtml
    {
        if (!function_exists('dynamic_sidebar') || !function_exists('is_active_sidebar') || !is_active_sidebar($id)) {
            return TrustedHtml::empty();
        }
        ob_start();
        dynamic_sidebar($id);

        return new TrustedHtml((string) ob_get_clean());
    }
}
