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
        $screens = PageOnboarding::screens(new Catalog($source->catalog()), $source);
        $check(12 === count($screens), $engine.': twelve installed screens');
        $check(count(array_unique(array_column($screens, 'id'))) === count($screens), $engine.': stable unique IDs');
        foreach ($screens as $screen) {
            $check([] !== $screen['blocks'] && isset($screen['preview']), $engine.': real blocks and preview');
            $check(strlen((string) wp_json_encode(['referenceScreenData' => $screen, 'userRequest' => 'Fill coherently'])) < 8000, $engine.': bounded composer reference');
        }
    }
    $installed = WPFastyBuilder\Sources::catalog('ui8kit');
    $check($installed instanceof Catalog, 'active adapter has installed catalog');
    $installedScreens = PageOnboarding::screens($installed, WPFastyBuilder\Sources::get('ui8kit'));
    $reference = PageOnboarding::reference('ui8kit', $installedScreens[0]['id']);
    $check(is_array($reference) && $reference['blocks'] === $installedScreens[0]['blocks'], 'reference JSON resolved from installed source only');
    $check('wpfasty-screen-reference/1' === $reference['schema'], 'versioned reference envelope');
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
    $old = wp_insert_post(['post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'NON-LIVE existing draft']);
    $ids[] = $old;
    $check(!PageOnboarding::eligible($old), 'existing empty page remains unchanged');
    $check(null === PageOnboarding::reference('ui8kit', 'uninstalled:screen'), 'unknown reference cannot be resolved');
    echo 'Onboarding contract passed: '.$assertions." assertions; no model or provider requests.\n";
} finally {
    foreach ($ids as $id) {
        wp_delete_post($id, true);
    }
    wp_set_current_user($originalUser);
    remove_filter('pre_http_request', $noNetwork);
    remove_filter('wpfasty_builder_engine', $fixtureEngine);
}
