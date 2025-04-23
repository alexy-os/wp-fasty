<?php
// Отладка запросов с post_parent
global $wpdb;
echo "<h2>Debug SQL Queries with post_parent</h2>";
echo "<pre>";
foreach ($wpdb->queries as $query) {
    if (strpos($query[0], 'post_parent') !== false) {
        echo "Query: " . htmlspecialchars($query[0]) . "\n\n";
        echo "Stack trace: " . htmlspecialchars($query[2]) . "\n\n";
        echo "-----------------------------------\n\n";
    }
}
echo "</pre>"; 