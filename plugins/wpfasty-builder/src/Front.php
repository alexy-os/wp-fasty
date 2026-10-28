<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * Front end: a page with a published layout renders its compiled template through the theme pipeline, Latte or
 * Twig (whichever `wpfasty_renderer` picked)
 * (`wpfasty_view_roots`, `wpfasty_resolve_view`, `wpfasty_context`). The only builder asset on the front end
 * is the block stylesheet; the only builder script is the small inline {open-now} one (Links::footer), printed only
 * on pages that use the token and only when the site has opening hours.
 */
final class Front
{
    private static ?int $page = null;
    private static bool $preview = false;
    private static ?BlockRenderer $renderer = null;
    private static bool $twigAttached = false;

    public static function register(): void
    {
        add_filter('wpfasty_view_roots', [self::class, 'roots'], 5);
        add_filter('wpfasty_resolve_view', [self::class, 'view'], 20);
        add_filter('wpfasty_context', [self::class, 'context'], 20, 2);
        add_action('wp_enqueue_scripts', [self::class, 'styles'], 20);
        add_action('template_redirect', [self::class, 'previewGuard'], 1);
        add_action('wp_head', [Pager::class, 'head'], 5);
    }

    /** @param array<int, string> $roots */
    public static function roots(array $roots): array
    {
        $dir = Store::viewsDir();
        if (!is_dir($dir)) {
            wp_mkdir_p($dir);
        }
        return [$dir, ...$roots];
    }

    /** true when this request previews the draft of the queried page (editors only, with a nonce) */
    public static function isPreview(int $pageId): bool
    {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- the nonce is verified below
        $nonce = isset($_GET['wpfb_preview'], $_GET['_wpnonce'])
            ? sanitize_text_field(wp_unslash((string) $_GET['_wpnonce']))
            : '';
        // phpcs:enable
        if ($nonce === '') {
            return false;
        }
        return current_user_can('edit_page', $pageId) && wp_verify_nonce($nonce, 'wpfb_preview_' . $pageId) !== false;
    }

    public static function previewUrl(int $pageId): string
    {
        return add_query_arg(
            ['wpfb_preview' => 1, '_wpnonce' => wp_create_nonce('wpfb_preview_' . $pageId)],
            (string) get_permalink($pageId),
        );
    }

    public static function previewGuard(): void
    {
        $id = is_page() ? (int) get_queried_object_id() : 0;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- isPreview() verifies the nonce
        if ($id > 0 && isset($_GET['wpfb_preview'])) {
            if (!self::isPreview($id)) {
                wp_die(esc_html__('This preview link has expired.', 'wpfasty-builder'), '', ['response' => 403]);
            }
            nocache_headers();
            add_filter('show_admin_bar', '__return_false');
            header('X-Robots-Tag: noindex');
        }
    }

