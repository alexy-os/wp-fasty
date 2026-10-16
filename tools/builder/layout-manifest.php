<?php
// Prints the @ui8kit/contract v0.4 manifest of the builder layout kind: php tools/builder/layout-manifest.php
require dirname(__DIR__, 2) . '/plugins/wpfasty-builder/src/Catalog.php';
require dirname(__DIR__, 2) . '/plugins/wpfasty-builder/src/Layout.php';
echo json_encode(WPFastyBuilder\Layout::manifest(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), "\n";
