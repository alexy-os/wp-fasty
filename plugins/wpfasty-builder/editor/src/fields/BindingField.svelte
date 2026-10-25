<script lang="ts">
  import Icon from "../Icon.svelte";
  import { Database } from "../icons";
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import { registry } from "./registry";
  import { levelOf, sourcesFor } from "../sources";

  let { field, path, value, onchange, ctx, kind }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx; kind: string } = $props();
  const key = $derived(path.join("."));
  const bound = $derived(ctx.bindings[key]);
  const sources = $derived(sourcesFor(ctx.dataSources, kind, bound?.source));
  const current = $derived(ctx.dataSources.find((d) => d.id === bound?.source));
  const Inner = $derived(registry[field.type] ?? registry.json);
</script>

<fieldset class="wpfb-group wpfb-binding">
  <legend><Icon icon={Database} size={12} /> {field.label} <span class="wpfb-muted">{kind}</span></legend>
  <div class="wpfb-field">
    <label for={fid(path) + "-src"}>Data</label>
    <select id={fid(path) + "-src"} value={bound?.source ?? ""}
      onchange={(e) => { const v = e.currentTarget.value; ctx.setBinding(key, v ? { source: v, limit: bound?.limit ?? 6, ...(v === bound?.source && bound?.map ? { map: bound.map } : {}) } : null); }}>
      <option value="">Sample data (edit below)</option>
      {#each sources as d (d.id)}<option value={d.id}>{d.label}{levelOf(d, kind) ? " (auto)" : ""}</option>{/each}
    </select>
  </div>
  {#if bound}
    <div class="wpfb-row">
      <div class="wpfb-field">
        <label for={fid(path) + "-n"}>How many</label>
        <input id={fid(path) + "-n"} type="number" min="1" max="24" value={bound.limit}
          onchange={(e) => ctx.setBinding(key, { ...bound, limit: Math.max(1, Math.min(24, e.currentTarget.valueAsNumber || 6)) })} />
      </div>
      <div class="wpfb-field">
        <label for={fid(path) + "-t"}>Term slug</label>
        <input id={fid(path) + "-t"} type="text" placeholder="e.g. design" value={bound.term ?? ""}
          onchange={(e) => { const t = e.currentTarget.value.trim(); const b = { ...bound }; if (t) b.term = t; else delete b.term; ctx.setBinding(key, b); }} />
      </div>
    </div>
    {#if current?.purchasable}
      <label class="wpfb-check"><input type="checkbox" checked={bound.sale === true}
        onchange={(e) => { const b = { ...bound }; if (e.currentTarget.checked) b.sale = true; else delete b.sale; ctx.setBinding(key, b); }} /> On sale only</label>
    {/if}
  {:else}
    <Inner {field} {path} {value} {onchange} {ctx} />
  {/if}
</fieldset>
