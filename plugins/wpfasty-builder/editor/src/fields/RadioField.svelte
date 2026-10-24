<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const options = $derived(field.type === "select" || field.type === "radio" ? field.options : []);
</script>

<div class="wpfb-field" role="radiogroup" aria-label={field.label}>
  <Label label={field.label} {path} {ctx} {onchange} tag="span" />
  <div class="wpfb-seg">
    {#each options as o (o.value)}
      <label class:is-on={value === o.value}>
        <input type="radio" name={fid(path)} checked={value === o.value} onchange={() => onchange(path, o.value)} />{o.label}
      </label>
    {/each}
  </div>
  {#if field.docs}<p class="wpfb-docs">{field.docs}</p>{/if}
</div>
