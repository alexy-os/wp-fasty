<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Core\BootableServiceInterface;
use WPFasty\Core\ContainerInterface;

/**
 * Context Schema Generator
 * 
 * Generates a context.schema.json file containing the structure of data returned by context factories
 * and a context.types.php file with PHP type definitions
 */
class ContextSchemaGenerator implements BootableServiceInterface
{
    /**
     * Service container
     *
     * @var ContainerInterface
     */
    protected $container;
    
    /**
     * Path to the context.schema.json file
     *
     * @var string
     */
    protected $schemaPath;
    
    /**
     * Path to the context.types.php file
     *
     * @var string
     */
    protected $typesPath;
    
    /**
     * Constructor
     *
     * @param ContainerInterface $container Service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->schemaPath = get_template_directory() . '/context.schema.json';
        $this->typesPath = get_template_directory() . '/context.types.php';
    }
    
    /**
     * Boot the service
     */
    public function boot(): void
    {
        // Register our hook for theme activation
        add_action('after_switch_theme', [$this, 'generateSchema']);
        
        // Add a hook that can be triggered manually for updates
        add_action('wpfasty_update_context_schema', [$this, 'generateSchema']);
    }
    
    /**
     * Generate the context schema from the factory methods
     */
    public function generateSchema(): void
    {
        $schema = $this->buildSchemaFromFactories();
        
        // Write the schema to a JSON file
        file_put_contents($this->schemaPath, json_encode($schema, JSON_PRETTY_PRINT));
        
        // Generate and write PHP type definitions
        $this->generateTypeDefinitions($schema);
    }
    
    /**
     * Generate PHP type definitions from schema
     *
     * @param array<string, mixed> $schema The schema structure
     */
    private function generateTypeDefinitions(array $schema): void
    {
        $typeContent = $this->buildTypeDefinitions($schema);
        file_put_contents($this->typesPath, $typeContent);
    }
    
    /**
     * Build PHP type definitions from schema
     *
     * @param array<string, mixed> $schema The schema structure
     * @return string PHP type definitions
     */
    private function buildTypeDefinitions(array $schema): string
    {
        $timestamp = date('Y-m-d H:i:s');
        
        $content = <<<PHP
<?php
/**
 * Context Types
 *
 * DO NOT MODIFY THIS FILE DIRECTLY. This file is auto-generated.
 * Generated on: {$timestamp}
 */

namespace WPFasty\Types;

/**
 * This file contains type definitions for template contexts
 * Import in Latte templates with: @import \WPFasty\Types\Context
 */

PHP;

        // Define the main Context interface
        $content .= <<<PHP

/**
 * Main context interface that combines all context types
 */
interface Context
{
    // This is a marker interface only
}

PHP;

        // Process each context type
        foreach ($schema as $contextName => $contextData) {
            $contextClassName = ucfirst($contextName) . 'Context';
            
            $content .= $this->generateInterfaceForContext($contextName, $contextClassName, $contextData);
        }
        
        return $content;
    }
    
    /**
     * Generate interface definition for a context
     *
     * @param string $contextName The context name (e.g., 'page', 'archive')
     * @param string $className The interface class name
     * @param array<string, mixed> $contextData The context data structure
     * @return string PHP interface definition
     */
    private function generateInterfaceForContext(string $contextName, string $className, array $contextData): string
    {
        $content = <<<PHP

/**
 * {$className} interface
 * 
 * Contains type definitions for the {$contextName} context
 */
interface {$className} extends Context
{
    /**
     * Get complete {$contextName} context array
     * 
     * @return array{

PHP;

        // Add the array type definition
        $typeDef = $this->buildArrayTypeDefinition($contextData, 2);
        $content .= $typeDef;
        
        $content .= <<<PHP
     * }
     */
    public function getContext(): array;
}

PHP;

        // For contexts with nested types, generate additional interfaces
        foreach ($contextData as $key => $value) {
            if (is_array($value) && !$this->isIndexedArray($value)) {
                $nestedClassName = ucfirst($key) . 'Context';
                $content .= $this->generateInterfaceForContext($key, $nestedClassName, $value);
            } elseif (is_array($value) && isset($value[0]) && is_array($value[0])) {
                // For arrays of objects, like posts
                $itemClassName = ucfirst(rtrim($key, 's')) . 'ItemContext';
                $content .= $this->generateInterfaceForContext(rtrim($key, 's') . '_item', $itemClassName, $value[0]);
            }
        }
        
        return $content;
    }
    
