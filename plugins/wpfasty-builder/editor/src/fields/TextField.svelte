<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const text = $derived(value === undefined || value === null ? "" : String(value));
  let timer: ReturnType<typeof setTimeout> | undefined;
  // typing updates the preview after a short pause; leaving the field commits at once
  function input(v: string) {
    clearTimeout(timer);
    timer = setTimeout(() => onchange(path, v), 300);
  }
  function commit(v: string) {
    clearTimeout(timer);
    if (v !== text) onchange(path, v);
  }
</script>

<div class="wpfb-field">
  <Label label={field.label} {path} {ctx} {onchange} />
  {#if field.type === "textarea"}
    <textarea id={fid(path)} rows="3" value={text} oninput={(e) => input(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)}></textarea>
  {:else}
    <input id={fid(path)} type="text" value={text} oninput={(e) => input(e.currentTarget.value)} onchange={(e) => commit(e.currentTarget.value)} />
  {/if}
  {#if field.docs}<p class="wpfb-docs">{field.docs}</p>{/if}
</div>
