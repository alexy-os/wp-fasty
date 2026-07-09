<?php

declare(strict_types=1);

use WPFasty\Data\ContextFactory;
use WPFasty\Core\Application;

// Define SAVEQUERIES only if not already defined
if (!defined('SAVEQUERIES')) {
    define('SAVEQUERIES', true);
}

// Enable query tracking
global $wpdb;
$wpdb->queries = []; // Reset queries

// Get the application instance
$app = Application::getInstance();

// Get the dependency container
$container = $app->getContainer();

// Get ContextFactory from the container or create it
if ($container->has(ContextFactory::class)) {
    $contextFactory = $container->get(ContextFactory::class);
} else {
    $contextFactory = new ContextFactory($container);
}

// Get the current page context
$context = $contextFactory->createPageContext();

// Start HTML output
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WP FastY Debug - <?php wp_title('|', true, 'right'); ?><?php bloginfo('name'); ?></title>
    <?php wp_head(); ?>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            line-height: 1.5;
            color: #333;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        h1, h2, h3 {
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1rem;
            margin-top: 2rem;
        }
        pre {
            background: #f5f5f5;
            font-size: 13px;
            padding: 1rem;
            border-radius: 4px;
            overflow: auto;
            max-height: 70vh;
            border: 1px solid #ddd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        th, td {
            text-align: left;
            padding: 0.5rem;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .stats-box {
            background: #f0f8ff;
            border: 1px solid #ccc;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }
        .stats-box h3 {
            margin-top: 0;
            border-bottom: none;
        }
        .footer {
            margin-top: 2rem;
            border-top: 1px solid #eee;
            padding-top: 1rem;
            font-size: 0.875rem;
            color: #666;
        }
        .query-wrapper {
            margin: 1rem 0;
        }
        .query-time {
            font-weight: bold;
            color: #d44;
        }
        .query-group {
            margin-bottom: 2rem;
        }
        .stack-trace {
            font-size: 0.85rem;
            background: #f8f8f8;
            border: 1px solid #eee;
            max-height: 300px;
            overflow: auto;
        }
        details {
            margin-top: 0.5rem;
            border: 1px solid #eee;
            padding: 0.5rem;
            border-radius: 4px;
        }
        summary {
            cursor: pointer;
            color: #444;
            font-weight: bold;
        }
        .post-parent-highlight {
            background-color: #fff8dc;
            border-left: 3px solid #ffa500;
            padding-left: 8px;
            margin-bottom: 25px;
        }
    </style>
</head>
<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <h1>WP FastY Theme Debug Console</h1>
    
    <?php
    // Show database query stats box
    global $wpdb;
    $query_count = count($wpdb->queries);
    $query_time = 0;

    foreach ($wpdb->queries as $query) {
        $query_time += $query[1];
    }
    ?>
    <div class="stats-box">
        <h3>Database Performance</h3>
        <p><strong>Total Queries:</strong> <?php echo $query_count; ?></p>
        <p><strong>Total Query Time:</strong> <?php echo round($query_time * 1000, 2); ?> ms</p>
        <p><strong>Average Query Time:</strong> <?php echo $query_count ? round(($query_time / $query_count) * 1000, 2) : 0; ?> ms per query</p>
    </div>

    <div class="context-dump">
        <h2>Page Context</h2>
        <pre><?php 
            $contextData = $context->toArray();
            echo esc_html(prettyPrintValue($contextData)); 
        ?></pre>
    </div>

    <h2>Database Queries</h2>
    
    <?php
    // Group queries by caller
    $query_groups = [];
    foreach ($wpdb->queries as $query) {
        $sql = $query[0];
        $time = $query[1];
        $stack = $query[2];

        // Extract the caller function from the stack
        $caller = "Unknown";
        if (preg_match('/\s+(\w+(?:->)?\w+)\s+/', $stack, $matches)) {
            $caller = $matches[1];
        }

        if (!isset($query_groups[$caller])) {
            $query_groups[$caller] = [
                'count' => 0,
                'time' => 0,
                'queries' => []
            ];
        }

        $query_groups[$caller]['count']++;
        $query_groups[$caller]['time'] += $time;
        $query_groups[$caller]['queries'][] = [
            'sql' => $sql,
            'time' => $time,
            'stack' => $stack
        ];
    }

    // Sort query groups by count (desc)
    uasort($query_groups, function ($a, $b) {
        return $b['count'] - $a['count'];
    });
    ?>
    
    <table>
        <thead>
            <tr>
                <th>Caller Function</th>
                <th>Query Count</th>
                <th>Total Time (ms)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($query_groups as $caller => $group) : ?>
            <tr>
                <td><?php echo esc_html($caller); ?></td>
                <td><?php echo esc_html($group['count']); ?></td>
                <td><?php echo esc_html(round($group['time'] * 1000, 2)); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <?php foreach ($query_groups as $caller => $group) : ?>
    <div class="query-group">
        <h3><?php echo esc_html($caller); ?> (<?php echo esc_html($group['count']); ?> queries)</h3>
        
        <div class="query-wrapper">
            <?php foreach ($group['queries'] as $query) : ?>
            <div class="query post-parent-highlight">
                <p class="query-time"><?php echo esc_html(round($query['time'] * 1000, 2)); ?> ms</p>
                <pre><?php echo esc_html($query['sql']); ?></pre>
                <details>
                    <summary>Stack Trace</summary>
                    <pre class="stack-trace"><?php echo isset($query['stack']) ? esc_html($query['stack']) : 'Stack trace not available'; ?></pre>
                </details>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <h2>Theme Information</h2>
    <div class="stats-box">
        <?php
        $theme = wp_get_theme();
        ?>
        <p><strong>Theme Name:</strong> <?php echo esc_html($theme->get('Name')); ?></p>
        <p><strong>Version:</strong> <?php echo esc_html($theme->get('Version')); ?></p>
        <p><strong>Author:</strong> <?php echo esc_html($theme->get('Author')); ?></p>
        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
    </div>

    <div class="footer">
        <p>WP FastY Theme Debug Console</p>
    </div>

    <?php wp_footer(); ?>
</body>
</html>
<?php

/**
 * Pretty print any value with indentation
 *
 * @param mixed $value The value to print
 * @param integer $level The current indent level
 * @return string The formatted value as string
 */
function prettyPrintValue($value, int $level = 0): string
{
    if (is_array($value)) {
        // Initialize the output string
        $output = "[\n";

        // Calculate indentation
        $indent = str_repeat("    ", $level + 1);

        // Process each element
        foreach ($value as $key => $val) {
            $output .= $indent;

            // Add the key
            if (is_string($key)) {
                $output .= "'" . addslashes($key) . "' => ";
            } else {
                $output .= $key . " => ";
            }

            // Add the value
            $output .= prettyPrintValue($val, $level + 1);
            $output .= ",\n";
        }

        // Close the array and return
        $output .= str_repeat("    ", $level) . "]";
        return $output;
    } elseif (is_bool($value)) {
        return $value ? 'true' : 'false';
    } elseif (is_null($value)) {
        return 'null';
    } elseif (is_string($value)) {
        // Truncate long strings
        if (strlen($value) > 1000) {
            return "'" . htmlspecialchars(substr($value, 0, 1000)) . "...'";
        }
        return "'" . htmlspecialchars($value) . "'";
    } elseif (is_object($value)) {
        // Convert object to array if possible
        if (method_exists($value, 'toArray')) {
            return prettyPrintValue($value->toArray(), $level);
        } else {
            $objVars = get_object_vars($value);
            if (!empty($objVars)) {
                return prettyPrintValue($objVars, $level);
            } else {
                return "Object(" . get_class($value) . ")";
            }
        }
    } else {
        return var_export($value, true);
    }
}