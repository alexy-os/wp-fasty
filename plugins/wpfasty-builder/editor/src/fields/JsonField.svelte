<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  let error = $state("");
  const text = $derived(JSON.stringify(value ?? null, null, 2));
  function commit(v: string) {
    try {
      onchange(path, JSON.parse(v));
      error = "";
    } catch (e) {
      error = (e as Error).message;
    }
  }
</script>

<div class="wpfb-field wpfb-json">
  <Label label={field.label} {path} {ctx} {onchange} />
  <textarea id={fid(path)} rows="6" spellcheck="false" value={text} onchange={(e) => commit(e.currentTarget.value)}></textarea>
  {#if error}<p class="wpfb-error">{error}</p>{/if}
</div>
