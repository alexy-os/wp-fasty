<?php

declare(strict_types=1);

/**
 * Factory Helper class for Container
 *
 * This class helps bridge the gap between Symfony DI Container
 * and PHP closures for service factories.
 *
 * @package WPFasty\Core
 */

namespace WPFasty\Core;

/**
 * Helper class for creating services from factory closures
 */
class FactoryHelper
{
    /**
     * Create a service using the stored factory closure
     *
     * @param string $abstract The service identifier
     * @param Container $container The container instance
     * @return mixed The created service
     * @throws \Exception When factory not found
     */
    public static function create(string $abstract, Container $container)
    {
        $factory = $container->getFactory($abstract);
        
        if ($factory === null) {
            throw new \Exception("Factory for service '{$abstract}' not found");
        }
        
        return $factory($container);
    }
}
