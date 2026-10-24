<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import { fid, type FieldCtx, type OnChange } from "./context";
  import Label from "./Label.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
</script>

<div class="wpfb-field wpfb-check">
  <input id={fid(path)} type="checkbox" checked={value === true} onchange={(e) => onchange(path, e.currentTarget.checked)} />
  <Label label={field.label} {path} {ctx} {onchange} />
</div>
