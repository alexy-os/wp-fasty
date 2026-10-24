<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
</script>

<div class="wpfb-field">
  <Label label={field.label} {path} {ctx} {onchange} />
  <input id={fid(path)} type="number" value={typeof value === "number" ? value : ""}
    onchange={(e) => { const n = e.currentTarget.valueAsNumber; if (Number.isFinite(n)) onchange(path, n); }} />
  {#if field.docs}<p class="wpfb-docs">{field.docs}</p>{/if}
</div>
