<?php

declare(strict_types=1);
namespace WPFastyBuilder;

/** Same-origin WP boundary. No worker/provider credentials in bootstrap or REST responses. */
final class Chat
{
    public const OPTION = 'wpfb_chat_settings';
    public const SLUG = 'wpfasty-builder-chat';
    private const WORKER = 'http://127.0.0.1:18991';

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
        add_action('admin_menu', static function (): void {
            add_options_page('Builder chat', 'Builder chat', 'manage_options', self::SLUG, [self::class, 'settings']);
        });
    }

    public static function settings(): void
    {
        if (!current_user_can('manage_options')) { wp_die('Insufficient capabilities.'); }
        $settings = (array) get_option(self::OPTION, []);
        if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? '') && check_admin_referer('wpfb_chat_settings')) {
            foreach (['cursor_key', 'jev_key', 'worker_token'] as $field) {
                $value = trim((string) wp_unslash($_POST[$field] ?? ''));
                if ('' !== $value && strlen($value) <= 4096) { $settings[$field] = $value; }
                if (isset($_POST['clear_'.$field])) { unset($settings[$field]); }
            }
            update_option(self::OPTION, $settings, false);
            echo '<div class="notice notice-success"><p>Settings saved server-side. Blank fields preserve existing values.</p></div>';
        }
        echo '<div class="wrap"><h1>Builder chat</h1><p>Node 24 worker on this WordPress host: '.esc_html(self::WORKER).'. Provider keys never enter the editor bundle. Grok is selected only if available to the Cursor key.</p>';
        echo '<p>Jev search is unavailable: the installed official extension documents typed decisions, not a search API. Its optional key is reserved; no Jev requests are made.</p><form method="post">';
        wp_nonce_field('wpfb_chat_settings');
        foreach (['cursor_key' => 'Cursor API key', 'jev_key' => 'Jev key (reserved; search unavailable)', 'worker_token' => 'Local worker shared token (at least 32 characters)'] as $field => $label) {
            echo '<p><label>'.esc_html($label).'<br><input type="password" autocomplete="new-password" name="'.esc_attr($field).'" value="" class="regular-text"></label> '.(empty($settings[$field]) ? 'Not set' : 'Configured (masked)').' <label><input type="checkbox" name="clear_'.esc_attr($field).'"> Clear</label></p>';
        }
        submit_button('Save'); echo '</form></div>';
    }

    public static function allowed(\WP_REST_Request $request): bool
    {
        $page = (int) $request['id'];
        return false !== wp_verify_nonce($request->get_header('X-WP-Nonce'), 'wp_rest')
            && 'page' === get_post_type($page) && current_user_can('edit_page', $page);
    }

    public static function routes(): void
    {
        foreach (['history' => 'GET', 'turn' => 'POST', 'apply' => 'POST'] as $action => $method) {
            register_rest_route(Rest::NS, '/chat/(?P<id>\d+)/'.$action, [
                'methods' => $method, 'callback' => [self::class, $action], 'permission_callback' => [self::class, 'allowed'],
            ]);
        }
    }

    private static function scope(int $page): array
    {
        $site = (string) get_option('wpfb_chat_site_id', '');
        if ('' === $site) { add_option('wpfb_chat_site_id', wp_generate_uuid4(), '', false); $site = (string) get_option('wpfb_chat_site_id'); }
        return ['site' => $site, 'page' => $page];
    }

    private static function worker(string $route, array $body, bool $withKey = false): mixed
    {
        $settings = (array) get_option(self::OPTION, []);
        if (strlen((string) ($settings['worker_token'] ?? '')) < 32 || ($withKey && empty($settings['cursor_key']))) {
            return new \WP_Error('wpfb_chat_unconfigured', 'Configure Builder chat in Settings → Builder chat. No model request was sent.', ['status' => 503]);
        }
        if ($withKey) { $body['credentials'] = ['cursor' => $settings['cursor_key']]; }
        $result = wp_remote_post(self::WORKER.$route, ['timeout' => 100, 'redirection' => 0,
            'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$settings['worker_token']], 'body' => wp_json_encode($body), 'limit_response_size' => 512000]);
        if (is_wp_error($result)) { return new \WP_Error('wpfb_worker_offline', 'Local Builder worker is unreachable. Start Node worker on this WordPress host; no automatic retry.', ['status' => 503]); }
        $data = json_decode((string) wp_remote_retrieve_body($result), true);
        $status = (int) wp_remote_retrieve_response_code($result);
        if (200 !== $status || !is_array($data)) {
            $safe = ['configured_grok_model_unavailable', 'worker_unauthorized', 'worker_busy', 'idempotency_conflict', 'turn_not_replayable_check_history', 'page_busy_or_interrupted', 'agent_failed_no_automatic_retry', 'invalid_turn'];
            $code = in_array($data['error'] ?? '', $safe, true) ? $data['error'] : 'worker_failure';
            return new \WP_Error('wpfb_'.$code, $code, ['status' => in_array($status, [400, 401, 409, 429, 503], true) ? $status : 502]);
        }
        return $data;
    }

    public static function history(\WP_REST_Request $request): mixed
    {
        $result = self::worker('/history', self::scope((int) $request['id']));
        if (!is_wp_error($result)) {
            $settings = (array) get_option(self::OPTION, []);
            $configured = !empty($settings['cursor_key']);
            $unavailable = empty($result['turns']) ? 'Sending is unavailable until a Cursor API key is configured server-side in Settings → Builder chat.' : 'Sending is unavailable without a configured Cursor API key. Existing conversation history remains available. Configure credentials server-side in Settings → Builder chat.';
            $result['availability'] = ['configured' => $configured, 'model' => 'grok-4.7', 'message' => $configured ? 'Grok configured; provider access is checked only when you send.' : $unavailable];
            $currentVersion = DraftGuard::version((int) $request['id']);
            // Recover completed worker proposals even if the original PHP request timed out.
            foreach (($result['turns'] ?? []) as $index => $turn) {
                if ('done' !== ($turn['state'] ?? '') || !is_array($turn['proposal'] ?? null) || !is_string($turn['source'] ?? null) || !preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $turn['requestId'] ?? '')) { continue; }
                $key = '_wpfb_chat_'.$turn['requestId'];
                add_post_meta((int) $request['id'], $key, wp_slash(wp_json_encode(['source' => $turn['source'], 'blocks' => $turn['proposal'], 'version' => $turn['baseVersion'] ?? -1])), true);
                // WP owns draft application; worker history owns generation. Never infer apply state from prose.
                $saved = json_decode((string) get_post_meta((int) $request['id'], $key, true), true);
                $applied = isset($saved['applied']) ? (int) $saved['applied'] : null;
                $base = (int) ($saved['version'] ?? -1);
                $state = null !== $applied ? 'applied' : ($base === $currentVersion ? 'pending' : 'stale');
                $result['turns'][$index]['application'] = ['state' => $state, 'baseVersion' => $base, 'appliedVersion' => $applied, 'currentVersion' => $currentVersion, 'canApply' => 'pending' === $state];
            }
        }
        return is_wp_error($result) ? $result : new \WP_REST_Response($result);
    }

    public static function turn(\WP_REST_Request $request): mixed
    {
        if (strlen($request->get_body()) > 12000) { return new \WP_Error('wpfb_chat_too_large', 'Message too large.', ['status' => 413]); }
        $page = (int) $request['id']; $body = (array) $request->get_json_params();
        $id = $body['requestId'] ?? ''; $content = $body['content'] ?? '';
        if (!is_string($id) || !is_string($content) || !preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $id) || '' === trim($content) || strlen($content) > 8000) { return new \WP_Error('wpfb_chat_invalid', 'Valid requestId and message required.', ['status' => 400]); }
        $cache = json_decode((string) get_post_meta($page, '_wpfb_turn_'.$id, true), true);
        if (is_array($cache)) {
            if (($cache['hash'] ?? '') !== hash('sha256', $content)) { return new \WP_Error('wpfb_idempotency_conflict', 'Request id reused with different content.', ['status' => 409]); }
            return new \WP_REST_Response($cache['result']);
        }
        $entry = Store::get($page, true) ?? [];
        $source = (string) ($entry['metadata']['source']['value'] ?? 'ui8kit');
        $catalog = Sources::catalog($source);
        if (null === $catalog) { return new \WP_Error('wpfb_source', 'Unknown catalog source.', ['status' => 400]); }
        $defs = [];
        foreach (($catalog->forEditor(null)['blocks'] ?? []) as $block) {
            $defs[] = ['id' => $block['id'], 'props' => $block['props'] ?? [], 'defaults' => $block['defaults'] ?? [], 'data' => $block['data'] ?? [], 'variants' => array_map(static fn (array $variant): array => ['id' => $variant['id'], 'template' => $variant['template'], 'set' => $variant['set'] ?? []], $block['variants'] ?? [])];
        }
        if ([] === $defs) { return new \WP_Error('wpfb_catalog_unavailable', 'Catalog definition unavailable.', ['status' => 503]); }
        $context = ['source' => $source, 'version' => (int) ($entry['version'] ?? 0), 'records' => $catalog->meta()['records'] ?? [], 'catalog' => $defs, 'blocks' => $entry['metadata']['blocks']['value'] ?? []];
        $result = self::worker('/turn', self::scope($page) + ['requestId' => $id, 'content' => $content, 'context' => $context], true);
        if (is_wp_error($result)) { return $result; }
        if (isset($result['proposal']) && is_array($result['proposal'])) {
            add_post_meta($page, '_wpfb_chat_'.$id, wp_slash(wp_json_encode(['source' => $source, 'blocks' => $result['proposal'], 'version' => $result['baseVersion'] ?? -1])), true);
        }
        add_post_meta($page, '_wpfb_turn_'.$id, wp_slash(wp_json_encode(['hash' => hash('sha256', $content), 'result' => $result])), true);
        return new \WP_REST_Response($result);
    }

    public static function apply(\WP_REST_Request $request): mixed
    {
        if (strlen($request->get_body()) > 12000) { return new \WP_Error('wpfb_chat_too_large', 'Request too large.', ['status' => 413]); }
        $page = (int) $request['id']; $body = (array) $request->get_json_params(); $id = (string) ($body['requestId'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $id)) { return new \WP_Error('wpfb_chat_invalid', 'Invalid requestId.', ['status' => 400]); }
        return DraftGuard::locked($page, static function () use ($page, $id) {
            $key = '_wpfb_chat_'.$id; $proposal = json_decode((string) get_post_meta($page, $key, true), true);
            if (!is_array($proposal)) { return new \WP_Error('wpfb_chat_missing', 'No saved proposal for this page.', ['status' => 404]); }
            if (isset($proposal['applied'])) { return new \WP_REST_Response(['applied' => true, 'version' => $proposal['applied'], 'currentVersion' => DraftGuard::version($page), 'replay' => true]); }
            if (($proposal['version'] ?? -1) !== DraftGuard::version($page)) { return new \WP_Error('wpfb_revision_conflict', 'Draft changed. Request a new proposal.', ['status' => 409]); }
            $catalog = Sources::catalog($proposal['source']);
            if (null === $catalog || !is_array($proposal['blocks']) || count($proposal['blocks']) > 100) { return new \WP_Error('wpfb_chat_schema', 'Invalid proposal.', ['status' => 400]); }
            $ids = [];
            foreach ($proposal['blocks'] as $block) {
                if (!is_array($block) || !is_string($block['id'] ?? null) || !preg_match(Catalog::IDENT, $block['id']) || isset($ids[$block['id']]) || !is_array($block['props'] ?? null) || !is_array($block['bindings'] ?? null)) { return new \WP_Error('wpfb_chat_schema', 'Invalid structural block.', ['status' => 400]); }
                $ids[$block['id']] = true;
                if (null === $catalog->variant((string) ($block['block'] ?? ''), (string) ($block['variant'] ?? ''))) { return new \WP_Error('wpfb_chat_invalid_catalog', 'Proposal references unknown catalog block/variant.', ['status' => 400]); }
            }
            $previous = Store::get($page, true) ?? [];
            $layout = ['source' => $proposal['source'], 'blocks' => $proposal['blocks'], 'collection' => $previous['metadata']['collection']['value'] ?? ''];
            $post = get_post($page);
            $base = $previous + ['id' => 'layout-'.$page, 'locale' => 'en', 'slug' => $post->post_name ?: 'page-'.$page, 'title' => $post->post_title, 'status' => 'draft', 'version' => 1, 'created_at' => gmdate('c'), 'updated_at' => gmdate('c')];
            $base['page_id'] = $page;
            [, $issues] = Layout::normalize($layout, $proposal['source'], $catalog, array_keys(DataSources::all()), $base);
            if ([] !== $issues) { return new \WP_Error('wpfb_chat_schema', 'Proposal failed Builder schema validation.', ['status' => 400, 'issues' => $issues]); }
            $saved = Store::save($page, $layout, false);
            if (is_wp_error($saved)) { return $saved; }
            $proposal['applied'] = (int) $saved[0]['version']; update_post_meta($page, $key, wp_slash(wp_json_encode($proposal)));
            return new \WP_REST_Response(['applied' => true, 'version' => $proposal['applied']]);
        });
    }
}
