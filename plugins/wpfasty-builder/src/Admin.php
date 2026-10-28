<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * wp-admin: the builder screen (Pages → Builder, edit_pages) and the sources screen (Settings → Builder sources,
 * manage_options). The editor script and styles are enqueued on the builder screen only.
 * Open a collection with ?collection=<id> to show only its blocks in the sidebar.
 * New pages: "Add New with Builder" next to "Add New" on Pages, "New page" on the Builder screen (page chooser and
 * editor bar) open the "New page" panel of the editor script (?new=1 opens it on load); Pages::create makes the draft.
 * The dark mode switch of the editor is the last item of the toolbar (admin bar), on the editor screen only; the
 * editor script wires it up and remembers the mode in localStorage per user.
 */
final class Admin
{
    /** transient prefix of the Sources screen notice (+ user id) */
    private const NOTICE = 'wpfb_sources_notice_';

    /** JSON inside a <script>: <, >, &, ' and " as \u escapes, so no value (a page title…) can end the script */
    private const JSON = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    public const SLUG = 'wpfasty-builder';
    public const SOURCES = 'wpfasty-builder-sources';
    /** admin bar node of the dark mode switch (li#wp-admin-bar-wpfasty-builder-dark) */
    public const DARK = 'wpfasty-builder-dark';
    /** after every core toolbar item (core top-secondary items run at 9991…9999): the switch is the last node */
    public const DARK_PRIORITY = 100000;
    private static string $hook = '';
    /** the editor is enqueued on this request (a page is open in the builder) */
    private static bool $editor = false;

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
        add_action('admin_enqueue_scripts', [self::class, 'pagesList']);
        add_filter('page_row_actions', [self::class, 'rowAction'], 10, 2);
        add_action('admin_post_wpfb_import', [self::class, 'handleImport']);
        add_action('admin_notices', [self::class, 'problems']);
        add_action('admin_bar_menu', [self::class, 'darkSwitch'], self::DARK_PRIORITY);
    }

    public static function menu(): void
    {
        self::$hook = (string) add_pages_page(
            __('Builder', 'wpfasty-builder'),
            __('Builder', 'wpfasty-builder'),
            'edit_pages',
            self::SLUG,
            [self::class, 'screen']
        );
        add_options_page(
            __('Builder sources', 'wpfasty-builder'),
            __('Builder sources', 'wpfasty-builder'),
            'manage_options',
            self::SOURCES,
            [self::class, 'sourcesScreen']
        );
    }

    /** the Builder screen with the "New page" panel open */
    public static function newUrl(): string
    {
        return add_query_arg(['page' => self::SLUG, 'new' => 1], admin_url('edit.php?post_type=page'));
    }

    public static function url(int $pageId, string $collection = ''): string
    {
        $args = ['page' => self::SLUG, 'post' => $pageId];
        if ($collection !== '') {
            $args['collection'] = $collection;
        }
        return add_query_arg($args, admin_url('edit.php?post_type=page'));
    }

    /**
     * @param array<string, string> $actions
     * @return array<string, string>
     */
    public static function rowAction(array $actions, \WP_Post $post): array
    {
        if (current_user_can('edit_page', $post->ID)) {
            $actions['wpfb'] = '<a href="' . esc_url(self::url($post->ID)) . '">' . esc_html__(
                'Edit with Builder',
                'wpfasty-builder',
            ) . '</a>';
        }
        return $actions;
    }

    /** a GET parameter of the screen URL (display state only; every action checks a nonce) */
    private static function query(string $key): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET[$key]) && is_string($_GET[$key]) ? sanitize_text_field(wp_unslash($_GET[$key])) : '';
    }

    private static function pageId(): int
    {
        return absint(self::query('post'));
    }

    /**
     * Pages list (edit.php?post_type=page): "Add New with Builder" right after the core "Add New" button
     * (.page-title-action), added by a small inline script — the core heading has no hook for a second button.
     */
    public static function pagesList(string $hook): void
    {
        if ($hook !== 'edit.php' || !current_user_can('edit_pages') || !function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if ($screen === null || $screen->post_type !== 'page') {
            return;
        }
        wp_register_script('wpfasty-builder-pages', false, [], Plugin::VERSION, true);
        wp_enqueue_script('wpfasty-builder-pages');
        wp_add_inline_script('wpfasty-builder-pages', self::addNewScript());
    }

    /** the inline script of pagesList(): one link, the core button's class, after the core button */
    public static function addNewScript(): string
    {
        return '(function(){var a=document.querySelector(".wrap .page-title-action");if(!a){return;}'
            . 'var b=document.createElement("a");b.className="page-title-action wpfb-add-new";'
            . 'b.href=' . wp_json_encode(self::newUrl(), self::JSON) . ';b.textContent='
            . wp_json_encode(__('Add New with Builder', 'wpfasty-builder'), self::JSON) . ';a.after(b);})();';
    }

    public static function enqueue(string $hook): void
    {
        if ($hook !== self::$hook || self::$hook === '') {
            return;
        }
        $id = self::pageId();
        $chooser = $id === 0 && current_user_can('edit_pages');
        if (!$chooser && ($id === 0 || get_post_type($id) !== 'page' || !current_user_can('edit_page', $id))) {
            return;
        }
        $js = Plugin::$dir . '/assets/editor.js';
        $css = Plugin::$dir . '/assets/editor.css';
        if (!is_file($js)) {
            return;
        }
        wp_enqueue_script(
            'wpfasty-builder-editor',
            plugins_url('assets/editor.js', Plugin::$file),
            [],
            (string) filemtime($js),
            true,
        );
        if (is_file($css)) {
            wp_enqueue_style(
                'wpfasty-builder-editor',
                plugins_url('assets/editor.css', Plugin::$file),
                [],
                (string) filemtime($css),
            );
        }
        $common = [
            'rest' => esc_url_raw(rest_url(Rest::NS)),
            'nonce' => wp_create_nonce('wp_rest'),
            'user' => get_current_user_id(),
            'starters' => Starters::forEditor(),
            'newPage' => self::query('new') === '1',
        ];
        if ($chooser) {
            wp_add_inline_script(
                'wpfasty-builder-editor',
                'window.WPFastyBuilderConfig = ' . wp_json_encode($common + [
                    'page' => 0, 'title' => '', 'source' => '', 'collection' => '', 'permalink' => '',
                    'canPublish' => false, 'editUrl' => '',
                ], self::JSON) . ';',
                'before',
            );
            return;
        }
        $collection = sanitize_key(self::query('collection'));
        $source = sanitize_key(self::query('source'));
        $entry = Store::get($id, true);
        if ($source === '') {
            $source = (string) ($entry['metadata']['source']['value'] ?? 'ui8kit');
        }
        if ($collection === '') {
            $collection = (string) ($entry['metadata']['collection']['value'] ?? '');
        }
        $config = $common + [
            'page' => $id,
            'title' => get_the_title($id),
            'source' => $source,
            'collection' => $collection,
            'permalink' => (string) get_permalink($id),
            'canPublish' => current_user_can('publish_pages'),
            'editUrl' => (string) get_edit_post_link($id, 'raw'),
        ];
        wp_add_inline_script(
            'wpfasty-builder-editor',
            'window.WPFastyBuilderConfig = ' . wp_json_encode($config, self::JSON) . ';',
            'before',
        );
        self::$editor = true;
    }

    /**
     * The dark mode switch: last item of the toolbar's secondary (right-hand) group, only on the builder screen with a
     * page open in the editor (screen id = the hook of the Builder page). A toggle button (aria-pressed, set by the
     * editor from the remembered mode), Lucide moon / sun inline; the editor script handles the click.
     */
    public static function darkSwitch(\WP_Admin_Bar $bar): void
    {
        if (!self::$editor || self::$hook === '' || !function_exists('get_current_screen')) {
            return;
        }
        $screen = get_current_screen();
        if ($screen === null || $screen->id !== self::$hook) {
            return;
        }
        $bar->add_node([
            'id' => self::DARK,
            'parent' => 'top-secondary',
            'title' => self::darkButton(),
            'href' => false,
            'meta' => ['class' => 'wpfb-adminbar-dark'],
        ]);
    }

    /** the switch markup; the editor script sets aria-pressed from the remembered mode and toggles it */
    public static function darkButton(): string
    {
        $label = __('Dark mode', 'wpfasty-builder');
        $svg = static fn (string $class, string $body): string => '<svg class="' . $class
            . '" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" '
            . 'stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
            . 'aria-hidden="true" focusable="false">' . $body . '</svg>';
        $moon = $svg('wpfb-dark-moon', '<path d="M20.985 12.486a9 9 0 1 1-9.473-9.472c.405-.022.617.46.402.803'
            . 'a6 6 0 0 0 8.268 8.268c.344-.215.825-.004.803.401"/>');
        $sun = $svg('wpfb-dark-sun', '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/>'
            . '<path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/>'
            . '<path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>');
        return '<button type="button" id="wpfb-dark-toggle" class="wpfb-dark-toggle" aria-pressed="false" title="'
            . esc_attr($label) . '">' . $moon . $sun . '<span class="screen-reader-text">' . esc_html($label)
            . '</span></button>';
    }

    public static function screen(): void
    {
        if (!current_user_can('edit_pages')) {
            wp_die(esc_html__('Sorry, you are not allowed to use the builder.', 'wpfasty-builder'));
        }
        $id = self::pageId();
        if ($id > 0 && get_post_type($id) === 'page' && current_user_can('edit_page', $id)) {
            echo '<div class="wrap wpfb-wrap"><div id="wpfasty-builder" class="wpfb-root"><p>' . esc_html__(
                'Loading the builder…',
                'wpfasty-builder',
            ) . '</p></div>';
            if (!is_file(Plugin::$dir . '/assets/editor.js')) {
                echo '<p>' . esc_html__(
                    'The editor build is missing: run the editor build (plugins/wpfasty-builder/editor).',
                    'wpfasty-builder',
                ) . '</p>';
            }
            echo '</div>';
            return;
        }
        $pages = get_pages(['post_status' => 'publish,draft,private', 'sort_column' => 'post_title']);
        echo '<div class="wrap"><h1 class="wp-heading-inline">' . esc_html__('Builder', 'wpfasty-builder') . '</h1> ';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup of escaped parts (newPageButton)
        echo self::newPageButton() . '<hr class="wp-header-end">';
        echo '<div id="wpfasty-builder-new" class="wpfb-root"></div>';
        echo '<p>' . esc_html__(
            'Choose a page to build. Pages with a published layout render it on the front end.',
            'wpfasty-builder',
        ) . '</p><table class="widefat striped"><tbody>';
        foreach ($pages as $p) {
            if (!current_user_can('edit_page', $p->ID)) {
                continue;
            }
            $state = Store::has($p->ID) ? esc_html__('built', 'wpfasty-builder') : '';
            printf(
                '<tr><td><a href="%s">%s</a></td><td>%s</td></tr>',
                esc_url(self::url($p->ID)),
                esc_html($p->post_title !== '' ? $p->post_title : '#' . $p->ID),
                esc_html($state)
            );
        }
        echo '</tbody></table></div>';
    }

    /** "New page" on the page chooser: the editor script opens the panel (aria-haspopup: a dialog) */
    public static function newPageButton(): string
    {
        return '<button type="button" id="wpfb-new-page" class="page-title-action" aria-haspopup="dialog">'
            . esc_html__('New page', 'wpfasty-builder') . '</button>';
    }

    /** blocks that cannot render in the site runtime — never rendered through the other engine instead */
    public static function problems(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        foreach (Sources::problems() as $msg) {
            echo '<div class="notice notice-error"><p>' . esc_html($msg) . '</p></div>';
        }
    }

    public static function sourcesScreen(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Sorry, you are not allowed to manage builder sources.', 'wpfasty-builder'));
        }
        echo '<div class="wrap"><h1>' . esc_html__('Builder sources', 'wpfasty-builder') . '</h1>';
        echo self::takeNotice(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped there
        echo '<table class="widefat striped"><thead><tr><th>' . esc_html__('Source', 'wpfasty-builder') . '</th><th>'
            . esc_html__('Collections', 'wpfasty-builder') . '</th></tr></thead><tbody>';
        foreach (Sources::all() as $src) {
            $catalog = Sources::catalog($src->id());
            $links = [];
            foreach ($catalog?->collections() ?? [] as $c) {
                if ($c['type'] === 'template') {
                    $links[] = esc_html($c['label']) . ' <code>' . esc_html($c['id']) . '</code>';
                }
            }
            printf(
                '<tr><td><strong>%s</strong><br><code>%s</code></td><td>%s</td></tr>',
                esc_html($src->label()),
                esc_html($src->id()),
                wp_kses_post(implode(', ', $links)),
            );
        }
        echo '</tbody></table>';
        if (Sources::canImport()) {
            echo '<h2>' . esc_html__('Import a remote source', 'wpfasty-builder') . '</h2>';
            echo '<p>' . esc_html(sprintf(
                // phpcs:disable Generic.Files.LineLength.TooLong -- a translatable literal must stay one string.
                /* translators: %1$s: site engine (latte or twig) */
                __('The URL must be a %1$s block registry (catalog.json + blocks.zip), e.g. https://registry.buildy.tw/%1$s/wpfasty/v1.0.0/.', 'wpfasty-builder'),
                // phpcs:enable Generic.Files.LineLength.TooLong
                (string) Plugin::engine(),
            )) . ' '
                . esc_html__('Remote templates compile through an allowlist of tags and functions.', 'wpfasty-builder')
                . '</p>';
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
            wp_nonce_field('wpfb_import');
            echo '<input type="hidden" name="action" value="wpfb_import">';
            echo '<p><label>' . esc_html__(
                'Id',
                'wpfasty-builder',
            ) . ' <input name="id" pattern="[a-z][a-z0-9_-]*" required></label> ';
            echo '<label>' . esc_html__(
                'URL',
                'wpfasty-builder',
            ) . ' <input name="url" type="url" size="60" required></label> ';
            submit_button(__('Import', 'wpfasty-builder'), 'primary', 'submit', false);
            echo '</p></form>';
        }
        echo '</div>';
    }

    public static function handleImport(): void
    {
        if (!Sources::canImport()) {
            wp_die(
                esc_html__('Sorry, you are not allowed to manage builder sources.', 'wpfasty-builder'),
                '',
                ['response' => 403],
            );
        }
        check_admin_referer('wpfb_import');
        $id = isset($_POST['id']) ? sanitize_key(wp_unslash((string) $_POST['id'])) : '';
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash((string) $_POST['url'])) : '';
        try {
            $s = Sources::import($id, $url);
            self::notice('success', sprintf(
                /* translators: 1: number of blocks, 2: number of collections */
                __('Imported %1$d blocks and %2$d collections.', 'wpfasty-builder'),
                $s['blocks'],
                $s['collections'],
            ));
        } catch (\Throwable $e) {
            self::notice('error', $e->getMessage());
        }
        wp_safe_redirect(add_query_arg(['page' => self::SOURCES], admin_url('options-general.php')));
        exit;
    }

    /**
     * The result of an import for the Sources screen after the redirect: kept server-side for the current user (a
     * transient, 5 minutes, read once), never in the URL — a link cannot put text on the screen.
     */
    public static function notice(string $type, string $text): void
    {
        set_transient(self::NOTICE . get_current_user_id(), [
            'type' => $type === 'error' ? 'error' : 'success',
            'text' => $text,
        ], 300);
    }

    /** the pending notice of the current user as an admin notice (escaped), then forgotten; '' without one */
    public static function takeNotice(): string
    {
        $key = self::NOTICE . get_current_user_id();
        $n = get_transient($key);
        if (!is_array($n) || !is_string($n['text'] ?? null)) {
            return '';
        }
        delete_transient($key);
        $type = ($n['type'] ?? '') === 'error' ? 'error' : 'success';
        return '<div class="notice notice-' . $type . '"><p>' . esc_html($n['text']) . '</p></div>';
    }
}
