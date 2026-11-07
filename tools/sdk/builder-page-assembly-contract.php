<?php

declare(strict_types=1);

// NON-LIVE: shared real theme fixture, real PHP/worker transport, injected runner only.
use WPFastyBuilder\AgentModule;
use WPFastyBuilder\Chat;
use WPFastyBuilder\Layout;
use WPFastyBuilder\Store;

if (!defined('WP_CLI') || !WP_CLI || '1' !== getenv('WPFB_CONTRACT_HTTP')) {
    throw new RuntimeException('Use an isolated CLI WP with the NON-LIVE HTTP fixture.');
}
if (!class_exists(AgentModule::class) || !AgentModule::enabled()) {
    require_once __DIR__.'/agent-module-fixture.php';
}
$assemblyChecks = 0;
$rewriteSeed = [];
$assembly = static function (bool $ok, string $label) use (&$assemblyChecks): void {
    if (!$ok) {
        throw new RuntimeException(sprintf('NON-LIVE assembly FAIL: %s', $label));
    }
    ++$assemblyChecks;
    echo 'PASS assembly '.$label."\n";
};
$settings = static fn () => [
    'worker_port' => Chat::validPort(getenv('WPFB_CONTRACT_PORT')) ?? Chat::DEFAULT_PORT,
    'worker_token' => str_repeat('nonlive-', 5), 'cursor_key' => 'TEST_ONLY_NO_PROVIDER_KEY',
];
$prepare = static function (int $page) use ($assembly, &$rewriteSeed): void {
    $published = Store::get($page, false);
    $draft = Store::get($page, true);
    $request = static function (string $action, array $body) use ($page): WP_REST_Request {
        $r = new WP_REST_Request('POST', '/wpfasty-builder/v1/chat/'.$page.'/'.$action);
        $r->set_url_params(['id' => $page]);
        $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $r->set_header('Content-Type', 'application/json');
        $r->set_body(wp_json_encode($body));

        return $r;
    };
    $turn = Chat::turn($request('turn', [
        'requestId' => 'assembly-turn-0001', 'content' => 'NON-LIVE page assembly: hero, about, CTA',
    ]));
    $assembly($turn instanceof WP_REST_Response, 'real module Chat reaches injected worker runner');
    $assembly(Store::get($page, true) === $draft, 'tool proposal alone does not save a draft');
    $apply = Chat::apply($request('apply', ['requestId' => 'assembly-turn-0001']));
    $assembly(
        $apply instanceof WP_REST_Response,
        'explicit human apply passes authoritative installed-catalog validation',
    );
    $blocks = Layout::blocks(Store::get($page, true) ?? []);
    $rewriteSeed = $blocks;
    $assembly(3 === count($blocks), 'three compatible structural blocks persisted in draft');
    $assembly(Store::get($page, false) === $published, 'agent turn and apply never alter the human-published layout');
};
$inspect = static function (string $html, int $page, bool $preview, string $scheme) use ($assembly): void {
    foreach (['fixture-hero', 'fixture-about', 'fixture-cta'] as $id) {
        $present = str_contains($html, 'data-wpfb-block="'.$id.'"');
        $assembly($preview === $present, ($preview ? 'saved draft '.$scheme : 'published page').' isolates '.$id);
    }
};
add_filter('pre_option_'.Chat::OPTION, $settings);
add_action('wpfb_nonlive_appearance_page', $prepare);
add_action('wpfb_nonlive_appearance_render', $inspect, 10, 4);
try {
    require dirname(__DIR__).'/qa/theme-appearance-contract.php';
    $assembly(14 === $assemblyChecks, 'combined tool/apply/theme light/dark/live hooks all executed');
} finally {
    remove_filter('pre_option_'.Chat::OPTION, $settings);
    remove_action('wpfb_nonlive_appearance_page', $prepare);
    remove_action('wpfb_nonlive_appearance_render', $inspect, 10);
}
echo sprintf("NON-LIVE assembly complete: %d checks; no paid model or agent publishing.\n", $assemblyChecks);

// Same non-live rewrite intent, same existing layout: compare draft and published-only fallback.
$rewritePages = [];
try {
    foreach (['draft', 'publish'] as $postStatus) {
        $rewritePage = wp_insert_post(['post_type' => 'page', 'post_status' => $postStatus, 'post_title' => 'NON-LIVE existing text rewrite']);
        $assembly(is_int($rewritePage) && $rewritePage > 0, 'disposable '.$postStatus.' rewrite page');
        $rewritePages[] = $rewritePage;
        $seed = Store::save($rewritePage, ['source' => 'ui8kit', 'blocks' => $rewriteSeed], 'publish' === $postStatus);
        $assembly(!is_wp_error($seed), 'same three-block seed validates for '.$postStatus);
        if ('publish' === $postStatus) {
            delete_post_meta($rewritePage, Store::DRAFT);
        }
        $liveBefore = get_post_meta($rewritePage, Store::PUBLISHED, true);
        $draftBefore = get_post_meta($rewritePage, Store::DRAFT, true);
        $snapshot = WPFastyBuilder\PageOperations::snapshot($rewritePage);
        $assembly(is_array($snapshot) && $snapshot['blocks'] === $rewriteSeed && $snapshot['version'] === WPFastyBuilder\DraftGuard::version($rewritePage), 'published fallback and draft snapshot share authoritative blocks/revision');
        $makeRequest = static function (string $action) use ($rewritePage): WP_REST_Request {
            $r = new WP_REST_Request('POST', '/wpfasty-builder/v1/chat/'.$rewritePage.'/'.$action);
            $r->set_url_params(['id' => $rewritePage]);
            $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
            $r->set_header('Content-Type', 'application/json');
            $r->set_body(wp_json_encode(['requestId' => 'rewrite-existing-0001', 'content' => 'NON-LIVE rewrite existing text: Перепиши тексты текущих блоков на русском, сохрани структуру. Не публикуй.']));

            return $r;
        };
        add_filter('pre_option_'.Chat::OPTION, $settings);
        $turn = Chat::turn($makeRequest('turn'));
        $assembly($turn instanceof WP_REST_Response, 'same text rewrite reaches tool guards for '.$postStatus);
        $assembly($liveBefore === get_post_meta($rewritePage, Store::PUBLISHED, true) && $draftBefore === get_post_meta($rewritePage, Store::DRAFT, true), 'proposal alone changes neither published nor draft metadata');
        $apply = Chat::apply($makeRequest('apply'));
        $assembly($apply instanceof WP_REST_Response, 'explicit human apply forks/updates draft for '.$postStatus);
        $changed = Layout::blocks(Store::get($rewritePage, true) ?? []);
        $expected = array_map(static function (array $block): array {
            $block['props']['title'] = 'Мастерская: '.$block['id'];

            return $block;
        }, $rewriteSeed);
        $assembly($changed === $expected, 'only installed title props change; ids/order/variants/bindings unchanged');
        $assembly($liveBefore === get_post_meta($rewritePage, Store::PUBLISHED, true) && get_post_status($rewritePage) === $postStatus, 'live layout and WordPress publication status unchanged');
        $assembly($snapshot['version'] + 1 === WPFastyBuilder\DraftGuard::version($rewritePage), 'draft revision increments exactly once');
        remove_filter('pre_option_'.Chat::OPTION, $settings);
    }
} finally {
    remove_filter('pre_option_'.Chat::OPTION, $settings);
    foreach ($rewritePages as $rewritePage) {
        wp_delete_post($rewritePage, true);
    }
}
echo "NON-LIVE published/draft existing-text rewrite checks complete; no model calls.\n";
