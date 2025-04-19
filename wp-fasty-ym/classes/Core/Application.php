<?php
namespace WPFasty\Core;

class Application {
    private static $instance = null;
    private $container;

    private function __construct() {
        $this->container = new Container();
        $this->registerServices();
        $this->bootServices();
    }

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function registerServices(): void {

        $this->container->bind('templates.fullpage', function($container) {
            return new \WPFasty\Templates\FullPageTemplate();
        });

        $this->container->singleton('hooks.page_template', function($container) {
            return new \WPFasty\Hooks\PageTemplateHooks($container);
        });
        
        $this->container->singleton('hooks.assets', function($container) {
            return new \WPFasty\Hooks\AssetsHooks($container);
        });
        
        $this->container->singleton('tools.html_editor', function($container) {
            return new \WPFasty\Tools\HtmlToEditor($container);
        });
    }

    private function bootServices(): void {
        $this->container->get('templates.fullpage');
        $this->container->get('hooks.page_template')->register();
        $this->container->get('hooks.assets')->register();
        $this->container->get('tools.html_editor')->register();
    }

    public function getContainer(): Container {
        return $this->container;
    }
}