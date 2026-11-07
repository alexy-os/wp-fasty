<?php

/** NON-LIVE: wp --path=OUR_ISOLATED_WP eval-file tools/sdk/builder-wp-contract.php. Never call on a customer site. */
require_once dirname(__DIR__, 2).'/plugins/wpfasty-builder/wpfasty-builder.php';
require_once __DIR__.'/agent-module-fixture.php';
use WPFastyBuilder\Chat;
use WPFastyBuilder\DraftGuard;
use WPFastyBuilder\Store;

function contract(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException(sprintf('NON-LIVE FAIL: %s', $label));
    } echo 'PASS '.$label."\n";
}
$engine = static fn () => 'latte';
add_filter('wpfasty_builder_engine', $engine);
$originalUser = get_current_user_id();
$admin = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
if (!$admin) {
    throw new RuntimeException('Own WP needs an administrator test account.');
}
$page = wp_insert_post(['post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'NON-LIVE Builder worker contract']);
if (!$page || is_wp_error($page)) {
    throw new RuntimeException('Cannot create local disposable fixture');
}
$request = static function (string $action, array $body = []) use ($page): WP_REST_Request {
    $r = new WP_REST_Request(in_array($action, ['history', 'progress'], true) ? 'GET' : 'POST', '/wpfasty-builder/v1/chat/'.$page.'/'.$action);
    $r->set_url_params(['id' => $page]);
    $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    $r->set_header('Content-Type', 'application/json');
    $r->set_body(wp_json_encode($body));
    if ('progress' === $action) {
        $r->set_query_params($body);
    }

    return $r;
};
$fixturePort = Chat::validPort(getenv('WPFB_CONTRACT_PORT')) ?? Chat::DEFAULT_PORT;
$settings = static fn () => ['worker_port' => $fixturePort, 'worker_token' => str_repeat('nonlive-', 5), 'cursor_key' => 'TEST_ONLY_NO_PROVIDER_KEY'];
$http = static function ($pre, $args, $url) use ($fixturePort) {
    if (!str_starts_with($url, 'http://127.0.0.1:'.$fixturePort.'/')) {
        return $pre;
    }
    if (str_ends_with($url, '/status')) {
        return ['response' => ['code' => 200], 'headers' => [], 'body' => wp_json_encode(['protocol' => 'wpfasty-agent/1', 'capabilities' => ['local-search/1', 'policy/1']])];
    }
    if (str_ends_with($url, '/progress')) {
        $scope = json_decode($args['body'], true);
        contract(!isset($scope['credentials']), 'progress never sends provider credential');

        return ['response' => ['code' => 200], 'body' => wp_json_encode(['requestId' => $scope['requestId'], 'state' => 'done', 'sequence' => 0, 'tool' => null]), 'headers' => []];
    }
    if (str_ends_with($url, '/history')) {
        $scope = json_decode($args['body'], true);
        $cached = json_decode((string) get_post_meta((int) $scope['page'], '_wpfb_turn_fixture-turn-0001', true), true);
        $turns = is_array($cached) ? [array_merge($cached['result'], ['requestId' => 'fixture-turn-0001', 'state' => 'done'])] : [];

        return ['response' => ['code' => 200], 'body' => wp_json_encode(['turns' => $turns]), 'headers' => []];
    }
    $body = json_decode($args['body'], true);
    contract(($body['credentials']['cursor'] ?? '') === 'TEST_ONLY_NO_PROVIDER_KEY', 'credential server-side only');
    contract(($args['redirection'] ?? -1) === 0, 'no worker redirects');
    $block = $body['context']['catalog'][0];

    return ['response' => ['code' => 200], 'headers' => [], 'body' => wp_json_encode(['source' => $body['context']['source'], 'answer' => 'NON-LIVE structural proposal', 'proposal' => [['id' => 'fixture-about', 'block' => $block['id'], 'variant' => $block['variants'][0]['id'], 'props' => (object) [], 'bindings' => (object) []]], 'baseVersion' => $body['context']['version']])];
};
try {
    wp_set_current_user((int) $admin[0]);
    contract(Chat::allowed($request('history')), 'administrator with nonce permitted');
    $routes = rest_get_server()->get_routes();
    $progressRoute = $routes['/wpfasty-builder/v1/chat/(?P<id>\d+)/progress'][0] ?? [];
    contract(($progressRoute['permission_callback'] ?? null) === [Chat::class, 'allowed'] && isset($progressRoute['methods']['GET']), 'progress GET wired to per-page nonce/capability guard');
    $deny = static fn () => ['do_not_allow'];
    add_filter('map_meta_cap', $deny);
    contract(!Chat::allowed($request('history')), 'valid nonce without edit capability rejected');
    remove_filter('map_meta_cap', $deny);
    $bad = $request('history');
    $bad->set_header('X-WP-Nonce', 'bad');
    contract(!Chat::allowed($bad), 'bad nonce rejected');
    wp_set_current_user(0);
    contract(!Chat::allowed($request('history')), 'anonymous rejected');
    wp_set_current_user((int) $admin[0]);
    $none = static fn () => [];
    add_filter('pre_option_'.Chat::OPTION, $none);
    $result = Chat::history($request('history'));
    contract(is_wp_error($result) && 'wpfb_chat_unconfigured' === $result->get_error_code(), 'missing config deterministic 503');
    remove_filter('pre_option_'.Chat::OPTION, $none);
    add_filter('pre_option_'.Chat::OPTION, $settings);
    // Host marker without a host token (local-wp vault locked): fail closed, never fall back to the DB copy.
    $_SERVER['WPFB_WORKER_KEY'] = 'managed';
    unset($_SERVER['WPFB_WORKER_TOKEN']);
    $sent = false;
    $spy = static function ($pre) use (&$sent) {
        $sent = true;

        return $pre;
    };
    add_filter('pre_http_request', $spy, 1);
    $locked = Chat::history($request('history'));
    remove_filter('pre_http_request', $spy, 1);
    contract(is_wp_error($locked) && 'wpfb_secret_unavailable' === $locked->get_error_code() && !$sent, 'managed marker + no host token fails closed without DB fallback or worker request');
    unset($_SERVER['WPFB_WORKER_KEY']);
    if (!getenv('WPFB_CONTRACT_HTTP')) {
        add_filter('pre_http_request', $http, 10, 3);
    }
    ob_start();
    Chat::settings();
    $html = ob_get_clean();
    contract(!str_contains($html, 'TEST_ONLY_NO_PROVIDER_KEY') && str_contains($html, 'Configured (masked)'), 'settings never print saved credential');
    $res = Chat::turn($request('turn', ['requestId' => 'fixture-turn-0001', 'content' => 'NON-LIVE test draft']));
    if (is_wp_error($res)) {
        echo 'Fixture error code: '.$res->get_error_code()."\n";
    }
    contract($res instanceof WP_REST_Response, 'catalog turn proxy response');
    contract(null === Store::get($page, false), 'turn did not publish');
    $progress = Chat::progress($request('progress', ['requestId' => 'fixture-turn-0001']));
    contract($progress instanceof WP_REST_Response && 'done' === $progress->get_data()['state'] && null === $progress->get_data()['tool'], 'read-only scoped progress reports persisted terminal state');
    contract(is_wp_error(Chat::progress($request('progress', ['requestId' => ['invalid']]))), 'array progress request rejected before transport');
    contract(is_wp_error(Chat::apply($request('apply', ['requestId' => ['invalid']]))), 'array apply request rejected before draft mutation');
    $apply = Chat::apply($request('apply', ['requestId' => 'fixture-turn-0001']));
    if (is_wp_error($apply)) {
        echo wp_json_encode($apply->get_error_data())."\n";
    }
    contract($apply instanceof WP_REST_Response, 'explicit apply saves actual catalog draft');
    $version = DraftGuard::version($page);
    contract(1 === $version, 'draft revision incremented');
    contract(null === Store::get($page, false), 'apply never publishes');
    $again = Chat::apply($request('apply', ['requestId' => 'fixture-turn-0001']));
    contract($again instanceof WP_REST_Response && DraftGuard::version($page) === $version, 'apply retry idempotent');
    $history = Chat::history($request('history'))->get_data();
    $application = $history['turns'][0]['application'] ?? [];
    if (getenv('WPFB_CONTRACT_HTTP')) {
        contract(($history['turns'][0]['agentPolicy']['policyVersion'] ?? 0) >= 1 && ($history['turns'][0]['agentPolicy']['loadedSkills'][0]['id'] ?? '') === 'block-selection', 'actual WP-worker HTTP history preserves locked policy/loaded-skill versions');
    }
    contract(($application['state'] ?? '') === 'applied' && ($application['appliedVersion'] ?? 0) === $version && false === ($application['canApply'] ?? true), 'applied history reload joins persisted WP revision and hides reapply');
    $noKey = static fn () => ['worker_port' => $fixturePort, 'worker_token' => str_repeat('nonlive-', 5)];
    add_filter('pre_option_'.Chat::OPTION, $noKey, 20);
    $history = Chat::history($request('history'))->get_data();
    contract(false === $history['availability']['configured'] && str_contains($history['availability']['message'], 'Existing conversation history remains available') && !str_contains($history['availability']['message'], 'No model request has been sent'), 'removed key keeps truthful existing history');
    remove_filter('pre_option_'.Chat::OPTION, $noKey, 20);

    $replay = Chat::turn($request('turn', ['requestId' => 'fixture-turn-0001', 'content' => 'NON-LIVE test draft']));
    contract($replay instanceof WP_REST_Response, 'turn replay after revision change no provider call');
    $conflict = Chat::turn($request('turn', ['requestId' => 'fixture-turn-0001', 'content' => 'different']));
    contract(is_wp_error($conflict) && 409 === $conflict->get_error_data()['status'], 'request content conflict 409');
    $stale = Chat::turn($request('turn', ['requestId' => 'fixture-turn-0002', 'content' => 'NON-LIVE stale proposal']));
    $save = DraftGuard::save($page, ['source' => 'ui8kit', 'blocks' => []], false, $version);
    contract(!is_wp_error($save), 'visual draft shares revision lock');
    $current = DraftGuard::version($page);
    $replay = Chat::apply($request('apply', ['requestId' => 'fixture-turn-0001']));
    contract($replay instanceof WP_REST_Response && $replay->get_data()['version'] === $version && $replay->get_data()['currentVersion'] === $current && DraftGuard::version($page) === $current, 'historical applied replay reports both revisions without modifying newer draft');

    $stale = Chat::apply($request('apply', ['requestId' => 'fixture-turn-0002']));
    contract(is_wp_error($stale) && 409 === $stale->get_error_data()['status'], 'stale proposal rejected');
    $save = DraftGuard::save($page, ['source' => 'ui8kit', 'blocks' => []], false, $version);
    contract(is_wp_error($save) && 409 === $save->get_error_data()['status'], 'stale visual save rejected');
    add_post_meta($page, '_wpfb_chat_invalid-0001', wp_slash(wp_json_encode(['source' => 'ui8kit', 'version' => DraftGuard::version($page), 'blocks' => [['id' => 'wrong', 'block' => 'does-not-exist', 'variant' => 'fake', 'props' => [], 'bindings' => []]]])), true);
    $invalid = Chat::apply($request('apply', ['requestId' => 'invalid-0001']));
    contract(is_wp_error($invalid) && 400 === $invalid->get_error_data()['status'], 'unknown catalog proposal rejected');
    remove_filter('pre_http_request', $http, 10);
    $offline = static fn () => new WP_Error('offline', 'NON-LIVE offline fixture');
    add_filter('pre_http_request', $offline, 10, 3);
    $error = Chat::history($request('history'));
    contract(is_wp_error($error) && 'wpfb_worker_offline' === $error->get_error_code(), 'worker offline clear 503');
    remove_filter('pre_http_request', $offline, 10);
} finally {
    remove_filter('wpfasty_builder_engine', $engine);
    remove_filter('pre_option_'.Chat::OPTION, $settings);
    remove_filter('pre_http_request', $http, 10);
    delete_option('wpfb_draft_lock_'.$page);
    wp_delete_post($page, true);
    wp_set_current_user($originalUser);
}
echo "NON-LIVE WordPress contracts complete; no real credentials or models used.\n";