    /**
     * Build array type definition string for PHPDoc
     * 
     * @param array<string, mixed> $data The data structure
     * @param int $indentLevel The indentation level
     * @return string PHPDoc array type definition
     */
    private function buildArrayTypeDefinition(array $data, int $indentLevel = 0): string
    {
        $indent = str_repeat('     ', $indentLevel);
        $typeDef = '';
        
        foreach ($data as $key => $value) {
            $typeDef .= "{$indent}{$key}: ";
            
            if (is_array($value)) {
                if ($this->isIndexedArray($value)) {
                    // This is a list/indexed array
                    if (!empty($value) && isset($value[0]) && is_array($value[0])) {
                        $typeDef .= "array<int, array{\n";
                        $typeDef .= $this->buildArrayTypeDefinition($value[0], $indentLevel + 1);
                        $typeDef .= "{$indent}}>";
                    } else {
                        // Empty array or array of scalars
                        $typeDef .= "array<int, mixed>";
                    }
                } else {
                    // This is an associative array
                    $typeDef .= "array{\n";
                    $typeDef .= $this->buildArrayTypeDefinition($value, $indentLevel + 1);
                    $typeDef .= "{$indent}}";
                }
            } else {
                // Scalars
                $typeDef .= $this->phpTypeFromValue($value);
            }
            
            $typeDef .= ",\n";
        }
        
        return $typeDef;
    }
    
    /**
     * Get PHP type from a value
     * 
     * @param mixed $value The value to get type from
     * @return string PHP type as string
     */
    private function phpTypeFromValue($value): string
    {
        if ($value === null) {
            return "?mixed";
        } elseif (is_string($value)) {
            return "string";
        } elseif (is_int($value)) {
            return "int";
        } elseif (is_float($value)) {
            return "float";
        } elseif (is_bool($value)) {
            return "bool";
        } else {
            return "mixed";
        }
    }
    
    /**
     * Check if array is indexed (list) rather than associative
     * 
     * @param array<mixed> $array The array to check
     * @return bool True if indexed, false if associative
     */
    private function isIndexedArray(array $array): bool
    {
        if (empty($array)) {
            return true;
        }
        
        return array_keys($array) === range(0, count($array) - 1);
    }
    
    /**
     * Build schema from factory methods
     *
     * @return array<string, mixed> The schema structure
     */
    private function buildSchemaFromFactories(): array
    {
        $schema = [];
        
        // Get context factory
        $contextFactory = $this->container->get('data.context_factory');
        
        // Extract schema from page context
        $pageContext = $this->extractContextKeys($contextFactory, 'createPageContext');
        if (!empty($pageContext)) {
            $schema['page'] = $pageContext;
        }
        
        // Extract schema from archive context
        $archiveContext = $this->extractContextKeys($contextFactory, 'createArchiveContext');
        if (!empty($archiveContext)) {
            $schema['archive'] = $archiveContext;
        }
        
        // Allow plugins/themes to add more contexts
        $schema = apply_filters('wpfasty_context_schema', $schema);
        
        return $schema;
    }
    
    /**
     * Extract context keys from factory method
     *
     * @param object $factory The factory object
     * @param string $method The method name
     * @return array<string, mixed> The extracted schema
     */
    private function extractContextKeys($factory, string $method): array
    {
        try {
            // Create a temporary post for context generation if needed
            $dummyPost = null;
            if ($method === 'createPageContext') {
                // Get the most recent published post as a dummy
                $dummyPost = get_posts([
                    'numberposts' => 1,
                    'post_status' => 'publish',
                ]);
                
                $dummyPost = !empty($dummyPost) ? $dummyPost[0] : null;
            }
            
            // Call the method to get the context
            $context = method_exists($factory, $method) 
                ? ($method === 'createPageContext' && $dummyPost 
                    ? $factory->$method($dummyPost) 
                    : $factory->$method())
                : [];
            
            // Extract just the structure without the values
            return $this->extractStructure($context);
        } catch (\Exception $e) {
            // Log error but don't break the site
            error_log('Error generating context schema: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extract structure from data
     *
     * @param array<string, mixed> $data The data to extract structure from
     * @return array<string, mixed> The extracted structure
     */
    private function extractStructure(array $data): array
    {
        $structure = [];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // Для числовых массивов, определяем, является ли это коллекцией
                if (array_key_exists(0, $value)) {
                    // Это, вероятно, коллекция - получаем структуру первого элемента
                    if (!empty($value) && is_array($value[0])) {
                        // Извлекаем структуру первого элемента
                        $itemStructure = $this->extractStructure($value[0]);
                        $structure[$key] = [$itemStructure];
                    } else {
                        // Пустой массив или массив скалярных значений
                        $structure[$key] = [];
                    }
                } else {
                    // Обычный ассоциативный массив
                    $structure[$key] = $this->extractStructure($value);
                }
            } else {
                // Для скалярных значений сохраняем тип
                if ($value === null) {
                    $structure[$key] = null;
                } elseif (is_string($value)) {
                    $structure[$key] = '';
                } elseif (is_int($value)) {
                    $structure[$key] = 0;
                } elseif (is_float($value)) {
                    $structure[$key] = 0.0;
                } elseif (is_bool($value)) {
                    $structure[$key] = false;
                } else {
                    // Для других типов просто null
                    $structure[$key] = null;
                }
            }
        }
        
        return $structure;
    }
} 