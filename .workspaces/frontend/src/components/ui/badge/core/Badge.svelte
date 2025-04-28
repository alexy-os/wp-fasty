<script lang="ts">
  import { type BadgeProps, badgeVariants } from "../interface";

  export let variant: BadgeProps["variant"] = "default";
  export let className: string = "";
  export let tag: "span" | "div" = "span";

  $: classes = `badge badge-${variant} ${className}`;
</script>

<svelte:element this={tag} class={classes}>
  <slot />
</svelte:element>
