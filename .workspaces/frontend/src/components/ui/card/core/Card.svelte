<script lang="ts">
  import { type CardProps, cardVariants } from "../interface";
  import Badge from "../../badge/core/Badge.svelte";

  export let variant: CardProps["variant"] = "default";
  export let size: CardProps["size"] = "default";
  export let title: string = "";
  export let description: string = "";
  export let badges: { text: string; variant?: string }[] = [];

  // Generate semantic classes
  $: classes = `card card-${variant}${size !== "default" ? ` card-${size}` : ""}`;
</script>

<div class={classes}>
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
