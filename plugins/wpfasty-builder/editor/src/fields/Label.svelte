<script lang="ts">
  import Icon from "../Icon.svelte";
  import { RotateCcw } from "../icons";
  import type { FieldCtx, OnChange } from "./context";
  import { fid } from "./context";

  let { label, path, ctx, onchange, tag = "label" }: { label: string; path: string[]; ctx: FieldCtx; onchange: OnChange; tag?: "label" | "span" } = $props();
  const set = $derived(ctx.overridden(path));
</script>

<span class="wpfb-label" class:is-set={set}>
  {#if tag === "label"}<label for={fid(path)}>{label}</label>{:else}<span>{label}</span>{/if}
  {#if set}
    <button type="button" class="wpfb-reset" title="Reset to the variant sample" aria-label="Reset {label}" onclick={() => onchange(path, undefined)}>
      <Icon icon={RotateCcw} size={12} />
    </button>
  {/if}
</span>
