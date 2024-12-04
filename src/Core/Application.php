<?php
namespace WPFasty\Core;

class Application {
    private static $instance = null;
    private $container;

    private function __construct() {
        error_log('Application construction started: ' . microtime(true));
        
        $this->container = new Container();
        $this->registerServices();
        $this->bootServices();
        
        error_log('Application construction finished: ' . microtime(true));
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerServices(): void {
        if (!class_exists('\WPFasty\Hooks\ThemeHooks')) {
            error_log('ThemeHooks class not found');
        }
        
        if (!class_exists('\WPFasty\Hooks\AssetsHooks')) {
            error_log('AssetsHooks class not found');
        }
        
        if (!class_exists('\WPFasty\Hooks\HeaderHooks')) {
            error_log('HeaderHooks class not found');
        }
        
        if (!class_exists('\WPFasty\Templates\FullPageTemplate')) {
            error_log('FullPageTemplate class not found');
        }
        
        $this->container->bind('hooks.theme', function($container) {
            return new \WPFasty\Hooks\ThemeHooks($container);
        });

        $this->container->bind('hooks.assets', function($container) {
            return new \WPFasty\Hooks\AssetsHooks($container);
        });

        $this->container->bind('hooks.header', function($container) {
            return new \WPFasty\Hooks\HeaderHooks($container);
        });

        $this->container->bind('templates.fullpage', function($container) {
            return new \WPFasty\Templates\FullPageTemplate();
        });
    }

    private function bootServices(): void {
        $this->container->get('hooks.theme')->register();
        $this->container->get('hooks.assets')->register();
        $this->container->get('hooks.header')->register();
        $this->container->get('templates.fullpage');
    }

    public function getContainer(): Container {
        return $this->container;
    }
}