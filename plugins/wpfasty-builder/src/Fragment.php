<?php

declare(strict_types=1);

namespace WPFastyBuilder;

/**
 * One block rendered on its own — the HTML the page template prints inside `<div data-wpfb-block="<id>">` for the same
 * block: the input is sanitized like a saved layout (Layout::normalize), the props are the ones Compiler writes into
 * the page (variant sample props, overrides, kind-aware labels) and every bound path is filled with the records the
 * page context would hold (`wpfb[<id>][<path>]`). The editor swaps this fragment into its preview instead of reloading
 * the page (REST POST /render-block). Only templates of the source's own runtime render (ADR-001, K7).
 */
final class Fragment
{
    /**
     * @param array<string, mixed> $input      untrusted {id?, block, variant, props?, bindings?}
     * @param list<string>         $dataSources ids accepted in bindings
     * @param callable(array<string, mixed>, string, array<string, mixed>|null): list<array<string, mixed>> $resolve
     *        records of a binding (binding, data kind, Catalog::slot())
     * @param callable(string, array<string, mixed>): string                    $render   HTML of a template
     * @param (callable(array<string, mixed>): array<string, mixed>)|null       $current  the record of the current
     *        route for a current-scope slot (Catalog::currentSlot); null: none (those paths stay null, as on a page
     *        without the record)
     * @param callable|null                                                   $paged    (binding, kind, slot) →
     *        [records, pages]: the first page of the list a pager pages and its number of pages (DataSources::paged
     *        on page 1); null: the list resolves as any binding, one page
     * @param (callable(int): (array<string, mixed>|null))|null $pager the pager group for a number of pages (the
     *        editor previews page 1: Pager::value(1, pages)); null: no pager
     * @return array{id: string, html: string, issues: list<array{code: string, path: string, message: string}>}|null
     *         null when the block or variant is unknown or its template is not of the catalog's runtime
     */
    public static function render(
        Catalog $catalog,
        string $sourceId,
        array $input,
        array $dataSources,
        callable $resolve,
        callable $render,
        ?callable $current = null,
        ?callable $paged = null,
        ?callable $pager = null,
    ): ?array {
        [$entry, $issues] = Layout::normalize(['blocks' => [$input]], $sourceId, $catalog, $dataSources, [
            'id' => 'fragment',
            'slug' => 'fragment',
            'title' => '',
            'version' => 1,
            'created_at' => '2000-01-01T00:00:00Z',
            'updated_at' => '2000-01-01T00:00:00Z',
        ]);
        $b = Layout::blocks($entry)[0] ?? null;
        if ($b === null) {
            return null;
        }
        $variant = (array) $catalog->variant((string) $b['block'], (string) $b['variant']);
        $template = (string) ($variant['template'] ?? '');
        $runtime = $catalog->runtime();
        $own = $runtime !== null && str_ends_with($template, Compiler::ENGINES[$runtime]);
        if (preg_match(Catalog::TEMPLATE, $template) !== 1 || !$own) {
            return null;
        }
        $props = Compiler::blockProps($variant, $b, $catalog);
        $block = (string) $b['block'];
        $variantId = (string) $b['variant'];
        $kinds = $catalog->bindable($block, $variantId);
        // the pager group (Pager): filled as on the page, null when the mode is not `pages` or on one page
        $at = $catalog->pager($block, $variantId);
        $active = $at !== null && $pager !== null
            && Pager::active(Layout::merge((array) ($variant['props'] ?? []), (array) $b['props']), $at['path']);
        $pages = 1;
        foreach ($b['bindings'] as $path => $bind) {
            $path = (string) $path;
            if ($active && $paged !== null && $path === $at['list'] && is_array($bind) && isset($kinds[$path])) {
                [$records, $pages] = $paged($bind, $kinds[$path], $catalog->slot($block, $variantId, $path));
                Layout::set($props, explode('.', $path), $records);
                continue;
            }
            Layout::set(
                $props,
                explode('.', $path),
                is_array($bind) && isset($kinds[$path])
                    ? $resolve($bind, $kinds[$path], $catalog->slot($block, $variantId, $path))
                    : [],
            );
        }
        if ($current !== null) {
            foreach ($catalog->currentValues($block, $variantId, $current) as $path => $value) {
                Layout::set($props, explode('.', $path), $value);
            }
        }
        if ($at !== null) {
            Layout::set($props, explode('.', $at['path']), $active ? $pager($pages) : null);
        }
        $issues = array_values(array_filter(
            $issues,
            static fn (array $i): bool => !str_starts_with($i['code'], 'core.'),
        ));
        return ['id' => (string) $b['id'], 'html' => $render($template, $props), 'issues' => $issues];
    }
}
