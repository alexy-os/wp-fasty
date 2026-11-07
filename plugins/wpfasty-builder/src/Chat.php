<?php

declare(strict_types=1);
namespace WPFastyBuilder;

/** Same-origin WP boundary. No worker/provider credentials in bootstrap or REST responses. */
final class Chat
{
    public const OPTION = 'wpfb_chat_settings';
    public const SLUG = 'wpfasty-builder-chat';
    public const DEFAULT_PORT = 18991;

    /** Worker port from settings: integer 1024-65535, host is always fixed loopback 127.0.0.1. */
    public static function port(): int
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Host parameter, not form input; validPort strictly validates digits/range without transforming it.
        $host = self::validPort($_SERVER['WPFB_WORKER_PORT'] ?? null);
        if (null !== $host) {
            return $host;
        }
        $settings = (array) get_option(self::OPTION, []);
        $port = self::validPort($settings['worker_port'] ?? null);

        return $port ?? self::DEFAULT_PORT;
    }

    public static function validPort(mixed $value): ?int
    {
        if (is_int($value)) {
            $value = (string) $value;
        }
        if (!is_string($value) || !preg_match('/^[1-9][0-9]{3,4}$/', $value)) {
            return null;
        }
        $port = (int) $value;

        return ($port >= 1024 && $port <= 65535) ? $port : null;
    }

    /** The host's worker holds the Cursor key itself (local-wp vault); WP must not send or store one. */
    public static function hostManagedKey(): bool
    {
        return 'managed' === ($_SERVER['WPFB_WORKER_KEY'] ?? ''); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Host flag: exact fixed-literal comparison; never forwarded raw.
    }

    /**
     * Worker credentials for one request. With the host marker (WPFB_WORKER_KEY=managed) the host is the
     * only source: an absent host token fails closed (vault locked) and the WP database is never consulted.
     * Legacy/manual settings are used only without the marker.
     *
     * @return array{token: string, cursor: ?string}|array{error: string, message: string}
     */
    public static function credentials(array $settings, bool $managed, ?string $hostToken, bool $withKey): array
    {
        if ($managed) {
            if (null === $hostToken) {
                return ['error' => 'secret_unavailable', 'message' => __('Vault locked / secret unavailable: the host did not supply the Builder worker token. Unlock local-wp and retry. No model request was sent.', 'wpfasty-builder')];
            }

            return ['token' => $hostToken, 'cursor' => null];
        }
        $token = $hostToken ?? (is_string($settings['worker_token'] ?? null) ? $settings['worker_token'] : '');
        $cursor = is_string($settings['cursor_key'] ?? null) && '' !== $settings['cursor_key'] ? $settings['cursor_key'] : null;
        if (strlen($token) < 32 || ($withKey && null === $cursor)) {
            return ['error' => 'chat_unconfigured', 'message' => __('Configure Builder chat in Settings → Builder chat. No model request was sent.', 'wpfasty-builder')];
        }

        return ['token' => $token, 'cursor' => $withKey ? $cursor : null];
    }

    /**
     * Applies the settings form. Managed by host: Cursor key, worker token and port are never accepted or
     * saved (only Clear of an old stored copy is honoured).
     */
    public static function mergeSettings(array $settings, mixed $post, bool $managed): array
    {
        $post = is_array($post) ? $post : [];
        foreach (['cursor_key', 'jev_key', 'worker_token'] as $field) {
            if (isset($post['clear_'.$field])) {
                unset($settings[$field]);
                continue;
            }
            if ($managed && 'jev_key' !== $field) {
                continue;
            }
            $value = $post[$field] ?? '';
            $value = is_string($value) ? trim($value) : '';
            if ('' !== $value && strlen($value) <= 4096) {
                $settings[$field] = $value;
            }
        }
        if (!$managed) {
            $port = $post['worker_port'] ?? '';
            $port = is_string($port) ? trim($port) : 'invalid';
            if ('' !== $port) {
                $valid = self::validPort($port);
                if (null === $valid) {
                    $settings['_invalid_port'] = true;
                } else {
                    $settings['worker_port'] = $valid;
                }
            }
        }

        return $settings;
    }

    /** Credential rows of the settings form. Managed by host: availability only, no inputs, no values. */
    public static function credentialsHtml(array $settings, bool $managed, bool $hostTokenPresent): string
    {
        $labels = ['cursor_key' => __('Cursor API key', 'wpfasty-builder'), 'jev_key' => __('Jev key (reserved; search unavailable)', 'wpfasty-builder'), 'worker_token' => __('Local worker shared token (at least 32 characters)', 'wpfasty-builder')];
        $clear = static fn (string $field): string => ' <label><input type="checkbox" name="clear_'.esc_attr($field).'"> '.esc_html__('Clear', 'wpfasty-builder').'</label>';
        $html = '';
        if ($managed) {
            $state = $hostTokenPresent ? __('available', 'wpfasty-builder') : __('unavailable (vault locked)', 'wpfasty-builder');
            $html .= '<div class="notice notice-info inline"><p>'.esc_html__('Managed by host (local-wp vault): the Cursor key, worker token and port are supplied per request and are never stored in this database.', 'wpfasty-builder').'</p></div>';
            foreach (['cursor_key', 'worker_token'] as $field) {
                $html .= '<p>'.esc_html($labels[$field]).': '.esc_html__('Managed by host', 'wpfasty-builder').' — '.esc_html($state)
                    .(empty($settings[$field]) ? '' : ' ('.esc_html__('an old stored copy exists', 'wpfasty-builder').')'.$clear($field)).'</p>';
            }
            $html .= '<p>'.esc_html__('Local worker port', 'wpfasty-builder').': '.esc_html__('Managed by host', 'wpfasty-builder').'</p>';
            $fields = ['jev_key'];
        } else {
            $fields = ['cursor_key', 'jev_key', 'worker_token'];
        }
        foreach ($fields as $field) {
            $html .= '<p><label>'.esc_html($labels[$field]).'<br><input type="password" autocomplete="new-password" name="'.esc_attr($field).'" value="" class="regular-text"></label> '
                .esc_html(empty($settings[$field]) ? __('Not set', 'wpfasty-builder') : __('Configured (masked)', 'wpfasty-builder')).$clear($field).'</p>';
        }
        if (!$managed) {
            $html .= '<p><label>'.esc_html__('Local worker port (1024-65535, host fixed 127.0.0.1)', 'wpfasty-builder').'<br><input type="number" min="1024" max="65535" step="1" name="worker_port" value="'.esc_attr((string) self::port()).'" class="small-text"></label></p>';
        }

        return $html;
    }

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'routes']);
        add_action('admin_menu', static function (): void {
            add_options_page('Builder chat', 'Builder chat', 'manage_options', self::SLUG, [self::class, 'settings']);
        });
    }

    public static function settings(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient capabilities.');
        }
        $settings = (array) get_option(self::OPTION, []);
        if ('POST' === sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'] ?? '')) && check_admin_referer('wpfb_chat_settings')) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Opaque credentials are not text-sanitized; mergeSettings enforces scalar type, the 4096-byte bound and the managed-host rule.
            $settings = self::mergeSettings($settings, wp_unslash($_POST), self::hostManagedKey());
            if (isset($settings['_invalid_port'])) {
                unset($settings['_invalid_port']);
                echo '<div class="notice notice-error"><p>'.esc_html__('Worker port must be an integer 1024-65535. Port unchanged.', 'wpfasty-builder').'</p></div>';
            }
            update_option(self::OPTION, $settings, false);
            echo '<div class="notice notice-success"><p>Settings saved server-side. Blank fields preserve existing values.</p></div>';
        }
        echo '<div class="wrap"><h1>Builder chat</h1><p>Node 24 worker on this WordPress host: '.esc_html(self::workerUrl()).'. Provider keys never enter the editor bundle. Grok is selected only if available to the Cursor key.</p>';
        echo '<p>Jev search is unavailable: the installed official extension documents typed decisions, not a search API. Its optional key is reserved; no Jev requests are made.</p><form method="post">';
        wp_nonce_field('wpfb_chat_settings');
        echo self::credentialsHtml($settings, self::hostManagedKey(), self::hostToken() !== null); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- credentialsHtml escapes every dynamic value.
        submit_button('Save');
        echo '</form></div>';
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

    public static function history(\WP_REST_Request $request): mixed
    {
        $result = self::worker('/history', self::scope((int) $request['id']));
        if (!is_wp_error($result)) {
            $settings = (array) get_option(self::OPTION, []);
            $configured = self::hostManagedKey() ? null !== self::hostToken() : !empty($settings['cursor_key']);
            $unavailable = empty($result['turns']) ? 'Sending is unavailable until a Cursor API key is configured server-side in Settings → Builder chat.' : 'Sending is unavailable without a configured Cursor API key. Existing conversation history remains available. Configure credentials server-side in Settings → Builder chat.';
            $result['availability'] = ['configured' => $configured, 'model' => 'grok-4.7', 'message' => $configured ? 'Grok configured; provider access is checked only when you send.' : $unavailable];
            $currentVersion = DraftGuard::version((int) $request['id']);
            // Recover completed worker proposals even if the original PHP request timed out.
            foreach (($result['turns'] ?? []) as $index => $turn) {
                if ('done' !== ($turn['state'] ?? '') || !is_array($turn['proposal'] ?? null) || !is_string($turn['source'] ?? null) || !preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $turn['requestId'] ?? '')) {
                    continue;
                }
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
        if (strlen($request->get_body()) > 12000) {
            return new \WP_Error('wpfb_chat_too_large', 'Message too large.', ['status' => 413]);
        }
        $page = (int) $request['id'];
        $body = (array) $request->get_json_params();
        $id = $body['requestId'] ?? '';
        $content = $body['content'] ?? '';
        if (!is_string($id) || !is_string($content) || !preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $id) || '' === trim($content) || strlen($content) > 8000) {
            return new \WP_Error('wpfb_chat_invalid', 'Valid requestId and message required.', ['status' => 400]);
        }
        $cache = json_decode((string) get_post_meta($page, '_wpfb_turn_'.$id, true), true);
        if (is_array($cache)) {
            if (($cache['hash'] ?? '') !== hash('sha256', $content)) {
                return new \WP_Error('wpfb_idempotency_conflict', 'Request id reused with different content.', ['status' => 409]);
            }

            return new \WP_REST_Response($cache['result']);
        }
        $entry = Store::get($page, true) ?? [];
        $source = (string) ($entry['metadata']['source']['value'] ?? 'ui8kit');
        $catalog = Sources::catalog($source);
        if (null === $catalog) {
            return new \WP_Error('wpfb_source', 'Unknown catalog source.', ['status' => 400]);
        }
        $defs = [];
        foreach (($catalog->forEditor(null)['blocks'] ?? []) as $block) {
            $defs[] = ['id' => $block['id'], 'props' => $block['props'] ?? [], 'defaults' => $block['defaults'] ?? [], 'data' => $block['data'] ?? [], 'variants' => array_map(static fn (array $variant): array => ['id' => $variant['id'], 'template' => $variant['template'], 'set' => $variant['set'] ?? []], $block['variants'] ?? [])];
        }
        if ([] === $defs) {
            return new \WP_Error('wpfb_catalog_unavailable', 'Catalog definition unavailable.', ['status' => 503]);
        }
        $context = ['source' => $source, 'version' => (int) ($entry['version'] ?? 0), 'records' => $catalog->meta()['records'] ?? [], 'catalog' => $defs, 'blocks' => $entry['metadata']['blocks']['value'] ?? []];
        $result = self::worker('/turn', self::scope($page) + ['requestId' => $id, 'content' => $content, 'context' => $context], true);
        if (is_wp_error($result)) {
            return $result;
        }
        if (isset($result['proposal']) && is_array($result['proposal'])) {
            add_post_meta($page, '_wpfb_chat_'.$id, wp_slash(wp_json_encode(['source' => $source, 'blocks' => $result['proposal'], 'version' => $result['baseVersion'] ?? -1])), true);
        }
        add_post_meta($page, '_wpfb_turn_'.$id, wp_slash(wp_json_encode(['hash' => hash('sha256', $content), 'result' => $result])), true);

        return new \WP_REST_Response($result);
    }

    public static function apply(\WP_REST_Request $request): mixed
    {
        if (strlen($request->get_body()) > 12000) {
            return new \WP_Error('wpfb_chat_too_large', 'Request too large.', ['status' => 413]);
        }
        $page = (int) $request['id'];
        $body = (array) $request->get_json_params();
        $id = (string) ($body['requestId'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_-]{8,100}$/', $id)) {
            return new \WP_Error('wpfb_chat_invalid', 'Invalid requestId.', ['status' => 400]);
        }

        return DraftGuard::locked($page, static function () use ($page, $id) {
            $key = '_wpfb_chat_'.$id;
            $proposal = json_decode((string) get_post_meta($page, $key, true), true);
            if (!is_array($proposal)) {
                return new \WP_Error('wpfb_chat_missing', 'No saved proposal for this page.', ['status' => 404]);
            }
            if (isset($proposal['applied'])) {
                return new \WP_REST_Response(['applied' => true, 'version' => $proposal['applied'], 'currentVersion' => DraftGuard::version($page), 'replay' => true]);
            }
            if (($proposal['version'] ?? -1) !== DraftGuard::version($page)) {
                return new \WP_Error('wpfb_revision_conflict', 'Draft changed. Request a new proposal.', ['status' => 409]);
            }
            $catalog = Sources::catalog($proposal['source']);
            if (null === $catalog || !is_array($proposal['blocks']) || count($proposal['blocks']) > 100) {
                return new \WP_Error('wpfb_chat_schema', 'Invalid proposal.', ['status' => 400]);
            }
            $ids = [];
            foreach ($proposal['blocks'] as $block) {
                if (!is_array($block) || !is_string($block['id'] ?? null) || !preg_match(Catalog::IDENT, $block['id']) || isset($ids[$block['id']]) || !is_array($block['props'] ?? null) || !is_array($block['bindings'] ?? null)) {
                    return new \WP_Error('wpfb_chat_schema', 'Invalid structural block.', ['status' => 400]);
                }
                $ids[$block['id']] = true;
                if (null === $catalog->variant((string) ($block['block'] ?? ''), (string) ($block['variant'] ?? ''))) {
                    return new \WP_Error('wpfb_chat_invalid_catalog', 'Proposal references unknown catalog block/variant.', ['status' => 400]);
                }
            }
            $previous = Store::get($page, true) ?? [];
            $layout = ['source' => $proposal['source'], 'blocks' => $proposal['blocks'], 'collection' => $previous['metadata']['collection']['value'] ?? ''];
            $post = get_post($page);
            $base = $previous + ['id' => 'layout-'.$page, 'locale' => 'en', 'slug' => $post->post_name ?: 'page-'.$page, 'title' => $post->post_title, 'status' => 'draft', 'version' => 1, 'created_at' => gmdate('c'), 'updated_at' => gmdate('c')];
            $base['page_id'] = $page;
            [, $issues] = Layout::normalize($layout, $proposal['source'], $catalog, array_keys(DataSources::all()), $base);
            if ([] !== $issues) {
                return new \WP_Error('wpfb_chat_schema', 'Proposal failed Builder schema validation.', ['status' => 400, 'issues' => $issues]);
            }
            $saved = Store::save($page, $layout, false);
            if (is_wp_error($saved)) {
                return $saved;
            }
            $proposal['applied'] = (int) $saved[0]['version'];
            update_post_meta($page, $key, wp_slash(wp_json_encode($proposal)));

            return new \WP_REST_Response(['applied' => true, 'version' => $proposal['applied']]);
        });
    }

    /**
     * Host-supplied worker token (e.g. a FastCGI param set per request by local-wp from its age vault).
     * Never stored in the DB. Falls back to the legacy wpfb_chat_settings.worker_token option.
     */
    private static function hostToken(): ?string
    {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Host-injected opaque token; preserve exact bytes, with string/length validation below.
        $value = $_SERVER['WPFB_WORKER_TOKEN'] ?? null;

        return (is_string($value) && strlen($value) >= 32 && strlen($value) <= 4096) ? $value : null;
    }

    private static function workerUrl(): string
    {
        return 'http://127.0.0.1:'.self::port();
    }

    private static function scope(int $page): array
    {
        $site = (string) get_option('wpfb_chat_site_id', '');
        if ('' === $site) {
            add_option('wpfb_chat_site_id', wp_generate_uuid4(), '', false);
            $site = (string) get_option('wpfb_chat_site_id');
        }

        return ['site' => $site, 'page' => $page];
    }

    private static function worker(string $route, array $body, bool $withKey = false): mixed
    {
        $auth = self::credentials((array) get_option(self::OPTION, []), self::hostManagedKey(), self::hostToken(), $withKey);
        if (isset($auth['error'])) {
            return new \WP_Error('wpfb_'.$auth['error'], $auth['message'], ['status' => 503]);
        }
        $token = $auth['token'];
        if (null !== $auth['cursor']) {
            $body['credentials'] = ['cursor' => $auth['cursor']];
        }
        $result = wp_remote_post(self::workerUrl().$route, ['timeout' => 100, 'redirection' => 0,
            'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer '.$token], 'body' => wp_json_encode($body), 'limit_response_size' => 512000]);
        if (is_wp_error($result)) {
            return new \WP_Error('wpfb_worker_offline', 'Local Builder worker is unreachable. Start Node worker on this WordPress host; no automatic retry.', ['status' => 503]);
        }
        $data = json_decode((string) wp_remote_retrieve_body($result), true);
        $status = (int) wp_remote_retrieve_response_code($result);
        if (200 !== $status || !is_array($data)) {
            $safe = ['configured_grok_model_unavailable', 'worker_unauthorized', 'worker_busy', 'idempotency_conflict', 'turn_not_replayable_check_history', 'page_busy_or_interrupted', 'agent_failed_no_automatic_retry', 'invalid_turn'];
            $code = in_array($data['error'] ?? '', $safe, true) ? $data['error'] : 'worker_failure';

            return new \WP_Error('wpfb_'.$code, $code, ['status' => in_array($status, [400, 401, 409, 429, 503], true) ? $status : 502]);
        }

        return $data;
    }
}
