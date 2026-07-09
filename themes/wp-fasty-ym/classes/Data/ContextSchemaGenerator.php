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
     * Known structure templates for common data types
     * 
     * @var array
     */
    private $knownStructures = [
        'posts' => [
            'title' => '',
            'content' => '',
            'slug' => '',
            'url' => '',
            'id' => 0,
            'excerpt' => '',
            'featuredImage' => [
                'url' => '',
                'width' => 0,
                'height' => 0,
                'alt' => ''
            ],
            'thumbnail' => [
                'url' => '',
                'width' => 0,
                'height' => 0,
                'alt' => ''
            ],
            'meta' => [
                '_edit_last' => '',
                '_edit_lock' => ''
            ],
            'categories' => [
                [
                    'name' => '',
                    'url' => '',
                    'id' => 0,
                    'slug' => '',
                    'description' => '',
                    'count' => 0
                ]
            ],
            'date' => [
                'formatted' => '',
                'display' => '',
                'modified' => '',
                'modified_display' => '',
                'timestamp' => 0,
                'year' => '',
                'month' => '',
                'day' => ''
            ]
        ],
        'pagination' => [
            'prev_url' => '',
            'next_url' => '',
            'pages' => [
                [
                    'number' => 0,
                    'url' => '',
                    'is_current' => false
                ]
            ],
            'current' => 0,
            'total' => 0
        ],
        'featuredImage' => [
            'url' => '',
            'width' => 0,
            'height' => 0,
            'alt' => ''
        ],
        'thumbnail' => [
            'url' => '',
            'width' => 0,
            'height' => 0,
            'alt' => ''
        ]
    ];
    
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
        $schemaData = $this->buildSchemaFromFactories();
        
        $schema = [
            '$schema' => 'http://json-schema.org/draft-07/schema#',
            'title' => 'WPFasty Context Schema',
            'description' => 'Schema for WordPress template contexts',
            'type' => 'object',
            'properties' => $schemaData,
            'required' => array_keys($schemaData)
        ];
        
        file_put_contents($this->schemaPath, json_encode($schema, JSON_PRETTY_PRINT));
        
        // Generate and write PHP type definitions
        $this->generateTypeDefinitions($schemaData);
    }
    
    /**
     * Extract structure from data
     *
     * @param array<string, mixed> $data The data to extract structure from
     * @return array<string, mixed> The extracted structure
     */
    private function extractStructure(array $data): array
    {
        $structure = [
            'type' => 'object',
            'properties' => [],
            'required' => []
        ];
        
        foreach ($data as $key => $value) {
            $structure['required'][] = $key;
            
            // Check if we have a known structure for this key
            if (isset($this->knownStructures[$key]) && (is_null($value) || empty($value) || (is_array($value) && empty($value)))) {
                // Use known structure if value is empty/null
                $knownStructure = $this->extractStructure($this->knownStructures[$key]);
                $structure['properties'][$key] = $knownStructure;
                $structure['properties'][$key]['description'] = ucfirst($key);
                continue;
            }
            
            if (is_array($value)) {
                if ($this->isIndexedArray($value)) {
                    // Handle indexed arrays
                    if (!empty($value) && is_array($value[0])) {
                        // Create schema for array items when they are objects
                        $itemSchema = $this->extractStructure($value[0]);
                        $structure['properties'][$key] = [
                            'type' => 'array',
                            'description' => ucfirst($key),
                            'items' => $itemSchema
                        ];
                    } else {
                        // For empty arrays or arrays of primitives
                        // Check for known structures first
                        if (isset($this->knownStructures[$key])) {
                            $itemSchema = $this->extractStructure($this->knownStructures[$key]);
                            $structure['properties'][$key] = [
                                'type' => 'array',
                                'description' => ucfirst($key),
                                'items' => $itemSchema
                            ];
                        } else {
                            // Otherwise infer from available data
                            $itemType = !empty($value) ? $this->getJsonSchemaType($value[0]) : 'string';
                            $structure['properties'][$key] = [
                                'type' => 'array',
                                'description' => ucfirst($key),
                                'items' => [
                                    'type' => $itemType
                                ]
                            ];
                        }
                    }
                } else {
                    // Handle associative arrays (objects)
                    $structure['properties'][$key] = $this->extractStructure($value);
                    $structure['properties'][$key]['description'] = ucfirst($key);
                }
            } else {
                // Handle scalar values
                $type = $this->getJsonSchemaType($value);
                $structure['properties'][$key] = [
                    'type' => $type,
                    'description' => ucfirst($key)
                ];
                
                // For nullable types, provide more specific type info when possible
                if ($type === 'null') {
                    if (isset($this->knownStructures[$key])) {
                        // Use known structure for this field
                        $structure['properties'][$key] = $this->extractStructure($this->knownStructures[$key]);
                        $structure['properties'][$key]['description'] = ucfirst($key);
                    } else {
                        // Default to multiple possible types
                        $structure['properties'][$key] = [
                            'type' => ['null', 'string', 'object'],
                            'description' => ucfirst($key)
                        ];
                    }
                }
            }
        }
        
        // Special handling for specific fields in context
        $this->applySpecialFieldHandling($structure);
        
        return $structure;
    }
    
    /**
     * Apply special handling for specific fields
     *
     * @param array<string, mixed> &$structure The structure to modify
     */
    private function applySpecialFieldHandling(array &$structure): void
    {
        // Check if this is an archive context with pagination
        if (isset($structure['properties']['posts']) && !isset($structure['properties']['pagination'])) {
            // Add pagination structure if posts exist but pagination doesn't
            $structure['properties']['pagination'] = $this->extractStructure($this->knownStructures['pagination']);
            $structure['properties']['pagination']['description'] = 'Pagination';
            $structure['required'][] = 'pagination';
        }
        
        // Ensure featured image and thumbnail have proper structure
        foreach (['featuredImage', 'thumbnail'] as $imgField) {
            if (isset($structure['properties'][$imgField]) && 
                (isset($structure['properties'][$imgField]['type']) && 
                 $structure['properties'][$imgField]['type'] === 'null')) {
                $structure['properties'][$imgField] = $this->extractStructure($this->knownStructures[$imgField]);
                $structure['properties'][$imgField]['description'] = ucfirst($imgField);
            }
        }
    }
    
    /**
     * Extract common context elements by analyzing existing contexts
     *
     * @return array<string, mixed> The common context structure
     */
    private function extractCommonKeys(): array
    {
        try {
            // Get contexts for analysis
            $contextFactory = $this->container->get('data.context_factory');
            $pageContext = $this->extractContextKeys($contextFactory, 'createPageContext');
            $archiveContext = $this->extractContextKeys($contextFactory, 'createArchiveContext');
            
            // Initialize common context structure
            $commonContext = [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ];
            
            // Safety check
            if (empty($pageContext['properties']) || empty($archiveContext['properties'])) {
                return $commonContext;
            }
            
            // Find common keys
            $pageKeys = array_keys($pageContext['properties']);
            $archiveKeys = array_keys($archiveContext['properties']);
            $commonKeys = array_intersect($pageKeys, $archiveKeys);
            
            // Add common keys to commonContext
            foreach ($commonKeys as $key) {
                $commonContext['properties'][$key] = $pageContext['properties'][$key];
                $commonContext['required'][] = $key;
            }
            
            return $commonContext;
        } catch (\Exception $e) {
            error_log('Error generating common context schema: ' . $e->getMessage());
            return [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ];
        }
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
        
        // Extract common keys first
        $commonContext = $this->extractCommonKeys();
        
        // Extract specific context types
        $pageContext = $this->extractContextKeys($contextFactory, 'createPageContext');
        $archiveContext = $this->extractContextKeys($contextFactory, 'createArchiveContext');
        
        // Add common keys at top level
        if (isset($commonContext['properties'])) {
            foreach ($commonContext['properties'] as $key => $value) {
                $schema[$key] = $value;
            }
        }
        
        // Get common key names for removal from specific contexts
        $commonKeys = isset($commonContext['properties']) ? array_keys($commonContext['properties']) : [];
        
        // Remove common keys from specific contexts
        $removeCommonKeys = function(array $context) use ($commonKeys) {
            if (isset($context['properties'])) {
                foreach ($commonKeys as $key) {
                    if (isset($context['properties'][$key])) {
                        unset($context['properties'][$key]);
                    }
                    if (($index = array_search($key, $context['required'] ?? [])) !== false) {
                        unset($context['required'][$index]);
                    }
                }
                $context['required'] = array_values($context['required'] ?? []);
            }
            return $context;
        };
        
        // Add specific contexts with common keys removed
        if (!empty($pageContext)) {
            $schema['page'] = $removeCommonKeys($pageContext);
        }
        
        if (!empty($archiveContext)) {
            $schema['archive'] = $removeCommonKeys($archiveContext);
        }
        
        // Allow plugins/themes to modify
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
            
            // Extract structure
            return $this->extractStructure($context);
        } catch (\Exception $e) {
            error_log('Error generating context schema: ' . $e->getMessage());
            return [
                'type' => 'object',
                'properties' => [],
                'required' => []
            ];
        }
    }
    
    /**
     * Get JSON Schema type from a value
     * 
     * @param mixed $value The value to get type from
     * @return string JSON Schema type as string
     */
    private function getJsonSchemaType($value): string
    {
        if ($value === null) return 'null';
        if (is_string($value)) return 'string';
        if (is_int($value)) return 'integer';
        if (is_float($value)) return 'number';
        if (is_bool($value)) return 'boolean';
        if (is_array($value)) return 'object';
        return 'string'; // Default for unknown types
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
     * @param string $contextName The context name
     * @param string $className The interface class name
     * @param array<string, mixed> $contextData The context data structure
     * @return string PHP interface definition
     */
    private function generateInterfaceForContext(string $contextName, string $className, array $contextData): string
    {
        // Implementation details omitted for brevity
        return ""; // Replace with actual implementation
    }
} 