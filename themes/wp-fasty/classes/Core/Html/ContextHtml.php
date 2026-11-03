<?php

declare(strict_types=1);

namespace WPFasty\Core\Html;

/**
 * The HTML fields of the template context: they hold TrustedHtml, never a string (ArchitectureTest, ContextHtmlTest).
 *
 * - page / every post: `content` (the_content), `linkPages` (wp_link_pages)
 * - `comments` (comment list + form), `widgets.sidebar`, `widgets.footer` (widget areas)
 *
 * `trust()` marks those fields of a frozen context (JSON fixtures, snapshots) the way the WordPress adapters do; the
 * renderers turn TrustedHtml into their raw-HTML type (`unwrap()`).
 */
final class ContextHtml
{
    /** keys of a post view-model that hold HTML */
    public const POST_FIELDS = ['content', 'linkPages'];

    /** keys of `widgets` (widget areas) */
    public const WIDGET_FIELDS = ['sidebar', 'footer'];

    /**
     * @param array<array-key, mixed> $context
     *
     * @return array<array-key, mixed>
     */
    public static function trust(array $context): array
    {
        $mark = static fn (mixed $v): mixed => \is_string($v) ? new TrustedHtml($v) : $v;
        if (\array_key_exists('comments', $context)) {
            $context['comments'] = $mark($context['comments']);
        }
        if (\is_array($context['widgets'] ?? null)) {
            foreach (self::WIDGET_FIELDS as $k) {
                if (\array_key_exists($k, $context['widgets'])) {
                    $context['widgets'][$k] = $mark($context['widgets'][$k]);
                }
            }
        }
        $post = static function (mixed $p) use ($mark): mixed {
            if (!\is_array($p)) {
                return $p;
            }
            foreach (self::POST_FIELDS as $k) {
                if (\array_key_exists($k, $p)) {
                    $p[$k] = $mark($p[$k]);
                }
            }

            return $p;
        };
        if (\array_key_exists('page', $context)) {
            $context['page'] = $post($context['page']);
        }
        if (\is_array($context['posts'] ?? null)) {
            $context['posts'] = array_map($post, $context['posts']);
        }

        return $context;
    }

    /**
     * Every TrustedHtml in the context, at any depth, becomes `$raw(html)`; an empty one becomes '' so `{if}` and
     * `?? false` tests stay what they were with strings.
     *
     * @param array<array-key, mixed>  $context
     * @param callable(string): object $raw
     *
     * @return array<array-key, mixed>
     */
    public static function unwrap(array $context, callable $raw): array
    {
        foreach ($context as $k => $v) {
            if ($v instanceof TrustedHtml) {
                $context[$k] = $v->isEmpty() ? '' : $raw((string) $v);
            } elseif (\is_array($v)) {
                $context[$k] = self::unwrap($v, $raw);
            }
        }

        return $context;
    }
}
