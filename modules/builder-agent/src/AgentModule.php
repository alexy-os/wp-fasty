<?php

declare(strict_types=1);

namespace WPFastyBuilder;

use PhpFasty\Moduly\Contract\ModuleInterface;
use PhpFasty\Moduly\Kernel\ModuleContext;

/** Optional ModulY lifecycle; no operation here deletes user drafts, policy or history. */
final class AgentModule implements ModuleInterface
{
    private static bool $enabled = false;

    public static function enabled(): bool
    {
        return self::$enabled;
    }

    /** @param list<array{id: string, label: string}> $features
     *
     * @return list<array{id: string, label: string}>
     */
    public static function features(array $features): array
    {
        return self::$enabled ? [...$features, ['id' => 'builder-agent', 'label' => __('Chat', 'wpfasty-builder')]] : $features;
    }

    public function boot(ModuleContext $context): void
    {
        if (!class_exists(PageOperations::class) || !PageOperations::compatible(1)) {
            throw new \RuntimeException('Builder Agent requires active Builder page operations API 1.');
        }
        Chat::register();
        AgentPolicy::register();
        add_filter('rest_endpoints', [self::class, 'endpoints']);
        add_filter('wpfasty_builder_features', [self::class, 'features']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue'], 5);
        add_action('admin_post_wpfb_agent_asset', [self::class, 'asset']);
        self::$enabled = true;
    }

    public function activate(ModuleContext $context): void
    {
        // ModulY can re-enable an already booted instance in the same request.
        self::$enabled = true;
        // Existing nonsensitive policy/options/page history stay in place. No migration is required.
    }

    public function deactivate(ModuleContext $context): void
    {
        self::$enabled = false;
        // No data deletion and no Node process starts/stops from WordPress.
    }

    /** @param array<string, mixed> $routes
     *
     * @return array<string, mixed>
     */
    public static function endpoints(array $routes): array
    {
        if (self::$enabled) {
            return $routes;
        }
        foreach (array_keys($routes) as $route) {
            if (str_starts_with($route, '/wpfasty-builder/v1/chat/') || str_starts_with($route, '/wpfasty-builder/v1/agent-brief/') || '/wpfasty-builder/v1/agent-policy' === $route) {
                unset($routes[$route]);
            }
        }

        return $routes;
    }

    public static function enqueue(string $hook): void
    {
        if (!self::$enabled || !Admin::isEditor($hook)) {
            return;
        }
        wp_enqueue_script('wpfasty-builder-agent', admin_url('admin-post.php?action=wpfb_agent_asset&kind=js'), [], '0.1.0', true);
        wp_enqueue_style('wpfasty-builder-agent', admin_url('admin-post.php?action=wpfb_agent_asset&kind=css'), [], '0.1.0');
    }

    /** Fixed, authenticated read-only assets, also works when MODULY_DIR is outside the public document root. */
    public static function asset(): void
    {
        if (!self::$enabled || (!current_user_can('edit_pages') && !current_user_can(AgentPolicy::CAP))) {
            wp_die(esc_html__('Not allowed.', 'wpfasty-builder'), '', ['response' => 403]);
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Authenticated GET of fixed nonsensitive static assets; no state change, no caller-controlled file path.
        $kind = 'css' === WordPressInput::query('kind') ? 'css' : 'js';
        $path = dirname(__DIR__).'/assets/agent.'.$kind;
        if (!is_file($path)) {
            wp_die(esc_html__('Agent assets unavailable. Reinstall the complete module.', 'wpfasty-builder'), '', ['response' => 503]);
        }
        header('Content-Type: '.('css' === $kind ? 'text/css' : 'application/javascript').'; charset=UTF-8');
        header('X-Content-Type-Options: nosniff');
        nocache_headers();
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Fixed packaged nonsensitive JS/CSS, authenticated above; never a user supplied path.
        readfile($path);
        exit;
    }
}
