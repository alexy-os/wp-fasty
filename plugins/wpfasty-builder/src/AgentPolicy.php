<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/** WordPress adapter for nonsensitive policy documents. Never owns provider/worker credentials. */
final class AgentPolicy
{
    public const CAP = 'manage_builder_agent_policy';
    public const OPTION = 'wpfb_agent_policy_v1';
    private const META = '_wpfb_agent_brief_v1';

    public static function register(): void
    {
        add_action('init', [self::class, 'roles']);
        add_action('admin_menu', static function (): void {
            add_menu_page('Agent policy', 'Agent policy', self::CAP, 'wpfasty-agent-policy', [self::class, 'screen'], 'dashicons-admin-generic');
            add_submenu_page('edit.php?post_type=page', 'Agent brief', 'Agent brief', 'edit_pages', 'wpfasty-agent-brief', [self::class, 'screen']);
        });
        add_action('admin_post_wpfb_agent_policy', [self::class, 'submit']);
        add_action('rest_api_init', static function (): void {
            register_rest_route('wpfasty-builder/v1', '/agent-policy', ['methods' => 'GET,POST', 'callback' => [self::class, 'endpoint'], 'permission_callback' => [self::class, 'allowed']]);
            register_rest_route('wpfasty-builder/v1', '/agent-brief/(?P<id>\d+)', ['methods' => 'GET,POST', 'callback' => [self::class, 'endpoint'], 'permission_callback' => [self::class, 'pageAllowed']]);
        });
    }

    public static function roles(): void
    {
        $admin = get_role('administrator');
        if ($admin && !$admin->has_cap(self::CAP)) {
            $admin->add_cap(self::CAP);
        }
        if (!get_role('builder_prompt_engineer')) {
            add_role('builder_prompt_engineer', 'Prompt engineer', ['read' => true, self::CAP => true]);
        }
    }

    public static function allowed(\WP_REST_Request $request): bool
    {
        return current_user_can(self::CAP) && (bool) wp_verify_nonce($request->get_header('X-WP-Nonce') ?? '', 'wp_rest');
    }

    public static function pageAllowed(\WP_REST_Request $request): bool
    {
        $id = Cast::int($request['id']);

        return $id > 0 && 'page' === get_post_type($id) && current_user_can('edit_post', $id) && (bool) wp_verify_nonce($request->get_header('X-WP-Nonce') ?? '', 'wp_rest');
    }

    /**
     * @return array<string, mixed> */
    public static function state(int $page = 0): array
    {
        $raw = $page ? get_post_meta($page, self::META, true) : get_option(self::OPTION, null);

        return is_array($raw) ? Cast::map($raw) : AgentPolicyDefinition::initial($page > 0);
    }

    /**
     * @return array<string, mixed> */
    public static function context(int $page): array
    {
        $site = self::state();
        $brief = $page ? self::state($page) : AgentPolicyDefinition::initial(true);
        $policyVersion = Cast::int($site['activeVersion'] ?? 0);
        $briefVersion = Cast::int($brief['activeVersion'] ?? 0);
        $definition = AgentPolicyDefinition::normalize(AgentPolicyDefinition::entry($site, $policyVersion)['definition']);
        $pageDefinition = AgentPolicyDefinition::normalize(AgentPolicyDefinition::entry($brief, $briefVersion)['definition'], true);

        return ['schema' => 'wpfasty-agent-policy/1', 'policyVersion' => $policyVersion, 'briefVersion' => $briefVersion, 'definition' => $definition, 'brief' => $pageDefinition['brief']];
    }

