<?php

declare(strict_types=1);

// NON-LIVE: included by the real PHP/worker assembly fixture. No SDK/model runner.
$referencePage = wp_insert_post(['post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'NON-LIVE screen reference']);
$referenceCalls = 0;
$countReferenceCalls = static function (mixed $pre, array $args, string $url) use (&$referenceCalls): mixed {
    if (str_ends_with($url, '/turn')) {
        ++$referenceCalls;
    }

    return $pre;
};
add_filter('pre_http_request', $countReferenceCalls, 10, 3);
add_filter('pre_option_'.WPFastyBuilder\Chat::OPTION, $settings);
try {
    $screens = WPFastyBuilder\PageOnboarding::screens(WPFastyBuilder\Sources::catalog('ui8kit'), WPFastyBuilder\Sources::get('ui8kit'));
    $makeReference = static function (array $override = []) use ($referencePage, $screens): WP_REST_Request {
        $request = new WP_REST_Request('POST');
        $request->set_url_params(['id' => $referencePage]);
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_header('Content-Type', 'application/json');
        $request->set_body(wp_json_encode($override + ['requestId' => 'screen-reference-0001', 'content' => 'NON-LIVE screen reference', 'screenId' => $screens[0]['id'], 'referenceScreenData' => ['blocks' => ['MALICIOUS_CLIENT_JSON']]]));

        return $request;
    };
    $before = WPFastyBuilder\Store::get($referencePage, true);
    $first = WPFastyBuilder\Chat::turn($makeReference());
    $assembly($first instanceof WP_REST_Response, 'installed screen reference reaches real worker with canonical server JSON');
    $assembly(1 === $referenceCalls, 'one explicit turn transport');
    $replay = WPFastyBuilder\Chat::turn($makeReference(['referenceScreenData' => ['ignored' => 'different client JSON']]));
    $assembly($replay instanceof WP_REST_Response && $replay->get_data() === $first->get_data(), 'replay ignores arbitrary client JSON and returns original proposal');
    $assembly(1 === $referenceCalls, 'replay makes no worker call');
    foreach ([['content' => 'changed request'], ['screenId' => $screens[1]['id']]] as $changed) {
        $result = WPFastyBuilder\Chat::turn($makeReference($changed));
        $assembly(is_wp_error($result) && 409 === $result->get_error_data()['status'], 'prompt or canonical reference change conflicts with original request ID');
    }
    $unknown = WPFastyBuilder\Chat::turn($makeReference(['requestId' => 'screen-unknown-0001', 'screenId' => 'not-installed:screen']));
    $assembly(is_wp_error($unknown) && 400 === $unknown->get_error_data()['status'], 'uninstalled reference is rejected');
    $oversize = WPFastyBuilder\Chat::turn($makeReference(['requestId' => 'screen-oversize-0001', 'content' => str_repeat('x', 7900)]));
    $assembly(is_wp_error($oversize) && 'wpfb_screen_reference_size' === $oversize->get_error_code(), 'bounded reference plus prompt rejects overflow before worker');
    $assembly(1 === $referenceCalls, 'all invalid/replayed references make zero additional calls');
    $assembly($before === WPFastyBuilder\Store::get($referencePage, true) && !WPFastyBuilder\Store::has($referencePage) && 'draft' === get_post_status($referencePage), 'selection and proposal never insert, apply or publish');
} finally {
    remove_filter('pre_http_request', $countReferenceCalls, 10);
    remove_filter('pre_option_'.WPFastyBuilder\Chat::OPTION, $settings);
    wp_delete_post($referencePage, true);
}
