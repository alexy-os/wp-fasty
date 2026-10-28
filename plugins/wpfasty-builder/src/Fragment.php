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
        foreach ($b['bindings'] as $path => $bind) {
            $path = (string) $path;
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
        $issues = array_values(array_filter(
            $issues,
            static fn (array $i): bool => !str_starts_with($i['code'], 'core.'),
        ));
        return ['id' => (string) $b['id'], 'html' => $render($template, $props), 'issues' => $issues];
    }
}
