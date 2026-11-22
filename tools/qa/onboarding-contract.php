<?php

declare(strict_types=1);

// NON-LIVE: run through `wp eval 'require ".../onboarding-contract.php";'` on a disposable fixture site.
use WPFastyBuilder\Catalog;
use WPFastyBuilder\PageOnboarding;
use WPFastyBuilder\Pages;
use WPFastyBuilder\Source\LocalSource;
use WPFastyBuilder\Store;

$originalUser = get_current_user_id();
$ids = [];
$assertions = 0;
$check = static function (bool $ok, string $message) use (&$assertions): void {
    ++$assertions;
    if (!$ok) {
        throw new RuntimeException(sprintf('Onboarding contract: %s', $message));
    }
};
$noNetwork = static function (): never {
    throw new RuntimeException('Onboarding must not fetch provider content at runtime.');
};
$fixtureEngine = static fn (): string => getenv('WPFB_QA_ENGINE') ?: 'latte';
add_filter('wpfasty_builder_engine', $fixtureEngine);
add_filter('pre_http_request', $noNetwork);
try {
    $admins = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    wp_set_current_user((int) ($admins[0] ?? 0));
    foreach (WPFastyBuilder\Plugin::runtimes() as $engine) {
        $source = new LocalSource('ui8kit', WPFastyBuilder\Plugin::$dir.'/sources/ui8kit/'.$engine, $engine);
        $catalog = new Catalog($source->catalog());
        $check(!isset($source->catalog()['collections']) && !method_exists($catalog, 'collections'), $engine.': no template collections');
        $blocks = $source->catalog()['blocks'] ?? [];
        $check(is_array($blocks) && 52 === count($blocks), $engine.': Simple catalog available for insertion');
        foreach (is_array($blocks) ? $blocks : [] as $block) {
            $check(is_array($block) && 1 === ($block['contract']['version'] ?? null), $engine.': block-contract/v1');
        }
    }
    $installed = WPFastyBuilder\Sources::catalog('ui8kit');
    $check($installed instanceof Catalog, 'active adapter has installed catalog');
    $check(null === PageOnboarding::reference('ui8kit', 'press:home'), 'removed screens cannot be referenced');
    $starters = WPFastyBuilder\Starters::forEditor();
    $check(['shop', 'folio', 'press'] === array_column($starters, 'id'), 'three Simple starter cards');
    foreach ($starters as $s) {
        $ref = PageOnboarding::reference('ui8kit', $s['id']);
        $check(is_array($ref) && 'ui8kit' === $ref['source'] && count($ref['blocks']) === $s['blocks'] && count($s['items']) === $s['blocks'], $s['id'].': Chat reference = the server starter file');
        $check(strlen((string) wp_json_encode($ref)) < 7000, $s['id'].': reference fits the bounded Chat reference size');
    }
    $check(null === PageOnboarding::reference('other', 'shop'), 'a starter of another source is not referenced');
    $check(null === PageOnboarding::reference('ui8kit', 'uninstalled'), 'unknown reference cannot be resolved');
    foreach (['standard', 'blank', 'sidebar'] as $mode) {
        $page = Pages::create('NON-LIVE onboarding '.$mode, '', 'empty');
        $check(is_array($page), 'new page created');
        $id = $page['id'];
        $ids[] = $id;
        $check(PageOnboarding::eligible($id), 'only marked empty draft is eligible');
        $request = new WP_REST_Request('POST');
        $request['id'] = $id;
        $request->set_header('content-type', 'application/json');
        $request->set_body((string) wp_json_encode(['mode' => $mode]));
        $denied = PageOnboarding::finish($request);
        $check(is_wp_error($denied) && 'wpfb_forbidden' === $denied->get_error_code(), 'nonce is required');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $result = PageOnboarding::finish($request);
        $check(is_array($result) && $mode === $result['mode'], 'valid shell accepted'.(is_wp_error($result) ? ': '.$result->get_error_code() : ''));
        $check($mode === get_post_meta($id, PageOnboarding::SHELL, true), 'shell persisted');
        $check(!PageOnboarding::eligible($id), 'onboarding is one-time');
        $check(!Store::has($id) && 'draft' === get_post_status($id), 'no publication');
        $again = PageOnboarding::finish($request);
        $check(is_wp_error($again) && 'wpfb_onboarding_conflict' === $again->get_error_code(), 'repeat transition conflicts');
    }
    foreach (['shop', 'folio', 'press'] as $starterId) {
        $page = Pages::create('NON-LIVE onboarding starter '.$starterId, '', 'empty');
        $check(is_array($page), 'new page created');
        $id = $page['id'];
        $ids[] = $id;
        $request = new WP_REST_Request('POST');
        $request['id'] = $id;
        $request->set_header('content-type', 'application/json');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_body((string) wp_json_encode(['mode' => 'standard', 'starter' => 'nope']));
        $bad = PageOnboarding::finish($request);
        $check(is_wp_error($bad) && 'wpfb_starter' === $bad->get_error_code() && PageOnboarding::eligible($id), 'unknown starter refused, page unchanged');
        $request->set_body((string) wp_json_encode(['mode' => 'standard', 'starter' => $starterId, 'blocks' => [['block' => 'evil']]]));
        $result = PageOnboarding::finish($request);
        $check(is_array($result) && 'standard' === $result['mode'], $starterId.': starter applied'.(is_wp_error($result) ? ': '.$result->get_error_code() : ''));
        $draft = WPFastyBuilder\Layout::blocks(Store::get($id, true) ?? []);
        $expected = WPFastyBuilder\Starters::get($starterId);
        $check(null !== $expected && array_column($draft, 'block') === array_column($expected['layout']['blocks'], 'block'), $starterId.': draft = server starter blocks (client blocks ignored)');
        $check(!Store::has($id) && !PageOnboarding::eligible($id), $starterId.': draft only, onboarding finished');
    }
    $old = wp_insert_post(['post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'NON-LIVE existing draft']);
    $ids[] = $old;
    $check(!PageOnboarding::eligible($old), 'existing empty page remains unchanged');
    echo 'Onboarding contract passed: '.$assertions." assertions; no model or provider requests.\n";
} finally {
    foreach ($ids as $id) {
        wp_delete_post($id, true);
    }
    wp_set_current_user($originalUser);
    remove_filter('pre_http_request', $noNetwork);
    remove_filter('wpfasty_builder_engine', $fixtureEngine);
}