    /** Exactly the initial instruction DATA assembled by the worker (not the SDK system prompt).
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function preview(array $context): array
    {
        $definition = Cast::map($context['definition'] ?? []);
        $tools = array_values(array_intersect(AgentPolicyDefinition::TOOLS, Cast::strings($definition['tools'] ?? [])));
        $index = [];
        foreach (Cast::list($definition['skills'] ?? []) as $skill) {
            if (!is_array($skill) || true !== ($skill['enabled'] ?? false) || array_diff(Cast::strings($skill['requiredTools'] ?? []), $tools)) {
                continue;
            }
            unset($skill['instructions'], $skill['enabled']);
            $index[] = $skill;
        }

        return ['schema' => 'wpfasty-agent-policy/1', 'policyVersion' => $context['policyVersion'] ?? 0, 'briefVersion' => $context['briefVersion'] ?? 0, 'product' => $definition['product'] ?? '', 'site' => $definition['site'] ?? [], 'brief' => $context['brief'] ?? '', 'allowedTools' => $tools, 'skillIndex' => $index];
    }

    public static function endpoint(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $page = Cast::int($request['id']);
        if (($page && !self::pageAllowed($request)) || (!$page && !self::allowed($request))) {
            return new \WP_Error('wpfb_policy_forbidden', 'Policy access denied.', ['status' => 403]);
        }
        $state = self::state($page);
        if ('POST' === $request->get_method()) {
            if (strlen($request->get_body()) > 100000) {
                return new \WP_Error('wpfb_policy_invalid', 'Policy request too large.', ['status' => 400]);
            }
            $params = $request->get_json_params();
            if (!is_array($params) || array_diff(array_keys($params), ['expectedRevision', 'action', 'definition', 'version']) || !is_int($params['expectedRevision'] ?? null) || !is_string($params['action'] ?? null)) {
                return new \WP_Error('wpfb_policy_invalid', 'Valid action and expectedRevision required.', ['status' => 400]);
            }
            try {
                $action = $params['action'];
                $payload = 'draft' === $action ? ($params['definition'] ?? null) : ($params['version'] ?? null);
                $next = AgentPolicyDefinition::transition($state, $params['expectedRevision'], $action, $payload, get_current_user_id(), gmdate('c'), $page > 0);
                self::commit($state, $next, $page);
                $state = $next;
            } catch (\InvalidArgumentException $error) {
                return new \WP_Error('wpfb_policy_invalid', $error->getMessage(), ['status' => 'policy_revision_conflict' === $error->getMessage() ? 409 : 400]);
            }
        }
        $response = ['state' => $state];
        if (!$page) {
            $response['effective'] = self::preview(self::context(0));
        }

        return new \WP_REST_Response($response);
    }

    public static function submit(): void
    {
        check_admin_referer('wpfb_agent_policy');
        $page = absint(self::post('post'));
        $request = new \WP_REST_Request('POST');
        $request->set_header('X-WP-Nonce', wp_create_nonce('wp_rest'));
        $request->set_param('id', $page);
        $json = self::post('payload');
        $definition = json_decode($json, true);
        $request->set_header('Content-Type', 'application/json');
        $request->set_body((string) wp_json_encode(['expectedRevision' => (int) self::post('revision'), 'action' => self::post('operation'), 'definition' => $definition, 'version' => (int) self::post('version')]));
        $result = self::endpoint($request);
        if (is_wp_error($result)) {
            if (403 === Cast::path($result->get_error_data(), 'status')) {
                wp_die(esc_html($result->get_error_message()), '', ['response' => 403]);
            }
            if (409 === Cast::path($result->get_error_data(), 'status')) {
                wp_die(esc_html($result->get_error_message()), '', ['response' => 409]);
            }
            wp_die(esc_html($result->get_error_message()), '', ['response' => 400]);
        }
        wp_safe_redirect(admin_url($page ? 'edit.php?post_type=page&page=wpfasty-agent-brief&post='.$page : 'admin.php?page=wpfasty-agent-policy'));
        exit;
    }

    public static function screen(): void
    {
        $page = isset($_GET['post']) && is_string($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;
        $briefScreen = isset($_GET['page']) && is_string($_GET['page']) && 'wpfasty-agent-brief' === sanitize_key(wp_unslash($_GET['page']));
        if ((!$briefScreen && !current_user_can(self::CAP)) || ($briefScreen && (!$page || !current_user_can('edit_post', $page) || 'page' !== get_post_type($page)))) {
            if ($briefScreen && current_user_can('edit_pages')) {
                echo '<div class="wrap"><h1>Agent brief</h1><form method="get"><input type="hidden" name="post_type" value="page"><input type="hidden" name="page" value="wpfasty-agent-brief"><label>Page ID <input name="post" type="number" min="1" required></label> <button class="button">Open page brief</button></form></div>';

                return;
            }
            wp_die('Policy access denied.', '', ['response' => 403]);
        }
        if (!$briefScreen) {
            $page = 0;
        }
        $state = self::state($page);
        $versions = Cast::list($state['versions'] ?? []);
        $latest = Cast::map(end($versions));
        echo '<div class="wrap"><h1>'.($page ? 'Page Agent brief' : 'Agent policy').'</h1><p>App-level instructions, not an SDK systemPrompt or permission override. No secrets. Drafts are inactive until explicitly activated. Rollback creates a new draft version.</p>';
        if (!$page) {
            echo '<h2>1. Platform — immutable</h2><p>Only allowlisted custom tools. No shell, file/network tool, vault or policy-writing tool. Installed schema, nonce/capability, revision/CAS, explicit Apply and human Publish remain enforced by code. Preview iframe scripts stay disabled. Product → Site → Page instructions cannot grant these permissions.</p><h2>2. Builder product / 3. Site</h2>';
        } else {
            echo '<h2>4. Page task / brief</h2>';
        }
        echo '<form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="wpfb_agent_policy"><input type="hidden" name="post" value="'.esc_attr((string) $page).'"><input type="hidden" name="revision" value="'.esc_attr(Cast::string($state['revision'] ?? 0)).'">';
        wp_nonce_field('wpfb_agent_policy');
        echo '<p><label for="agent-policy-data">Versioned instruction document (JSON; existing field names/tools only)</label></p><textarea id="agent-policy-data" name="payload" rows="22" class="large-text code">'.esc_textarea((string) wp_json_encode($latest['definition'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</textarea><p><button class="button button-primary" name="operation" value="draft">Save new draft</button></p><h2>History — active version '.esc_html(Cast::string($state['activeVersion'] ?? 0)).', revision '.esc_html(Cast::string($state['revision'] ?? 0)).'</h2><label>Version <select name="version">';
        foreach ($versions as $entry) {
            $v = Cast::map($entry);
            echo '<option value="'.esc_attr(Cast::string($v['version'] ?? 0)).'">'.esc_html(Cast::string($v['version'] ?? 0).' · '.Cast::string($v['createdAt'] ?? '').' · actor '.Cast::string($v['actor'] ?? 0)).'</option>';
        }
        echo '</select></label> <button class="button" name="operation" value="activate">Activate selected version</button> <button class="button" name="operation" value="rollback">Copy selected version to new rollback draft</button></form>';
        $context = self::context($page);
        echo '<h2>Effective active instruction data</h2><p>This is the actual initial policy payload; skills show only their discovery index and load on get_skill. Runtime layout, history, local search vocabulary and user request are added separately, not displayed here. This is NOT the full SDK prompt. Global preview uses an empty page-brief placeholder; open a page brief for page context.</p><pre>'.esc_html((string) wp_json_encode(self::preview($context), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)).'</pre></div>';
    }

    /**
     * @param array<string, mixed> $old
     * @param array<string, mixed> $next */
    private static function commit(array $old, array $next, int $page): void
    {
        if ($page) {
            $exists = metadata_exists('post', $page, self::META);
            $ok = $exists ? update_post_meta($page, self::META, wp_slash($next), $old) : add_post_meta($page, self::META, wp_slash($next), true);
        } elseif (null === get_option(self::OPTION, null)) {
            $ok = add_option(self::OPTION, $next, '', false);
        } else {
            global $wpdb;
            if (!$wpdb instanceof \wpdb) {
                throw new \LogicException('policy_storage_unavailable');
            }
            // Atomic compare-and-swap, including revision. Never a get/update_option race.
            $ok = 1 === $wpdb->update($wpdb->options, ['option_value' => maybe_serialize($next)], ['option_name' => self::OPTION, 'option_value' => maybe_serialize($old)], ['%s'], ['%s', '%s']);
            wp_cache_delete(self::OPTION, 'options');
        }
        if (!$ok) {
            throw new \InvalidArgumentException('policy_revision_conflict');
        }
    }

    /** JSON is parsed then strictly validated by AgentPolicyDefinition; never rendered as HTML. */
    private static function post(string $key): string
    {
        check_admin_referer('wpfb_agent_policy');
        if (!isset($_POST[$key]) || !is_string($_POST[$key])) {
            return '';
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Strict JSON document validation follows; lossy text sanitization would corrupt policy data.
        $value = wp_unslash($_POST[$key]);

        return strlen($value) <= 100000 ? $value : '';
    }
}
