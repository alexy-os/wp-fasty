<?php

declare(strict_types=1);

use WPFasty\Data\ContextFactory;
use WPFasty\Core\Application;

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

get_header();

// Output the context as a pretty array
echo '<pre class="text-sm p-8">';
$contextData = $context->toArray();
echo esc_html(prettyPrintValue($contextData)); 
echo '</pre>';

get_footer();

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