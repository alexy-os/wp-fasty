<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * wp-admin: the builder screen (Pages → Builder, edit_pages) and the sources screen (Settings → Builder sources,
 * manage_options). The editor script and styles are enqueued on the builder screen only.
 * Open a collection with ?collection=<id> to show only its blocks in the sidebar.
 */
final class Admin
{
    public const SLUG = 'wpfasty-builder';
    public const SOURCES = 'wpfasty-builder-sources';
    private static string $hook = '';

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'menu']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
        add_filter('page_row_actions', [self::class, 'rowAction'], 10, 2);
        add_action('admin_post_wpfb_import', [self::class, 'handleImport']);
        add_action('admin_notices', [self::class, 'problems']);
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
                'Edit with builder',
                'wpfasty-builder',
            ) . '</a>';
        }
        return $actions;
    }

    /** a GET parameter of the screen URL (display state only; every action checks a nonce) */
    private static function query(string $key): string
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        return isset($_GET[$key]) && is_string($_GET[$key]) ? (string) wp_unslash($_GET[$key]) : '';
    }

    private static function pageId(): int
    {
        return absint(self::query('post'));
    }

    public static function enqueue(string $hook): void
    {
        if ($hook !== self::$hook || self::$hook === '') {
            return;
        }
        $id = self::pageId();
        if ($id === 0 || get_post_type($id) !== 'page' || !current_user_can('edit_page', $id)) {
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
        $collection = sanitize_key(self::query('collection'));
        $source = sanitize_key(self::query('source'));
        $entry = Store::get($id, true);
        if ($source === '') {
            $source = (string) ($entry['metadata']['source']['value'] ?? 'ui8kit');
        }
        if ($collection === '') {
            $collection = (string) ($entry['metadata']['collection']['value'] ?? '');
        }
        $config = [
            'rest' => esc_url_raw(rest_url(Rest::NS)),
            'nonce' => wp_create_nonce('wp_rest'),
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
            'window.WPFastyBuilderConfig = ' . wp_json_encode($config) . ';',
            'before',
        );
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
        echo '<div class="wrap"><h1>' . esc_html__('Builder', 'wpfasty-builder') . '</h1>';
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
        $msg = sanitize_text_field(self::query('wpfb_msg'));
        if ($msg !== '') {
            echo '<div class="notice notice-info"><p>' . esc_html($msg) . '</p></div>';
        }
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
        echo '</p></form></div>';
    }

    public static function handleImport(): void
    {
        if (!current_user_can('manage_options')) {
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
            $msg = sprintf(
                /* translators: 1: number of blocks, 2: number of collections */
                __('Imported %1$d blocks and %2$d collections.', 'wpfasty-builder'),
                $s['blocks'],
                $s['collections'],
            );
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
        }
        wp_safe_redirect(add_query_arg(
            ['page' => self::SOURCES, 'wpfb_msg' => rawurlencode($msg)],
            admin_url('options-general.php'),
        ));
        exit;
    }
}
