<script lang="ts">
  import type { HTMLAttributes } from "svelte/elements";
  import { cn } from "$lib/utils.js";
  import { type CardProps, cardVariants } from "./interface";

  type $$Props = HTMLAttributes<HTMLDivElement> & CardProps;

  let className: $$Props["class"] = undefined;
  export { className as class };
  export let variant: CardProps["variant"] = "default";
  export let size: CardProps["size"] = "default";

  // Generate semantic classes based on props
  $: variantClasses = `card card-${variant}${size !== "default" ? ` card-${size}` : ""}`;

  // Combine semantic classes with any additional custom classes
  $: combinedClasses = cn(variantClasses, className);
</script>

<div
  class={combinedClasses}
  {...$$restProps}
  on:click
  on:focusin
  on:focusout
  on:mouseenter
  on:mouseleave
>
  {#if title}
    <div class="card-header">
      <h3 class="card-title">{title}</h3>
    </div>
  {/if}

  {#if badges.length > 0}
    <div class="card-badges">
      {#each badges as badge}
        <Badge variant={badge.variant}>{badge.text}</Badge>
      {/each}
    </div>
  {/if}

  {#if description}
    <div class="card-content">
      <p class="card-description">{description}</p>
    </div>
  {/if}

  <div class="card-footer">
    <slot name="footer" />
  </div>
</div>
