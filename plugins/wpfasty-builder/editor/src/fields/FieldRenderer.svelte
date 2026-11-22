<script lang="ts">
  import type { Json } from "../types";
  import type { Field } from "./schema";
  import type { FieldCtx, OnChange } from "./context";
  import { registry } from "./registry";

  let { field, path, value, onchange, ctx }: { field: Field; path: string[]; value: Json | undefined; onchange: OnChange; ctx: FieldCtx } = $props();
  const Comp = $derived(registry[field.type] ?? registry.json);
</script>

<Comp {field} {path} {value} {onchange} {ctx} />
