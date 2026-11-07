<?php

declare(strict_types=1);

// NON-LIVE isolated WP only. Real published ModulY installer/kernel; no provider or Node call.
use PhpFasty\Moduly\Package\Fs;
use PhpFasty\Moduly\Wp\Plugin as ModulyPlugin;
use WPFastyBuilder\AgentModule;
use WPFastyBuilder\Chat;
use WPFastyBuilder\DraftGuard;
use WPFastyBuilder\PageOperations;

if (!defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('This fixture is CLI-only.');
}
if (get_option('moduly_modules', [])) {
    throw new RuntimeException('Use an isolated WP without installed modules; never run on a customer site.');
}
$root = dirname(__DIR__, 2);
$runtime = getenv('WPFB_MODULY_RUNTIME');
if (!is_string($runtime) || !is_file($runtime.'/moduly.php')) {
    throw new RuntimeException('Set WPFB_MODULY_RUNTIME to the verified published ModulY plugin directory.');
}
$check = static function (bool $ok, string $label): void {
    if (!$ok) {
        throw new RuntimeException(sprintf('NON-LIVE module FAIL: %s', $label));
    }
    echo 'PASS module '.$label."\n";
};
$original = get_current_user_id();
$admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
wp_set_current_user((int) ($admins[0] ?? 0));
$check(current_user_can('edit_pages'), 'own fixture admin exists');
$engine = static fn () => 'latte';
add_filter('wpfasty_builder_engine', $engine);
$page = wp_insert_post(['post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'NON-LIVE optional module lifecycle']);
$check(is_int($page) && $page > 0, 'disposable page created');
$check([] === apply_filters('wpfasty_builder_features', []), 'core alone exposes no Agent feature');
$routes = rest_get_server()->get_routes();
$check(!isset($routes['/wpfasty-builder/v1/chat/(?P<id>\d+)/history']), 'core alone has no chat route');
$saved = DraftGuard::locked($page, static fn () => PageOperations::replaceLocked($page, 'ui8kit', [], 0));
$check(!is_wp_error($saved), 'core manual draft saves with no ModulY or Node');
$policy = get_option('wpfb_agent_policy_v1', false);
$history = 'NON-LIVE preserved history';
update_post_meta($page, '_wpfb_turn_fixture-preserve', $history);
$directory = sys_get_temp_dir().'/wpfb-moduly-fixture-'.bin2hex(random_bytes(6));
define('MODULY_DIR', $directory);
require_once $runtime.'/moduly.php';
ModulyPlugin::load();
$host = ModulyPlugin::instance();
$check(null !== $host, 'actual published host loaded');
$check([] === apply_filters('wpfasty_builder_features', []), 'ModulY without Agent leaves core manual');
// Only this disposable CLI process uses upstream's explicit development unsigned-package mode.
// Production trust/signature policy is not modified and no signing keys are read or generated.
$check(in_array(wp_get_environment_type(), ['local', 'development'], true), 'explicit development-only unsigned fixture mode');
define('MODULY_ALLOW_UNSIGNED', true);
register_shutdown_function(static function () use ($host, $directory, $page, $original): void {
    try {
        if (isset($host->modules->records()['builder-agent'])) {
            $host->modules->disable('builder-agent');
            $host->modules->remove('builder-agent');
        }
    } finally {
        delete_option('moduly_modules');
        delete_option('moduly_modules_meta');
        delete_option('moduly_pending_activate');
        wp_delete_post($page, true);
        wp_set_current_user($original);
        Fs::remove($directory);
    }
});
$host->modules->install($root.'/var/build/builder-agent-unsigned.zip');
$check([] === apply_filters('wpfasty_builder_features', []), 'install does not auto-enable Agent');
$host->modules->enable('builder-agent');
$check(AgentModule::enabled(), 'actual kernel boot invokes Agent lifecycle');
WPFastyBuilder\AgentPolicy::roles();
$check('builder-agent' === (apply_filters('wpfasty_builder_features', [])[0]['id'] ?? null), 'enabled optional feature adapter registered');
// WordPress fires rest_api_init on the next request after module installation; simulate that event here.
Chat::routes();
WPFastyBuilder\AgentPolicy::routes();
$fakeSettings = static fn () => ['worker_token' => str_repeat('NONLIVE-', 5), 'cursor_key' => 'TEST_ONLY_NO_PROVIDER_KEY'];
add_filter('pre_option_'.Chat::OPTION, $fakeSettings);
$old = static fn () => ['response' => ['code' => 200], 'headers' => [], 'body' => wp_json_encode(['active' => false, 'credentials' => 'managed'])];
add_filter('pre_http_request', $old);
$request = new WP_REST_Request('GET', '/wpfasty-builder/v1/chat/'.$page.'/history');
$request->set_url_params(['id' => $page]);
$request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
$bad = Chat::history($request);
$check(is_wp_error($bad) && 'wpfb_worker_incompatible' === $bad->get_error_code(), 'old 873b00d resource rejected before any model operation');
remove_filter('pre_http_request', $old);
$offline = static fn () => new WP_Error('fixture_no_node', 'NON-LIVE offline');
add_filter('pre_http_request', $offline);
$bad = Chat::history($request);
$check(is_wp_error($bad) && 'wpfb_worker_offline' === $bad->get_error_code(), 'Node unavailable is explanatory Agent-only error');
remove_filter('pre_http_request', $offline);
remove_filter('pre_option_'.Chat::OPTION, $fakeSettings);
$snapshot = PageOperations::snapshot($page);
$check(!is_wp_error($snapshot) && $snapshot['version'] >= 1, 'manual page port unaffected by unavailable worker');
$host->modules->disable('builder-agent');
$check(!AgentModule::enabled() && [] === apply_filters('wpfasty_builder_features', []), 'disable removes optional feature');
$check(!Chat::allowed($request), 'disabled module rejects a still-loaded callback');
$check(!WPFastyBuilder\AgentPolicy::allowed($request), 'disabled policy callback also rejects access');
$check(!isset(rest_get_server()->get_routes()['/wpfasty-builder/v1/agent-policy']), 'disabled module exposes no policy route');
$check($history === get_post_meta($page, '_wpfb_turn_fixture-preserve', true) && $policy === get_option('wpfb_agent_policy_v1', false), 'disable preserves history and policy');
$check(!is_wp_error(PageOperations::snapshot($page)), 'disabled Agent leaves manual page usable');
$host->modules->enable('builder-agent');
$check(AgentModule::enabled(), 'explicit re-enable works without deleting data');
Chat::routes();
WPFastyBuilder\AgentPolicy::routes();
