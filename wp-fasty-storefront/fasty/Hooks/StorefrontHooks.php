<?php
/**
 * Storefront Hooks
 * Handles Storefront theme-specific hooks and overrides
 */

namespace FastyChild\Hooks;

use FastyChild\Core\Container;

class StorefrontHooks {
    /**
     * Container instance
     * @var Container
     */
    private $container;
    
    /**
     * Constructor
     * 
     * @param Container $container
     */
    public function __construct(Container $container) {
        $this->container = $container;
    }
    
    /**
     * Register hook handlers
     * 
     * @return void
     */
    public function register(): void {
        // Модифицируем метод register, чтобы он запускал обработку хуков на раннем этапе
        add_action('init', [$this, 'processAllHooksFromConfig'], 5);
        
        // Основные фильтры Storefront
        add_filter('storefront_page_layout', [$this, 'modifyPageLayout']);
        add_filter('storefront_footer_widget_columns', [$this, 'modifyFooterWidgetColumns']);
        add_filter('storefront_credit_text', [$this, 'modifyCreditText']);
    }
    
    /**
     * Обрабатывает все хуки из конфигурации
     */
    public function processAllHooksFromConfig(): void {
        $app = $this->container->get('app');
        $hooks_config = $app->config('storefront.hooks', []);
        
        // Перебираем все хуки из конфигурации (storefront_header, storefront_footer и т.д.)
        foreach ($hooks_config as $hook_name => $config) {
            // Удаляем хуки, указанные в секции 'remove'
            if (isset($config['remove']) && is_array($config['remove'])) {
                foreach ($config['remove'] as $callback => $priority) {
                    remove_action($hook_name, $callback, $priority);
                }
            }
            
            // Добавляем хуки, указанные в секции 'add'
            if (isset($config['add']) && is_array($config['add'])) {
                foreach ($config['add'] as $method => $priority) {
                    // Проверяем, существует ли метод в этом классе
                    if (method_exists($this, $method)) {
                        add_action($hook_name, [$this, $method], $priority);
                    }
                }
            }
        }
        
        // Особая обработка для override_header, если флаг включен
        if ($app->config('storefront.override_header', false)) {
            remove_all_actions('storefront_header');
            add_action('storefront_header', [$this, 'customHeader'], 10);
        }
    }
    
    /**
     * Modify page layout
     * 
     * @param string $layout Default layout
     * @return string Modified layout
     */
    public function modifyPageLayout(string $layout): string {
        $app = $this->container->get('app');
        $custom_layout = $app->config('storefront.page_layout', $layout);
        
        return $custom_layout;
    }
    
    /**
     * Modify footer widget columns
     * 
     * @param int $columns Default number of columns
     * @return int Modified number of columns
     */
    public function modifyFooterWidgetColumns(int $columns): int {
        $app = $this->container->get('app');
        $custom_columns = $app->config('storefront.footer_widget_columns', $columns);
        
        return $custom_columns;
    }
    
    /**
     * Modify credit text
     * 
     * @param string $text Default credit text
     * @return string Modified credit text
     */
    public function modifyCreditText(string $text): string {
        $app = $this->container->get('app');
        $custom_text = $app->config('storefront.credit_text', $text);
        
        if ($custom_text !== $text) {
            return $custom_text;
        }
        
        // If no custom text is defined, you can still modify the default
        return $text;
    }
    
    /**
     * Custom header implementation
     * 
     * @return void
     */
    public function customHeader(): void {
        ?>
        <header class="fasty-navbar">
            <div class="fasty-navbar-container">
                <!-- Бренд (логотип/название) слева -->
                <div class="fasty-navbar-brand">
                    <?php storefront_site_title_or_logo(); ?>
                </div>
                
                <!-- Навигация и действия справа -->
                <div class="fasty-navbar-actions">
                    <!-- Основная навигация -->
                    <?php if (function_exists('storefront_primary_navigation')): ?>
                        <nav class="fasty-navbar-menu">
                            <?php storefront_primary_navigation(); ?>
                        </nav>
                    <?php endif; ?>
                    
                    <!-- Иконки справа (корзина, поиск и др.) -->
                    <div class="fasty-navbar-icons">
                        <?php if (function_exists('storefront_header_cart')): ?>
                            <div class="fasty-navbar-cart">
                                <?php storefront_header_cart(); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (function_exists('storefront_product_search')): ?>
                            <div class="fasty-navbar-search">
                                <?php storefront_product_search(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Мобильная кнопка меню (видна только на маленьких экранах) -->
                <button class="fasty-navbar-toggle" aria-label="Toggle menu">
                    <span class="fasty-navbar-toggle-bar">1</span>
                    <span class="fasty-navbar-toggle-bar">2</span>
                    <span class="fasty-navbar-toggle-bar">3</span>
                </button>
            </div>
        </header>
        
        <!-- Мобильное меню (скрыто по умолчанию) -->
        <div class="fasty-mobile-menu">
            <?php if (function_exists('storefront_primary_navigation')): ?>
                <?php storefront_primary_navigation(); ?>
            <?php endif; ?>
        </div>
        <?php
    }
} 