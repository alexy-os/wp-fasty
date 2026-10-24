<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const options = $derived(field.type === "select" || field.type === "radio" ? field.options : []);
</script>

<div class="wpfb-field">
  <Label label={field.label} {path} {ctx} {onchange} />
  <select id={fid(path)} value={String(value ?? "")}
    onchange={(e) => { const o = options[e.currentTarget.selectedIndex]; if (o) onchange(path, o.value); }}>
    {#each options as o (o.value)}<option value={String(o.value)}>{o.label}</option>{/each}
  </select>
  {#if field.docs}<p class="wpfb-docs">{field.docs}</p>{/if}
</div>
