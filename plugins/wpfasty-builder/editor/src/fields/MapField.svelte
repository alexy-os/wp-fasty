<script lang="ts">
  import Icon from "../Icon.svelte";
  import { Trash2, Plus } from "../icons";
      import type { Json, Obj } from "../types";
  import type { Field } from "./schema";
  import type { FieldCtx, OnChange } from "./context";
  import { isObj } from "../util";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const rows = $derived(Object.entries(isObj(value) ? value : {}));
  function put(entries: [string, Json][]) {
    onchange(path, Object.fromEntries(entries.filter(([k]) => k !== "")) as Obj);
  }
</script>

<fieldset class="wpfb-group wpfb-map">
  <legend><Label label={field.label} {path} {ctx} {onchange} tag="span" /></legend>
  {#each rows as [k, v], i (k)}
    <div class="wpfb-map-row">
      <input type="text" aria-label="Key" value={k} onchange={(e) => put(rows.map(([a, b], j) => (j === i ? [e.currentTarget.value, b] : [a, b])))} />
      <input type="text" aria-label="Label for {k}" value={String(v ?? "")} onchange={(e) => put(rows.map(([a, b], j) => (j === i ? [a, e.currentTarget.value] : [a, b])))} />
      <button type="button" class="wpfb-icon wpfb-danger" aria-label="Remove {k}" onclick={() => put(rows.filter((_, j) => j !== i))}><Icon icon={Trash2} size={16} /></button>
    </div>
  {/each}
  <button type="button" class="button button-small wpfb-add" onclick={() => put([...rows, [`key${rows.length + 1}`, ""]])}><Icon icon={Plus} size={16} /> Add entry</button>
</fieldset>
