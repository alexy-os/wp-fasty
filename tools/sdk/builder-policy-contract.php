<?php

/** NON-LIVE on OUR isolated WP only. Disposable users/pages; restore nonsensitive policy option. */
use WPFastyBuilder\AgentPolicy as Policy;
use WPFastyBuilder\AgentPolicyDefinition as Definition;

function policyCheck(bool $ok, string $label): void
{
    if (!$ok) {
        throw new RuntimeException(sprintf('NON-LIVE FAIL %s', $label));
    }
    echo 'PASS '.$label."\n";
}
$original = get_option(Policy::OPTION, null);
$originalUser = get_current_user_id();
$oldGet = $_GET;
$users = [];
$pages = [];
add_role('wpfb_policy_fixture_editor', 'NON-LIVE own pages', ['read' => true, 'edit_pages' => true]);
$request = static function (string $method = 'GET', int $page = 0, array $body = []): WP_REST_Request {
    $r = new WP_REST_Request($method);
    $r->set_url_params(['id' => $page]);
    $r->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
    $r->set_header('Content-Type', 'application/json');
    $r->set_body(wp_json_encode($body));

    return $r;
};
try {
    foreach (['builder_prompt_engineer', 'wpfb_policy_fixture_editor'] as $role) {
        $id = wp_insert_user(['user_login' => 'nonlive-policy-'.wp_generate_password(10, false), 'user_pass' => wp_generate_password(40), 'role' => $role]);
        if (is_wp_error($id)) {
            throw new RuntimeException('Cannot create disposable policy actor');
        }
        $users[] = $id;
    }
    foreach ($users as $id) {
        $pages[] = wp_insert_post(['post_type' => 'page', 'post_status' => 'draft', 'post_author' => $id, 'post_title' => 'NON-LIVE policy isolation']);
    }
    delete_option(Policy::OPTION);
    wp_set_current_user($users[0]);
    policyCheck(current_user_can(Policy::CAP) && !current_user_can('manage_options') && !current_user_can('publish_pages') && !current_user_can('edit_pages'), 'Prompt engineer has only read + policy rights, no settings/publish/page grants');
    policyCheck(Policy::allowed($request()), 'policy manager + nonce permitted');
    $bad = $request();
    $bad->set_header('X-WP-Nonce', 'bad');
    policyCheck(!Policy::allowed($bad) && is_wp_error(Policy::endpoint($bad)), 'bad nonce denied even inside adapter');
    wp_set_current_user(0);
    policyCheck(is_wp_error(Policy::endpoint($request())), 'anonymous policy access denied');
    wp_set_current_user($users[1]);
    policyCheck(!Policy::allowed($request()) && is_wp_error(Policy::endpoint($request('POST', 0, ['expectedRevision' => 0, 'action' => 'draft', 'definition' => Definition::defaults()]))), 'page editor cannot read/write global policy endpoint');
    policyCheck(Policy::pageAllowed($request('GET', $pages[1])) && !Policy::pageAllowed($request('GET', $pages[0])), 'page brief rights limited to actual editable page');
    wp_set_current_user($users[0]);
    $definition = Definition::defaults();
    $definition['site']['brand'] = '</textarea><script>NONLIVE_POLICY_XSS</script>';
    $draft = Policy::endpoint($request('POST', 0, ['expectedRevision' => 0, 'action' => 'draft', 'definition' => $definition]));
    policyCheck($draft instanceof WP_REST_Response && 1 === Policy::state()['activeVersion'], 'saved global draft is not active');
    $stale = Policy::endpoint($request('POST', 0, ['expectedRevision' => 0, 'action' => 'activate', 'version' => 2]));
    policyCheck(is_wp_error($stale) && 409 === $stale->get_error_data()['status'], 'stale revision rejected409');
    $active = Policy::endpoint($request('POST', 0, ['expectedRevision' => 1, 'action' => 'activate', 'version' => 2]));
    policyCheck($active instanceof WP_REST_Response && 2 === Policy::context($pages[1])['policyVersion'], 'explicit activation locks version in context');
    $_GET = ['page' => 'wpfasty-agent-policy'];
    ob_start();
    Policy::screen();
    $html = ob_get_clean();
    policyCheck(!str_contains($html, '<script>NONLIVE_POLICY_XSS') && str_contains($html, '&lt;script&gt;NONLIVE_POLICY_XSS'), 'stored policy XSS escaped in editor and effective preview');
    $before = Policy::state();
    $next = Definition::transition($before, 2, 'draft', Definition::defaults(), $users[0], 'fixture');
    update_option(Policy::OPTION, array_replace($before, ['revision' => 3]), false);
    $commit = new ReflectionMethod(Policy::class, 'commit');
    try {
        $commit->invoke(null, $before, $next, 0);
        throw new RuntimeException('CAS should fail');
    } catch (InvalidArgumentException $error) {
        policyCheck('policy_revision_conflict' === $error->getMessage(), 'storage-level CAS rejects intervening write');
    }
    wp_set_current_user($users[1]);
    $briefText = 'Only fixture page A \\path\\ "quoted"';
    $brief = Policy::endpoint($request('POST', $pages[1], ['expectedRevision' => 0, 'action' => 'draft', 'definition' => ['brief' => $briefText]]));
    policyCheck($brief instanceof WP_REST_Response && '' === Policy::context($pages[1])['brief'], 'page brief draft inactive');
    $brief = Policy::endpoint($request('POST', $pages[1], ['expectedRevision' => 1, 'action' => 'activate', 'version' => 2]));
    policyCheck($brief instanceof WP_REST_Response && $briefText === Policy::context($pages[1])['brief'] && '' === Policy::context($pages[0])['brief'], 'active page brief isolated from other pages');
    $invalid = Policy::endpoint($request('POST', $pages[1], ['expectedRevision' => 2, 'action' => 'draft', 'definition' => ['brief' => 'x', 'tools' => ['shell']]]));
    policyCheck(is_wp_error($invalid), 'page instructions cannot escalate tools or write global layers');
    echo "NON-LIVE policy WordPress contracts complete; no model or credential access.\n";
} finally {
    wp_set_current_user($originalUser);
    $_GET = $oldGet;
    if (null === $original) {
        delete_option(Policy::OPTION);
    } else {
        update_option(Policy::OPTION, $original, false);
    }
    foreach ($pages as $page) {
        wp_delete_post($page, true);
    }
    require_once ABSPATH.'wp-admin/includes/user.php';
    foreach ($users as $user) {
        wp_delete_user($user);
    }
    remove_role('wpfb_policy_fixture_editor');
}
