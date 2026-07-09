<?php
/**
 * wp-fasty monorepo root — not a WordPress theme.
 * Active themes live under themes/<slug>/.
 */
http_response_code(404);
header('Content-Type: text/plain; charset=utf-8');
echo "wp-fasty monorepo. Use themes/wp-fasty-ym as the WordPress theme.\n";
