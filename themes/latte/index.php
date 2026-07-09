<?php

declare(strict_types=1);

if (is_front_page() && !is_home()) {
    $view = 'pages/front-page';
} elseif (is_singular('post')) {
    $view = 'pages/single';
} elseif (is_page()) {
    $view = 'pages/page';
} elseif (is_home() || is_archive() || is_search()) {
    $view = 'pages/archive';
} else {
    $view = 'pages/page';
}

require __DIR__ . '/includes/render.php';
