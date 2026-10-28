<?php

declare(strict_types=1);

// esc_url for tests/PagerTest.php (Pager::links); the other WP functions it needs are in bootstrap.php
if (!function_exists('esc_url')) {
    function esc_url(string $url): string
    {
        return str_replace('&', '&#038;', $url);
    }
}
