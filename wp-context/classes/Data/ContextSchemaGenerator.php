<?php

declare(strict_types=1);

namespace WPFasty\Data;

use WPFasty\Core\BootableServiceInterface;
use WPFasty\Core\ContainerInterface;

/**
 * Context Schema Generator
 * 
 * Generates a context.json file containing the structure of data returned by context factories
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
     * Path to the context.json file
     *
     * @var string
     */
    protected $schemaPath;
    
    /**
     * Constructor
     *
     * @param ContainerInterface $container Service container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->schemaPath = get_template_directory() . '/context.json';
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
        
        // Write the schema to a file
        file_put_contents($this->schemaPath, json_encode($schema, JSON_PRETTY_PRINT));
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