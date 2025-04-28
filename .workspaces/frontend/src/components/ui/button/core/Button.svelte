<script lang="ts">
  import { type ButtonProps } from "../interface";

  export let variant: ButtonProps["variant"] = "primary";
  export let size: ButtonProps["size"] = "default";
  export let disabled = false;
  export let type: "button" | "submit" | "reset" = "button";

  $: classes = `button button-${variant} ${size !== "default" ? `button-${size}` : ""}`;
</script>

<button class={classes} {disabled} {type}>
  <slot></slot>
</button>
