<?php

declare(strict_types=1);

namespace WPFasty\Adapter\WordPress;

use WPFasty\Core\Model\RequestState;
use WPFasty\Core\Port\RequestStateProviderInterface;

final class WordPressRequestStateProvider implements RequestStateProviderInterface
{
    public function current(): RequestState
    {
        return new RequestState(
            is_search() ? (string) get_search_query() : '',
            is_front_page(),
            is_singular(),
            apply_filters('wpfasty_shell', 'classic'),
            function_exists('current_theme_supports') && current_theme_supports('wpfasty-full-width'),
            gmdate('Y'),
        );
    }
}
