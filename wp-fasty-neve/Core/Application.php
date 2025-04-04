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
    }

    private function bootServices(): void {
        $this->container->get('templates.fullpage');
        $this->container->get('hooks.page_template')->register();
    }

    public function getContainer(): Container {
        return $this->container;
    }
}