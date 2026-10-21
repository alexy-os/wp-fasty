/**
 * wpfasty-catalog@2: the catalog of ONE runtime (docs/agent/builder-registry.md). Both runtimes share blocks,
 * variants, props and collections; only `runtime`/`templates` and the variant template extension differ.
 */
export const CATALOG_VERSION = "1.0.0";
export type Runtime = "latte" | "twig";
export const EXT: Record<Runtime, string> = { latte: ".latte", twig: ".html.twig" };

export function runtimeCatalog(base: any, runtime: Runtime, version = CATALOG_VERSION): any {
  const { engines: _e, $schema: _s, version: _v, templates: _t, ...rest } = base;
  const blocks = (base.blocks ?? []).map((b: any) => ({
    ...b,
    variants: (b.variants ?? []).map((v: any) => ({ ...v, template: String(v.template).replace(/\.latte$/, EXT[runtime]) })),
  }));
  return {
    $schema: "https://github.com/alexy-os/wp-fasty/plugins/wpfasty-builder/catalog@2",
    format: 2,
    runtime,
    templates: runtime,
    version,
    ...rest,
    blocks,
  };
}
