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
        add_filter('wpfasty_color_scheme', [self::class, 'colorScheme']);
        add_action('wp_head', [Pager::class, 'head'], 5);
    }

    /**
     * @param array<int, string> $roots
     *
     * @return list<string>
     */
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
        $nonce = isset($_GET['wpfb_preview'], $_GET['_wpnonce']) && is_string($_GET['_wpnonce'])
            ? sanitize_text_field(wp_unslash($_GET['_wpnonce']))
            : '';
        // phpcs:enable
        if ('' === $nonce) {
            return false;
        }

        return current_user_can('edit_page', $pageId) && false !== wp_verify_nonce($nonce, 'wpfb_preview_'.$pageId);
    }

    public static function previewUrl(int $pageId): string
    {
        return add_query_arg(
            ['wpfb_preview' => 1, '_wpnonce' => wp_create_nonce('wpfb_preview_'.$pageId)],
            (string) get_permalink($pageId),
        );
    }

    /** Preview-only presentation; never stores or overrides the live site's color preference. */
    public static function colorScheme(mixed $value): mixed
    {
        $id = is_page() ? (int) get_queried_object_id() : 0;
        if ($id <= 0 || !self::isPreview($id)) {
            return $value;
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- isPreview() verifies the page nonce/capability above
        $scheme = isset($_GET['wpfb_scheme']) && is_string($_GET['wpfb_scheme'])
            ? sanitize_key(wp_unslash($_GET['wpfb_scheme']))
            : 'light';
        // phpcs:enable

        return 'dark' === $scheme ? 'dark' : 'light';
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
        if (null === self::themeRenderer()) {
            return $view;
        }
        $id = (int) get_queried_object_id();
        $preview = self::isPreview($id);
        if (!$preview && (!Store::has($id) || post_password_required($id))) {
            return $view;
        }
        $entry = Store::get($id, $preview);
        $compiled = null !== $entry ? Store::compile($id, $entry, $preview) : null;
        if (null === $compiled) {
            return $view;
        }
        self::$page = $id;
        self::$preview = $preview;
        self::attach();

        return $compiled;
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function context(array $context, mixed $post = null): array
    {
        if (null === self::$page) {
            return $context;
        }
        $entry = Store::get(self::$page, self::$preview);
        $context['wpfb'] = null !== $entry ? self::bound($entry, $context) : [];
        // record tokens of every block ({post:}, {product:}, {term:}, {price:}, {stock:}): one query per type
        Links::prime(null !== $entry ? array_column(Layout::blocks($entry), 'props') : []);
        $mode = get_post_meta(self::$page, PageOnboarding::SHELL, true);
        if (in_array($mode, ['blank', 'standard', 'sidebar'], true)) {
            $context['builder_shell'] = $mode;
            $context['shell'] = 'classic';
        }
        if (in_array($mode, ['blank', 'standard'], true)) {
            $context['widgets'] = ['sidebar' => ''] + Cast::map($context['widgets'] ?? []);
        }

        return $context;
    }

    /**
     * Data of every binding: [block id][path] => records; the pager group of a list block (Pager) at its path.
     *
     * @param array<string, mixed> $entry
     * @param array<string, mixed> $context the theme context (ContextFactory: `pagination` of the main query)
     *
     * @return array<string, array<string, mixed>>
     */
    public static function bound(array $entry, array $context = []): array
    {
        $catalog = Sources::catalog(Cast::string(Cast::path($entry, 'metadata', 'source', 'value')));
        $out = [];
        foreach (Layout::blocks($entry) as $b) {
            $kinds = $catalog?->bindable(Cast::string($b['block']), Cast::string($b['variant'])) ?? [];
            // the pager (PostGrid `pager`): asked for by the variant / layout mode, filled here, null on one page
            $pager = $catalog?->pager(Cast::string($b['block']), Cast::string($b['variant']));
            $sample = (array) ($catalog?->variant(Cast::string($b['block']), Cast::string($b['variant']))['props'] ?? []);
            $paged = null !== $pager && Pager::active(Layout::merge($sample, (array) $b['props']), $pager['path']);
            foreach (Cast::map($b['bindings']) as $path => $bind) {
                $bind = DataSources::binding($bind);
                if (null !== $bind && isset($kinds[$path])) {
                    $slot = $catalog?->slot(Cast::string($b['block']), Cast::string($b['variant']), Cast::string($path));
                    if ($paged && Cast::string($path) === $pager['list']) {
                        // the block's own query: the page of the request, its pages from the found rows of that query
                        $page = Pager::request();
                        [$records, $pages] = DataSources::paged($bind, $kinds[Cast::string($path)], $slot, $page);
                        $out[Cast::string($b['id'])][Cast::string($path)] = $records;
                        $out[Cast::string($b['id'])][$pager['path']] = Pager::value($page, $pages, Pager::urls());
                        continue;
                    }
                    $out[Cast::string($b['id'])][Cast::string($path)] = DataSources::resolve(
                        $bind,
                        $kinds[Cast::string($path)],
                        $slot,
                    );
                }
            }
            if (null !== $pager && !array_key_exists($pager['path'], $out[Cast::string($b['id'])] ?? [])) {
                // no own query: the main query (ContextFactory pagination, else max_num_pages); a page has one page
                $out[Cast::string($b['id'])][$pager['path']] = $paged
                    ? Pager::value(...[...Pager::main($context), Pager::urls($context)])
                    : null;
            }
            // the record of the current route (the queried post: richtext title, the_content…), read once per page
            $current = $catalog?->currentValues(
                Cast::string($b['block']),
                Cast::string($b['variant']),
                static fn (array $slot): array => DataSources::current($slot),
            ) ?? [];
            foreach ($current as $path => $value) {
                $out[Cast::string($b['id'])][Cast::string($path)] = $value;
            }
        }

        return $out;
    }

    public static function styles(): void
    {
        if (null === self::$page && (!is_page() || !Store::has((int) get_queried_object_id()))) {
            return;
        }
        $file = Plugin::$dir.'/assets/blocks.css';
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
            $engine->registerUndefinedFunctionCallback(static fn (string $name): \Twig\TwigFunction|false => 'wpfb_block' === $name
                    ? new \Twig\TwigFunction('wpfb_block', [self::class, 'block'], ['is_safe' => ['html']])
            : false);
        }
    }

    /** @param array<string, mixed> $props */
    public static function block(string $source, string $template, array $props): string
    {
        self::$renderer ??= new BlockRenderer(Sources::all(), Store::dir().'/cache');

        return Links::finish(self::$renderer->render($source, $template, self::prepare($props)));
    }

    /**
     * The props a block template gets: link tokens resolved (Links::walk), then made safe to print (Safe::props: URL
     * props with a disallowed scheme emptied, control characters dropped) — after the tokens, so a resolved value
     * ({site:…}, a record URL) is checked like a typed one. Latte and Twig render from the same safe props.
     *
     * @param array<string, mixed> $props
     *
     * @return array<string, mixed>
     */
    public static function prepare(array $props): array
    {
        return Cast::map(Safe::props(Links::walk($props)));
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
        $engine = match (true) {
            is_object($renderer) && method_exists($renderer, 'latte') => $renderer->latte(),
            is_object($renderer) && method_exists($renderer, 'twig') => $renderer->twig(),
            default => null,
        };

        return $engine instanceof \Latte\Engine || $engine instanceof \Twig\Environment ? $engine : null;
    }
}
