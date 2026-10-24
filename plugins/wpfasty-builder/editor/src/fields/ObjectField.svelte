<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import type { FieldCtx, OnChange } from "./context";
  import { isObj } from "../util";
  import FieldRenderer from "./FieldRenderer.svelte";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const fields = $derived(field.type === "object" ? Object.entries(field.objectFields) : []);
  const obj = $derived(isObj(value) ? value : {});
</script>

<fieldset class="wpfb-group">
  <legend><Label label={field.label} {path} {ctx} {onchange} tag="span" /></legend>
  {#each fields as [k, f] (k)}
    <FieldRenderer field={f} path={[...path, k]} value={obj[k]} {onchange} {ctx} />
  {/each}
</fieldset>