    public static function view(mixed $view): mixed
    {
        if (!is_page()) {
            return $view;
        }
        // Landings compile to Latte and Twig; an unknown theme renderer renders the page as a normal page.
        if (self::themeRenderer() === null) {
            return $view;
        }
        $id = (int) get_queried_object_id();
        $preview = self::isPreview($id);
        if (!$preview && (!Store::has($id) || post_password_required($id))) {
            return $view;
        }
        $entry = Store::get($id, $preview);
        $compiled = $entry !== null ? Store::compile($id, $entry, $preview) : null;
        if ($compiled === null) {
            return $view;
        }
        self::$page = $id;
        self::$preview = $preview;
        self::attach();
        return $compiled;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public static function context(array $context, mixed $post = null): array
    {
        if (self::$page === null) {
            return $context;
        }
        $entry = Store::get(self::$page, self::$preview);
        $context['wpfb'] = $entry !== null ? self::bound($entry, $context) : [];
        // record tokens of every block ({post:}, {product:}, {term:}, {price:}, {stock:}): one query per type
        Links::prime($entry !== null ? array_column(Layout::blocks($entry), 'props') : []);
        $context['widgets']['sidebar'] = '';
        return $context;
    }

    /**
     * Data of every binding: [block id][path] => records; the pager group of a list block (Pager) at its path.
     *
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $context the theme context (ContextFactory: `pagination` of the main query)
     * @return array<string, array<string, mixed>>
     */
    public static function bound(array $entry, array $context = []): array
    {
        $catalog = Sources::catalog((string) ($entry['metadata']['source']['value'] ?? ''));
        $out = [];
        foreach (Layout::blocks($entry) as $b) {
            $kinds = $catalog?->bindable((string) $b['block'], (string) $b['variant']) ?? [];
            // the pager (PostGrid `pager`): asked for by the variant / layout mode, filled here, null on one page
            $pager = $catalog?->pager((string) $b['block'], (string) $b['variant']);
            $sample = (array) ($catalog?->variant((string) $b['block'], (string) $b['variant'])['props'] ?? []);
            $paged = $pager !== null && Pager::active(Layout::merge($sample, (array) $b['props']), $pager['path']);
            foreach ($b['bindings'] as $path => $bind) {
                if (is_array($bind) && isset($kinds[(string) $path])) {
                    $slot = $catalog?->slot((string) $b['block'], (string) $b['variant'], (string) $path);
                    if ($paged && (string) $path === $pager['list']) {
                        // the block's own query: the page of the request, its pages from the found rows of that query
                        $page = Pager::request();
                        [$records, $pages] = DataSources::paged($bind, $kinds[(string) $path], $slot, $page);
                        $out[(string) $b['id']][(string) $path] = $records;
                        $out[(string) $b['id']][$pager['path']] = Pager::value($page, $pages);
                        continue;
                    }
                    $out[(string) $b['id']][(string) $path] = DataSources::resolve(
                        $bind,
                        $kinds[(string) $path],
                        $slot,
                    );
                }
            }
            if ($pager !== null && !array_key_exists($pager['path'], $out[(string) $b['id']] ?? [])) {
                // no own query: the main query (ContextFactory pagination, else max_num_pages); a page has one page
                $out[(string) $b['id']][$pager['path']] = $paged ? Pager::value(...Pager::main($context)) : null;
            }
            // the record of the current route (the queried post: richtext title, the_content…), read once per page
            $current = $catalog?->currentValues(
                (string) $b['block'],
                (string) $b['variant'],
                static fn (array $slot): array => DataSources::current($slot),
            ) ?? [];
            foreach ($current as $path => $value) {
                $out[(string) $b['id']][(string) $path] = $value;
            }
        }
        return $out;
    }

    public static function styles(): void
    {
        if (self::$page === null && !(is_page() && Store::has((int) get_queried_object_id()))) {
            return;
        }
        $file = Plugin::$dir . '/assets/blocks.css';
        if (is_file($file)) {
            wp_enqueue_style(
                'wpfasty-builder-blocks',
                plugins_url('assets/blocks.css', Plugin::$file),
                [],
                (string) filemtime($file),
            );
        }
    }

    /** registers wpfb_block() on the theme engine (Latte function or Twig function, same callable) */
    public static function attach(): void
    {
        $engine = self::themeRenderer();
        if ($engine instanceof \Latte\Engine) {
            if (!isset($engine->getFunctions()['wpfb_block'])) {
                $engine->addFunction('wpfb_block', [self::class, 'block']);
            }
            return;
        }
        if ($engine instanceof \Twig\Environment && !self::$twigAttached) {
            self::$twigAttached = true;
            // A callback, not addFunction(): it works after the Twig extensions are initialized.
            $engine->registerUndefinedFunctionCallback(static fn (string $name): \Twig\TwigFunction|false
                => $name === 'wpfb_block'
                    ? new \Twig\TwigFunction('wpfb_block', [self::class, 'block'], ['is_safe' => ['html']])
                    : false);
        }
    }

    /** @param array<string, mixed> $props */
    public static function block(string $source, string $template, array $props): string
    {
        self::$renderer ??= new BlockRenderer(Sources::all(), Store::dir() . '/cache');
        return Links::finish(self::$renderer->render($source, $template, self::prepare($props)));
    }

    /**
     * The props a block template gets: link tokens resolved (Links::walk), then made safe to print (Safe::props: URL
     * props with a disallowed scheme emptied, control characters dropped) — after the tokens, so a resolved value
     * ({site:…}, a record URL) is checked like a typed one. Latte and Twig render from the same safe props.
     *
     * @param array<string, mixed> $props
     * @return array<string, mixed>
     */
    public static function prepare(array $props): array
    {
        return Safe::props(Links::walk($props));
    }

    /** the theme's Latte engine or Twig environment, null without a known WP FastY renderer */
    public static function themeRenderer(): \Latte\Engine|\Twig\Environment|null
    {
        if (!class_exists('\WPFasty\Core\Application')) {
            return null;
        }
        try {
            $renderer = \WPFasty\Core\Application::getInstance()->get('template.engine');
        } catch (\Throwable) {
            return null;
        }
        if (is_object($renderer) && method_exists($renderer, 'latte')) {
            return $renderer->latte();
        }
        return is_object($renderer) && method_exists($renderer, 'twig') ? $renderer->twig() : null;
    }
}
