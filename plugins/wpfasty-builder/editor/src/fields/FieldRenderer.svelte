<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import type { FieldCtx, OnChange } from "./context";
  import { registry } from "./registry";
  import BindingField from "./BindingField.svelte";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const kind = $derived(ctx.bindable[path.join(".")]);
  const Comp = $derived(registry[field.type] ?? registry.json);
</script>

{#if kind !== undefined}
  <BindingField {field} {path} {value} {onchange} {ctx} {kind} />
{:else}
  <Comp {field} {path} {value} {onchange} {ctx} />
{/if}
